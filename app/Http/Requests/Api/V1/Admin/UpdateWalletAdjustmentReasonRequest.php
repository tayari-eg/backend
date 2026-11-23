<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletAdjustmentReasonRequest extends FormRequest
{
    use ValidatesUniqueTranslatedNames;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Send only the fields that change; a name always needs both languages.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->uniqueTranslatedNameRules('sometimes', WalletAdjustmentReason::class, 'admin.wallet_adjustment_reason_name_taken', $this->route('walletAdjustmentReason')?->id),
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
