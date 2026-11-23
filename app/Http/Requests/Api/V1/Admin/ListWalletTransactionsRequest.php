<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\FiltersWalletTransactions;
use Illuminate\Foundation\Http\FormRequest;

class ListWalletTransactionsRequest extends FormRequest
{
    use FiltersWalletTransactions;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->walletTransactionRules();
    }
}
