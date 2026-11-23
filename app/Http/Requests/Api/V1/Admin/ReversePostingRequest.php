<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesWalletAdjustmentReason;
use Illuminate\Foundation\Http\FormRequest;

class ReversePostingRequest extends FormRequest
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
        return $this->adjustmentReasonRules();
    }
}
