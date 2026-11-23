<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesWalletAdjustmentReason;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreWalletAdjustmentRequest extends FormRequest
{
    use ValidatesWalletAdjustmentReason;

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
            // Positive gives the owner money, negative takes it.
            'amount' => ['required', 'decimal:0,2', 'min:-99999999.99', 'max:99999999.99', function (string $attribute, mixed $value, Closure $fail): void {
                if (BigDecimal::of((string) $value)->isZero()) {
                    $fail('wallet.amount_zero')->translate();
                }
            }],
            ...$this->adjustmentReasonRules(),
        ];
    }

    public function amount(): BigDecimal
    {
        return BigDecimal::of((string) $this->validated('amount'))->toScale(2);
    }
}
