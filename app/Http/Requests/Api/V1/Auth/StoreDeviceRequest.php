<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Domains\Accounts\Enums\Platform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
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
            'platform' => ['required', Rule::enum(Platform::class)],
            'push_token' => ['required', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:20'],
        ];
    }
}
