<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Data\PostingLine;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Domains\Wallet\Models\WalletPosting;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

class AdjustWalletAction
{
    public function __construct(private PostToWalletsAction $postToWallets) {}

    /**
     * A manual correction with a reason: positive gives the owner money, negative takes it (the balance may go
     * negative). The same request key returns the adjustment already made.
     *
     * @throws ValidationException (errors.wallet) for a system wallet
     */
    public function handle(Wallet $wallet, BigDecimal $amount, WalletAdjustmentReason $reason, string $note, string $requestKey, User $staff): WalletPosting
    {
        if ($wallet->isSystem()) {
            throw ValidationException::withMessages(['wallet' => __('wallet.system_wallet')]);
        }

        // Created before PostToWalletsAction opens its transaction.
        $adjustments = Wallet::system(SystemWallet::Adjustments);

        return $this->postToWallets->handle(PostingType::Adjustment, "adjustment:{$wallet->id}:{$requestKey}", [
            new PostingLine($wallet, TransactionType::Adjustment, $amount),
            new PostingLine($adjustments, TransactionType::Adjustment, $amount->negated()),
        ], attributes: [
            'created_by' => $staff->id,
            'reason_id' => $reason->id,
            'note' => $note,
        ]);
    }
}
