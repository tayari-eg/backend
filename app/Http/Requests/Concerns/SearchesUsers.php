<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Accounts\Data\PhoneNumber;

/**
 * The admin user search: a full phone, part of a phone, or a name (see User::whereMatchesSearch()).
 */
trait SearchesUsers
{
    private int $minPhoneSearchDigits = 4;

    /**
     * @return array<string, list<string>>
     */
    protected function userSearchRules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    private function searchTerm(): ?string
    {
        $search = trim(PhoneNumber::westernDigits((string) $this->validated('search')));

        return $search === '' ? null : $search;
    }

    /**
     * A full Egyptian mobile number in any format, matched exactly.
     */
    public function searchPhone(): ?PhoneNumber
    {
        return PhoneNumber::tryFrom($this->searchTerm());
    }

    /**
     * Part of a phone number (digits only, at least 4), without a leading 0 so 0100123 also matches +20100123….
     */
    public function searchPhoneDigits(): ?string
    {
        $search = $this->searchTerm();

        if ($search === null || $this->searchPhone() !== null || ! ctype_digit($search) || strlen($search) < $this->minPhoneSearchDigits) {
            return null;
        }

        return str_starts_with($search, '0') ? substr($search, 1) : $search;
    }

    public function searchName(): ?string
    {
        return $this->searchPhone() === null && $this->searchPhoneDigits() === null ? $this->searchTerm() : null;
    }
}
