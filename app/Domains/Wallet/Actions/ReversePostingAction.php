<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Data\PostingLine;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Domains\Wallet\Models\WalletPosting;
use App\Domains\Wallet\Models\WalletTransaction;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

class ReversePostingAction
{
    public function __construct(private PostToWalletsAction $postToWallets) {}

    /**
     * Undoes a settlement or an adjustment recorded by mistake with a mirror posting; the original stays. A posting is
     * reversed only once: asking again returns the same reversal. Its wallets are the original's, so they all exist.
     *
     * @throws ValidationException (errors.posting) for an order, cancel fee, bonus or reversal posting
     */
    public function handle(WalletPosting $posting, WalletAdjustmentReason $reason, string $note, User $staff): WalletPosting
    {
        if (! $posting->type->canBeReversed()) {
            throw ValidationException::withMessages(['posting' => __('wallet.cannot_reverse')]);
        }

        $lines = $posting->transactions()->with('wallet')->get()
            ->map(fn (WalletTransaction $transaction): PostingLine => new PostingLine(
                $transaction->wallet,
                $transaction->type,
                BigDecimal::of((string) $transaction->amount)->negated(),
                $transaction->order_id,
            ))
            ->all();

        return $this->postToWallets->handle(PostingType::Reversal, "reversal:{$posting->id}", $lines, attributes: [
            'created_by' => $staff->id,
            'reason_id' => $reason->id,
            'note' => $note,
            'reversal_of_id' => $posting->id,
        ]);
    }
}
