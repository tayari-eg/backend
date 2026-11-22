<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Domains\Accounts\Enums\AppMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppModeRequest extends FormRequest
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
            'mode' => ['required', Rule::enum(AppMode::class)],
        ];
    }

    public function mode(): AppMode
    {
        return AppMode::from($this->validated('mode'));
    }
}
