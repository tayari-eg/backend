<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesCancelReason;
use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    use ValidatesCancelReason;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->cancelReasonRules();
    }
}
