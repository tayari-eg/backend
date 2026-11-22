<?php

namespace App\Http\Requests\Concerns;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Tayars\Models\VehicleType;
use App\Rules\EgyptianNationalIdNumber;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * Field rules shared by the tayar application and its later changes.
 */
trait ValidatesTayarApplication
{
    /**
     * National ID numbers are often typed with Arabic digits or spaces.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('national_id_number'))) {
            $this->merge(['national_id_number' => preg_replace('/\s+/', '', PhoneNumber::westernDigits($this->input('national_id_number')))]);
        }
    }

    /**
     * @return list<mixed>
     */
    protected function nationalIdNumberRules(?int $ignoreProfileId = null): array
    {
        return ['string', new EgyptianNationalIdNumber, Rule::unique('tayar_profiles', 'national_id_number')->ignore($ignoreProfileId)];
    }

    /**
     * @return list<mixed>
     */
    protected function vehicleTypeRules(): array
    {
        return ['integer', Rule::exists(VehicleType::class, 'id')->where(fn (Builder $query) => $query->where('is_active', true))];
    }

    /**
     * @return list<mixed>
     */
    protected function plateNumberRules(?int $ignoreVehicleId = null): array
    {
        return ['nullable', 'string', 'max:20', Rule::unique('vehicles', 'plate_number')->ignore($ignoreVehicleId)];
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function vehicleDetailRules(): array
    {
        return [
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
        ];
    }

    /**
     * @return list<string>
     */
    protected function fileRules(): array
    {
        return ['file', 'mimes:'.implode(',', config('tayari.media.allowed_mimes')), 'max:'.config('tayari.media.max_upload_kb')];
    }

    /**
     * Photos must be images: the same size limit as other files, without pdf.
     *
     * @return list<string>
     */
    protected function imageRules(): array
    {
        return ['file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('tayari.media.max_upload_kb')];
    }

    /**
     * @return array<string, string>
     */
    protected function tayarApplicationMessages(): array
    {
        return ['national_id_number.unique' => __('tayars.national_id_number_taken')];
    }

    protected function vehicleTypeRequiresLicense(mixed $vehicleTypeId): bool
    {
        return is_numeric($vehicleTypeId) && VehicleType::query()->whereKey($vehicleTypeId)->value('requires_license') === true;
    }
}
