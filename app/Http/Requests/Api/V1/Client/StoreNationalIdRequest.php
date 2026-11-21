<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Domains\Accounts\Enums\DocumentCollection;
use Illuminate\Foundation\Http\FormRequest;

class StoreNationalIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('submitNationalId', $this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $document = ['required', 'file', 'mimes:'.implode(',', config('tayari.media.allowed_mimes')), 'max:'.config('tayari.media.max_upload_kb')];

        return [
            DocumentCollection::NationalIdFront->value => $document,
            DocumentCollection::NationalIdBack->value => $document,
        ];
    }
}
