<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string', 'max:64', 'current_password:sanctum'],
            'password' => ['required', 'string', 'max:64', 'confirmed', 'different:current_password', Password::min(8)],
            'password_confirmation' => ['required', 'string', 'max:64'],
        ];
    }
}
