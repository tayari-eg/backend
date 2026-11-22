<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Domains\Tayars\Enums\TayarDocument;
use App\Domains\Tayars\Enums\VehicleDocument;
use App\Domains\Tayars\Models\TayarProfile;
use App\Http\Requests\Concerns\ValidatesTayarApplication;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTayarProfileRequest extends FormRequest
{
    use ValidatesTayarApplication;

    public function authorize(): Response
    {
        return Gate::forUser($this->user())->inspect('create', TayarProfile::class);
    }

    /**
     * The driving licence, the vehicle licence and the plate are only required when the vehicle type needs a licence.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $requiresLicense = Rule::requiredIf(fn (): bool => $this->vehicleTypeRequiresLicense($this->input('vehicle_type_id')));
        $rules = [
            'national_id_number' => ['required', ...$this->nationalIdNumberRules()],
            'vehicle_type_id' => ['required', ...$this->vehicleTypeRules()],
            'plate_number' => [$requiresLicense, ...$this->plateNumberRules()],
            ...$this->vehicleDetailRules(),
            VehicleDocument::VehicleLicense->value => [$requiresLicense, 'nullable', ...$this->fileRules()],
            VehicleDocument::VehiclePhotos->value => ['required', 'array', 'min:1', 'max:'.VehicleDocument::MAX_PHOTOS],
            VehicleDocument::VehiclePhotos->value.'.*' => ['required', ...$this->imageRules()],
        ];

        foreach (TayarDocument::cases() as $document) {
            $fileRules = $document === TayarDocument::PersonalPhoto ? $this->imageRules() : $this->fileRules();

            $rules[$document->value] = $document->isAlwaysRequired()
                ? ['required', ...$fileRules]
                : [$requiresLicense, 'nullable', ...$fileRules];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->tayarApplicationMessages();
    }

    /**
     * @return array{vehicle_type_id: int, plate_number: ?string, model: ?string, color: ?string, year: ?int}
     */
    public function vehicleAttributes(): array
    {
        return [
            'vehicle_type_id' => (int) $this->validated('vehicle_type_id'),
            'plate_number' => $this->validated('plate_number'),
            'model' => $this->validated('model'),
            'color' => $this->validated('color'),
            'year' => $this->validated('year') === null ? null : (int) $this->validated('year'),
        ];
    }

    /**
     * @return array<string, UploadedFile>
     */
    public function documents(): array
    {
        $documents = [];

        foreach (TayarDocument::cases() as $document) {
            if ($this->hasFile($document->value)) {
                $documents[$document->value] = $this->file($document->value);
            }
        }

        return $documents;
    }

    /**
     * @return array{vehicle_license: ?UploadedFile, vehicle_photos: list<UploadedFile>}
     */
    public function vehicleDocuments(): array
    {
        return [
            'vehicle_license' => $this->file(VehicleDocument::VehicleLicense->value),
            'vehicle_photos' => array_values($this->file(VehicleDocument::VehiclePhotos->value, [])),
        ];
    }
}
