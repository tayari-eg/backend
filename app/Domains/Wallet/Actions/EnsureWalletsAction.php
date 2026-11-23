<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;

class EnsureWalletsAction
{
    /**
     * Creates the wallets a posting will touch. Call it before opening the transaction that posts: inside one, MySQL's
     * REPEATABLE READ can hide a wallet another request creates at the same moment, so the posting then only reads
     * wallets that already exist.
     *
     * @param  list<array{int, WalletType}>  $userWallets  the owner's user id and the wallet type
     * @param  list<SystemWallet>  $systemWallets
     */
    public function handle(array $userWallets, array $systemWallets = []): void
    {
        foreach ($userWallets as [$userId, $type]) {
            Wallet::ensureFor($userId, $type);
        }

        foreach ($systemWallets as $code) {
            Wallet::system($code);
        }
    }
}
