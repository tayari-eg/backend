<?php

namespace Database\Seeders;

use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Models\Wallet;
use Illuminate\Database\Seeder;

class SystemWalletSeeder extends Seeder
{
    /**
     * Idempotent: creates each of Tayari's system wallets that is missing and never touches an existing balance.
     */
    public function run(): void
    {
        foreach (SystemWallet::cases() as $code) {
            Wallet::system($code);
        }
    }
}
