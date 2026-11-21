<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VerifyNationalIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviewNationalId', $this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
