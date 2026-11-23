<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Wallet\Enums\SettlementKind;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSettlementRequest extends FormRequest
{
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
            'idempotency_key' => ['required', 'uuid'],
            'kind' => ['required', Rule::enum(SettlementKind::class)],
            'amount' => ['required', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'reference' => ['required', 'string', 'max:100'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function kind(): SettlementKind
    {
        return SettlementKind::from($this->validated('kind'));
    }

    public function amount(): BigDecimal
    {
        return BigDecimal::of((string) $this->validated('amount'))->toScale(2);
    }

    public function note(): ?string
    {
        $note = trim((string) $this->validated('note'));

        return $note === '' ? null : $note;
    }
}
