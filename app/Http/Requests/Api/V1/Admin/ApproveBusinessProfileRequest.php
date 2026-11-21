<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveBusinessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('businessProfile'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
