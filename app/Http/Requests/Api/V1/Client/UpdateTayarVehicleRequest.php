<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Domains\Tayars\Enums\TayarDocument;
use App\Domains\Tayars\Enums\VehicleDocument;
use App\Http\Requests\Concerns\ValidatesTayarApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTayarVehicleRequest extends FormRequest
{
    use ValidatesTayarApplication;

    public function authorize(): bool
    {
        $profile = $this->user()->tayarProfile;

        return $profile?->vehicle !== null && $this->user()->can('update', $profile);
    }

    /**
     * Send only the fields that change.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'vehicle_type_id' => ['sometimes', ...$this->vehicleTypeRules()],
            'plate_number' => ['sometimes', ...$this->plateNumberRules($this->user()->tayarProfile?->vehicle?->id)],
            ...collect($this->vehicleDetailRules())->map(fn (array $rules): array => ['sometimes', ...$rules])->all(),
        ];
    }

    /**
     * A vehicle type that needs a licence needs a plate; moving to one also needs both licences already uploaded.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $profile = $this->user()->tayarProfile;
            $vehicle = $profile->vehicle;
            $vehicleTypeId = $this->input('vehicle_type_id', $vehicle->vehicle_type_id);

            if (! $this->vehicleTypeRequiresLicense($vehicleTypeId)) {
                return;
            }

            if (blank($this->has('plate_number') ? $this->input('plate_number') : $vehicle->plate_number)) {
                $validator->errors()->add('plate_number', __('validation.required', ['attribute' => __('validation.attributes.plate_number')]));
            }

            // The licences were checked when the application was sent; only a new type needs them checked again.
            if ((int) $vehicleTypeId === $vehicle->vehicle_type_id) {
                return;
            }

            if (! $profile->hasMedia(TayarDocument::DrivingLicense->value)) {
                $validator->errors()->add(TayarDocument::DrivingLicense->value, __('validation.required', ['attribute' => __('validation.attributes.driving_license')]));
            }

            if (! $vehicle->hasMedia(VehicleDocument::VehicleLicense->value)) {
                $validator->errors()->add(VehicleDocument::VehicleLicense->value, __('validation.required', ['attribute' => __('validation.attributes.vehicle_license')]));
            }
        }];
    }

    /**
     * @return array<string, mixed>
     */
    public function vehicleAttributes(): array
    {
        return $this->safe()->only(['vehicle_type_id', 'plate_number', 'model', 'color', 'year']);
    }
}
