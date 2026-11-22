<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Domains\Tayars\Enums\VehicleDocument;
use App\Http\Requests\Concerns\ValidatesTayarApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreVehicleDocumentRequest extends FormRequest
{
    use ValidatesTayarApplication;

    public function authorize(): bool
    {
        $profile = $this->user()->tayarProfile;

        return $profile?->vehicle !== null && $this->user()->can('update', $profile);
    }

    /**
     * The licence is one `file`; the photos are 1 to 4 `files`, which replace all the current photos.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->route('collection') === VehicleDocument::VehicleLicense) {
            return ['file' => ['required', ...$this->fileRules()]];
        }

        return [
            'files' => ['required', 'array', 'min:1', 'max:'.VehicleDocument::MAX_PHOTOS],
            'files.*' => ['required', ...$this->imageRules()],
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploadedFiles(): array
    {
        return $this->route('collection') === VehicleDocument::VehicleLicense
            ? [$this->file('file')]
            : array_values($this->file('files', []));
    }
}
