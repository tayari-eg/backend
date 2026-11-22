<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Domains\Tayars\Models\VehicleType;
use App\Http\Requests\Concerns\ValidatesUniqueTranslatedNames;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleTypeRequest extends FormRequest
{
    use ValidatesUniqueTranslatedNames;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Send only the fields that change; a name always needs both languages.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->uniqueTranslatedNameRules('sometimes', VehicleType::class, 'admin.vehicle_type_name_taken', $this->route('vehicleType')?->id),
            'requires_license' => ['sometimes', 'boolean'],
            'can_carry_food' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
