<?php

namespace App\Http\Requests\Api\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->user()->businessProfile;

        return $profile !== null && $this->user()->can('update', $profile);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:'.implode(',', config('tayari.media.allowed_mimes')), 'max:'.config('tayari.media.max_upload_kb')],
        ];
    }
}
