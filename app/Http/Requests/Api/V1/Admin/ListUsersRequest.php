<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Http\Requests\Concerns\PaginatesResults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListUsersRequest extends FormRequest
{
    use PaginatesResults;

    /**
     * The national_id_status filter value for users who never uploaded a national ID.
     */
    public const string NO_NATIONAL_ID = 'none';

    private const int MIN_PHONE_SEARCH_DIGITS = 4;

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
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'account_type' => ['sometimes', Rule::enum(AccountType::class)],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            // Query strings send booleans as text.
            'phone_verified' => ['sometimes', 'in:true,false,1,0'],
            'national_id_status' => ['sometimes', Rule::in([self::NO_NATIONAL_ID, ...array_column(VerificationStatus::cases(), 'value')])],
            'business_profile_status' => ['sometimes', Rule::enum(VerificationStatus::class)],
            ...$this->paginationRules(),
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

        if ($search === null || $this->searchPhone() !== null || ! ctype_digit($search) || strlen($search) < self::MIN_PHONE_SEARCH_DIGITS) {
            return null;
        }

        return str_starts_with($search, '0') ? substr($search, 1) : $search;
    }

    public function searchName(): ?string
    {
        return $this->searchPhone() === null && $this->searchPhoneDigits() === null ? $this->searchTerm() : null;
    }

    public function phoneVerified(): ?bool
    {
        return $this->has('phone_verified') ? $this->boolean('phone_verified') : null;
    }

    public function filtersNationalIdStatus(): bool
    {
        return $this->has('national_id_status');
    }

    public function nationalIdStatus(): ?VerificationStatus
    {
        return VerificationStatus::tryFrom((string) $this->validated('national_id_status'));
    }
}
