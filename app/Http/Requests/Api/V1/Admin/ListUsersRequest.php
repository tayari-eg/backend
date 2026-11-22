<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Http\Requests\Concerns\PaginatesResults;
use App\Http\Requests\Concerns\SearchesUsers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListUsersRequest extends FormRequest
{
    use PaginatesResults, SearchesUsers;

    /**
     * The national_id_status filter value for users who never uploaded a national ID.
     */
    public const string NO_NATIONAL_ID = 'none';

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
            ...$this->userSearchRules(),
            'account_type' => ['sometimes', Rule::enum(AccountType::class)],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            // Query strings send booleans as text.
            'phone_verified' => ['sometimes', 'in:true,false,1,0'],
            'national_id_status' => ['sometimes', Rule::in([self::NO_NATIONAL_ID, ...array_column(VerificationStatus::cases(), 'value')])],
            'business_profile_status' => ['sometimes', Rule::enum(VerificationStatus::class)],
            ...$this->paginationRules(),
        ];
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
