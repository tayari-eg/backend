<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\PaginatesResults;
use Illuminate\Foundation\Http\FormRequest;

class ListPricingRulesRequest extends FormRequest
{
    use PaginatesResults;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mode_id' => ['sometimes', 'integer', 'min:1'],
            'vehicle_type_id' => ['sometimes', 'integer', 'min:1'],
            // Query strings send booleans as text.
            'is_active' => ['sometimes', 'in:true,false,1,0'],
            ...$this->paginationRules(),
        ];
    }

    public function modeId(): ?int
    {
        return $this->has('mode_id') ? (int) $this->validated('mode_id') : null;
    }

    public function vehicleTypeId(): ?int
    {
        return $this->has('vehicle_type_id') ? (int) $this->validated('vehicle_type_id') : null;
    }

    public function isActive(): ?bool
    {
        return $this->has('is_active') ? $this->boolean('is_active') : null;
    }
}
