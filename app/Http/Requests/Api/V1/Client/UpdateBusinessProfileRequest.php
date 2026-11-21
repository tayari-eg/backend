<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Domains\Accounts\Enums\BusinessLinkType;
use App\Domains\Accounts\Models\BusinessType;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessProfileRequest extends FormRequest
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
            'business_type_id' => ['sometimes', 'integer', Rule::exists(BusinessType::class, 'id')->where(fn (Builder $query) => $query->where('is_active', true))],
            'business_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'avg_daily_orders' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000'],
            'tax_registration_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'links' => ['sometimes', 'array', 'max:10'],
            'links.*.type' => ['required', Rule::enum(BusinessLinkType::class)],
            'links.*.url' => ['required', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        return $this->safe()->only(['business_type_id', 'business_name', 'description', 'avg_daily_orders', 'tax_registration_number']);
    }

    /**
     * @return list<array{type: string, url: string}>|null
     */
    public function links(): ?array
    {
        return $this->has('links') ? array_values($this->validated('links', [])) : null;
    }
}
