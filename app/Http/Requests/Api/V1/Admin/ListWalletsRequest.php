<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Wallet\Enums\WalletType;
use App\Http\Requests\Concerns\PaginatesResults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListWalletsRequest extends FormRequest
{
    use PaginatesResults;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(WalletType::class)],
            // Query strings send booleans as text.
            'owing' => ['sometimes', 'in:true,false,1,0'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'in:balance,-balance'],
            ...$this->paginationRules(),
        ];
    }

    public function walletType(): ?WalletType
    {
        return $this->has('type') ? WalletType::from($this->validated('type')) : null;
    }

    public function owing(): bool
    {
        return $this->boolean('owing');
    }

    public function search(): ?string
    {
        return filled($this->validated('search')) ? trim($this->validated('search')) : null;
    }

    public function searchPhone(): ?PhoneNumber
    {
        return PhoneNumber::tryFrom($this->search());
    }

    public function sort(): ?string
    {
        return $this->validated('sort');
    }
}
