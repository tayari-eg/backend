<?php

namespace App\Http\Requests\Api\V1\Client\Wallet;

use App\Http\Requests\Concerns\FiltersWalletTransactions;

class ListWalletTransactionsRequest extends ShowWalletRequest
{
    use FiltersWalletTransactions;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->walletTransactionRules();
    }
}
