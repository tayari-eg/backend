<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Data\PostingLine;
use App\Domains\Wallet\Data\WalletSummary;
use App\Domains\Wallet\Enums\SettlementKind;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletPosting;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RecordSettlementAction
{
    public function __construct(private PostToWalletsAction $postToWallets) {}

    /**
     * Staff record money that changed hands outside the app: a payout to a tayar or a business (at most what is payable
     * now), or cash a tayar handed in (at most what the tayar owes). Decided on the locked wallet; the same request key
     * returns the settlement already recorded.
     *
     * @throws ValidationException (errors.amount) when the amount is more than allowed, (errors.wallet) for a system
     *                             wallet or cash handed in on a business wallet
     */
    public function handle(Wallet $wallet, SettlementKind $kind, BigDecimal $amount, string $reference, ?string $note, string $requestKey, User $staff): WalletPosting
    {
        if ($wallet->isSystem() || ($kind === SettlementKind::CashHandedIn && $wallet->type !== WalletType::Tayar)) {
            throw ValidationException::withMessages(['wallet' => __("wallet.{$kind->value}_wrong_wallet")]);
        }

        $type = $kind === SettlementKind::Payout ? TransactionType::Payout : TransactionType::CashHandedIn;
        $walletAmount = $kind === SettlementKind::Payout ? $amount->negated() : $amount;
        // Created before PostToWalletsAction opens its transaction.
        $treasury = Wallet::system(SystemWallet::Treasury);

        return $this->postToWallets->handle($kind->postingType(), "settlement:{$wallet->id}:{$requestKey}", [
            new PostingLine($wallet, $type, $walletAmount),
            new PostingLine($treasury, $type, $walletAmount->negated()),
        ], attributes: [
            'created_by' => $staff->id,
            'reference' => $reference,
            'note' => $note,
        ], guard: function (Collection $wallets) use ($wallet, $kind, $amount): void {
            $summary = WalletSummary::of($wallets[$wallet->id]);
            $limit = $kind === SettlementKind::Payout ? $summary->payable : $summary->cashToHandIn;

            if ($amount->isGreaterThan($limit)) {
                throw ValidationException::withMessages(['amount' => __("wallet.{$kind->value}_too_much", ['amount' => (string) $limit->toScale(2)])]);
            }
        });
    }
}
