<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRoleRequest extends FormRequest
{
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
            'role' => ['required', Rule::enum(StaffRole::class)->only(StaffRole::assignable())],
        ];
    }

    public function role(): StaffRole
    {
        return StaffRole::from($this->validated('role'));
    }
}
