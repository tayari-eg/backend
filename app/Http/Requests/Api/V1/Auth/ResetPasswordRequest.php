<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Concerns\NormalizesPhoneNumber;
use App\Rules\EgyptianMobileNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    use NormalizesPhoneNumber;

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
            'phone' => ['required', 'string', new EgyptianMobileNumber],
            'code' => ['required', 'string', 'max:10'],
            'password' => ['required', 'string', 'max:64', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required', 'string', 'max:64'],
        ];
    }
}
