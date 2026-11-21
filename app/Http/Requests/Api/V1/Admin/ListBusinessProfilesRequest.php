<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Accounts\Enums\VerificationStatus;
use App\Http\Requests\Concerns\PaginatesResults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListBusinessProfilesRequest extends FormRequest
{
    use PaginatesResults;

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
            'status' => ['sometimes', Rule::enum(VerificationStatus::class)],
            ...$this->paginationRules(),
        ];
    }

    public function status(): VerificationStatus
    {
        return VerificationStatus::from($this->validated('status', VerificationStatus::Pending->value));
    }
}
