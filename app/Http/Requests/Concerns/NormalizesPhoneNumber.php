<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Accounts\Data\PhoneNumber;

/**
 * Rewrites the phone input to +20… before validation; an invalid number is left as typed so the rule rejects it.
 */
trait NormalizesPhoneNumber
{
    protected function prepareForValidation(): void
    {
        $phone = PhoneNumber::tryFrom($this->input('phone'));

        if ($phone !== null) {
            $this->merge(['phone' => $phone->value]);
        }
    }

    public function phoneNumber(): PhoneNumber
    {
        return PhoneNumber::from($this->validated('phone'));
    }
}
