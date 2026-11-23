<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Illuminate\Foundation\Http\FormRequest;

class StoreWalletAdjustmentReasonRequest extends FormRequest
{
    use ValidatesUniqueTranslatedNames;

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
            ...$this->uniqueTranslatedNameRules('required', WalletAdjustmentReason::class, 'admin.wallet_adjustment_reason_name_taken'),
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
