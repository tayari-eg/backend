<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Domains\Tayars\Enums\TayarDocument;
use App\Http\Requests\Concerns\ValidatesTayarApplication;
use Illuminate\Foundation\Http\FormRequest;

class StoreTayarDocumentRequest extends FormRequest
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
        $isPhoto = $this->route('collection') === TayarDocument::PersonalPhoto;

        return [
            'file' => ['required', ...($isPhoto ? $this->imageRules() : $this->fileRules())],
        ];
    }
}
