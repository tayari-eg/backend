<?php

namespace Tests\Feature\Domains\Wallet;

use App\Domains\Wallet\Actions\PostToWalletsAction;
use App\Domains\Wallet\Data\PostingLine;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletPosting;
use App\Domains\Wallet\Models\WalletTransaction;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class PostToWalletsActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<array{Wallet, TransactionType, string}>  $lines
     */
    private function postLines(string $key, array $lines): WalletPosting
    {
        return app(PostToWalletsAction::class)->handle(
            PostingType::Adjustment,
            $key,
            array_map(fn (array $line): PostingLine => new PostingLine($line[0], $line[1], BigDecimal::of($line[2])), $lines),
        );
    }

    public function test_it_writes_the_lines_with_their_balance_after_and_updates_the_cached_balances(): void
    {
        $wallet = Wallet::factory()->create();
        $adjustments = Wallet::system(SystemWallet::Adjustments);

        $this->postLines('first', [[$wallet, TransactionType::Adjustment, '100.00'], [$adjustments, TransactionType::Adjustment, '-100.00']]);
        $posting = $this->postLines('second', [[$wallet, TransactionType::Adjustment, '-30.50'], [$adjustments, TransactionType::Adjustment, '30.50']]);

        $this->assertSame('69.50', $wallet->refresh()->balance);
        $this->assertSame('-69.50', $adjustments->refresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', ['posting_id' => $posting->id, 'wallet_id' => $wallet->id, 'amount' => '-30.50', 'balance_after' => '69.50']);
        $this->assertDatabaseHas('wallet_transactions', ['posting_id' => $posting->id, 'wallet_id' => $adjustments->id, 'amount' => '30.50', 'balance_after' => '-69.50']);
        $this->assertSame('0.00', (string) BigDecimal::of((string) Wallet::query()->sum('balance'))->toScale(2));
    }

    public function test_the_same_key_returns_the_first_posting_and_writes_nothing_more(): void
    {
        $wallet = Wallet::factory()->create();
        $lines = [[$wallet, TransactionType::Adjustment, '10.00'], [Wallet::system(SystemWallet::Adjustments), TransactionType::Adjustment, '-10.00']];

        $first = $this->postLines('same-key', $lines);
        $second = $this->postLines('same-key', $lines);

        $this->assertTrue($first->is($second));
        $this->assertFalse($second->wasRecentlyCreated);
        $this->assertDatabaseCount('wallet_postings', 1);
        $this->assertDatabaseCount('wallet_transactions', 2);
        $this->assertSame('10.00', $wallet->refresh()->balance);
    }

    public function test_lines_that_do_not_add_up_to_zero_are_refused_and_nothing_is_written(): void
    {
        $wallet = Wallet::factory()->create();

        try {
            $this->postLines('unbalanced', [[$wallet, TransactionType::Adjustment, '10.00'], [Wallet::system(SystemWallet::Adjustments), TransactionType::Adjustment, '-9.99']]);
            $this->fail('Expected an unbalanced posting to be refused.');
        } catch (LogicException) {
            $this->assertDatabaseCount('wallet_postings', 0);
            $this->assertSame('0.00', $wallet->refresh()->balance);
        }
    }

    public function test_earnings_wait_for_the_payout_hour_in_business_time_and_other_lines_count_at_once(): void
    {
        config(['app.business_timezone' => 'Africa/Cairo', 'tayari.wallet.available_from_hour' => 23]);
        // 10:00 in Cairo (UTC+2 in winter).
        $this->travelTo(CarbonImmutable::parse('2026-01-15 08:00:00', 'UTC'));
        $wallet = Wallet::factory()->create();

        $earning = $this->postToWallet($wallet, '50.00', TransactionType::DeliveryEarning);
        $cash = $this->postToWallet($wallet, '-20.00', TransactionType::CashCollected);

        $this->assertSame('2026-01-15 21:00:00', WalletTransaction::query()->where('posting_id', $earning->id)->where('wallet_id', $wallet->id)->firstOrFail()->available_at->toDateTimeString());
        $this->assertSame('2026-01-15 08:00:00', WalletTransaction::query()->where('posting_id', $cash->id)->where('wallet_id', $wallet->id)->firstOrFail()->available_at->toDateTimeString());
    }

    public function test_an_earning_after_the_payout_hour_counts_at_once(): void
    {
        config(['app.business_timezone' => 'Africa/Cairo', 'tayari.wallet.available_from_hour' => 23]);
        // 23:30 in Cairo.
        $this->travelTo(CarbonImmutable::parse('2026-01-15 21:30:00', 'UTC'));
        $wallet = Wallet::factory()->create();

        $posting = $this->postToWallet($wallet, '50.00', TransactionType::DeliveryEarning);

        $this->assertSame('2026-01-15 21:30:00', WalletTransaction::query()->where('posting_id', $posting->id)->where('wallet_id', $wallet->id)->firstOrFail()->available_at->toDateTimeString());
    }

    public function test_it_locks_the_wallets_in_id_order(): void
    {
        $adjustments = Wallet::system(SystemWallet::Adjustments);
        $wallet = Wallet::factory()->create();
        $locks = [];
        DB::listen(function (QueryExecuted $query) use (&$locks): void {
            if (str_contains($query->sql, 'for update') && str_contains($query->sql, 'wallets')) {
                $locks[] = $query->sql;
            }
        });

        // The higher id first in the lines.
        $this->postLines('ordered', [[$wallet, TransactionType::Adjustment, '5.00'], [$adjustments, TransactionType::Adjustment, '-5.00']]);

        $this->assertCount(1, $locks);
        // MySQL and PostgreSQL take row locks in the order the rows are read.
        $this->assertMatchesRegularExpression('/order by [`"]?id[`"]? asc for update$/', $locks[0]);
    }

    public function test_ledger_rows_are_never_updated_or_deleted(): void
    {
        $posting = $this->postToWallet(Wallet::factory()->create(), '5.00');
        $transaction = WalletTransaction::query()->where('posting_id', $posting->id)->firstOrFail();

        foreach ([fn () => $transaction->update(['amount' => '1.00']), fn () => $transaction->delete(), fn () => $posting->update(['note' => 'x']), fn () => $posting->delete()] as $write) {
            try {
                $write();
                $this->fail('Expected the ledger write to be refused.');
            } catch (LogicException) {
                $this->assertDatabaseHas('wallet_transactions', ['id' => $transaction->id, 'amount' => '5.00']);
            }
        }
    }
}
