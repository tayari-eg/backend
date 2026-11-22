<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Http\Requests\Concerns\ValidatesTayarApplication;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTayarProfileRequest extends FormRequest
{
    use ValidatesTayarApplication;

    public function authorize(): bool
    {
        $profile = $this->user()->tayarProfile;

        return $profile !== null && $this->user()->can('update', $profile);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'national_id_number' => ['sometimes', ...$this->nationalIdNumberRules($this->user()->tayarProfile?->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->tayarApplicationMessages();
    }
}
