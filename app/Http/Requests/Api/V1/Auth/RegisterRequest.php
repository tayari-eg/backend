<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Domains\Accounts\Enums\AccountType;
use App\Http\Requests\Concerns\NormalizesPhoneNumber;
use App\Rules\EgyptianMobileNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use NormalizesPhoneNumber;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Register never touches an existing account: an unverified user finishes through otp/resend and verify-phone.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['bail', 'required', 'string', new EgyptianMobileNumber, Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'max:64', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required', 'string', 'max:64'],
            'account_type' => ['required', Rule::enum(AccountType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.unique' => __('auth.phone_taken'),
        ];
    }
}
