<?php

namespace App\Rules;

use App\Domains\Accounts\Data\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class EgyptianMobileNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (PhoneNumber::tryFrom($value) === null) {
            $fail('validation.egyptian_mobile_number')->translate();
        }
    }
}
