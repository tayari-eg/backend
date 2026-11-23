<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Wallet\Enums\TransactionType;
use Illuminate\Validation\Rule;

trait FiltersWalletTransactions
{
    use PaginatesResults;

    /**
     * @return array<string, mixed>
     */
    protected function walletTransactionRules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(TransactionType::class)],
            ...$this->paginationRules(),
        ];
    }

    public function transactionType(): ?TransactionType
    {
        return $this->has('type') ? TransactionType::from($this->validated('type')) : null;
    }
}
