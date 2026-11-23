<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Wallet\Data\PostingLine;
use App\Domains\Wallet\Enums\PostingType;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\TransactionType;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletPosting;
use Brick\Math\BigDecimal;

class PostSignupBonusAction
{
    public function __construct(private PostToWalletsAction $postToWallets) {}

    /**
     * Once per tayar ever (the key is the tayar's user), paid from Tayari's bonus budget. It counts in the balance at
     * once, but WalletSummary keeps it from being paid out until the tayar has enough delivered orders. Nothing to post
     * when the configured bonus is not positive.
     */
    public function handle(TayarProfile $profile): ?WalletPosting
    {
        $bonus = BigDecimal::of(config('tayari.wallet.signup_bonus'));

        if (! $bonus->isPositive()) {
            return null;
        }

        return $this->postToWallets->handle(PostingType::SignupBonus, "signup_bonus:{$profile->user_id}", [
            new PostingLine(Wallet::ensureFor($profile->user_id, WalletType::Tayar), TransactionType::Bonus, $bonus),
            new PostingLine(Wallet::system(SystemWallet::BonusExpense), TransactionType::Bonus, $bonus->negated()),
        ], $profile);
    }
}
