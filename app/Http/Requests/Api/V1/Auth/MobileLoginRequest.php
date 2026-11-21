<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Concerns\NormalizesPhoneNumber;
use App\Rules\EgyptianMobileNumber;
use Illuminate\Foundation\Http\FormRequest;

class MobileLoginRequest extends FormRequest
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
            'password' => ['required', 'string', 'max:64'],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }
}
