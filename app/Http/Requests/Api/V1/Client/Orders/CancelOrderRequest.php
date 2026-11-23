<?php

namespace App\Http\Requests\Api\V1\Client\Orders;

use App\Http\Requests\Concerns\ValidatesCancelReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CancelOrderRequest extends FormRequest
{
    use ValidatesCancelReason;

    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('cancel', $this->route('order'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->cancelReasonRules();
    }
}
