<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Domains\Accounts\Enums\OtpPurpose;
use App\Http\Requests\Concerns\NormalizesPhoneNumber;
use App\Rules\EgyptianMobileNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResendOtpRequest extends FormRequest
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
            'purpose' => ['required', Rule::enum(OtpPurpose::class)],
        ];
    }
}
