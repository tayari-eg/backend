<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Tayars\Enums\TayarStatus;
use App\Http\Requests\Concerns\PaginatesResults;
use App\Http\Requests\Concerns\SearchesUsers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTayarApplicationsRequest extends FormRequest
{
    use PaginatesResults, SearchesUsers;

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
            'status' => ['sometimes', Rule::enum(TayarStatus::class)],
            'vehicle_type_id' => ['sometimes', 'integer'],
            ...$this->userSearchRules(),
            ...$this->paginationRules(),
        ];
    }

    public function status(): TayarStatus
    {
        return TayarStatus::from($this->validated('status', TayarStatus::Pending->value));
    }
}
