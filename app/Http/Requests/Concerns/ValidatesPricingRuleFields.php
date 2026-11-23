<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesPricingRuleFields
{
    /**
     * @var list<string>
     */
    private static array $amountFields = ['base_fare', 'price_per_km', 'minimum_fare', 'food_extra', 'tayar_share_percent'];

    /**
     * @return array<string, mixed>
     */
    protected function pricingRuleRules(string $presence): array
    {
        $amount = ['numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'];

        return [
            'mode_id' => [$presence, 'integer', Rule::exists('modes', 'id')],
            'vehicle_type_id' => ['sometimes', 'nullable', 'integer', Rule::exists('vehicle_types', 'id')],
            'base_fare' => [$presence, ...$amount],
            'price_per_km' => [$presence, ...$amount],
            'minimum_fare' => [$presence, ...$amount],
            'food_extra' => ['sometimes', 'nullable', ...$amount],
            'tayar_share_percent' => [$presence, 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The validated fields with amounts as decimal strings, so no float reaches the Action.
     *
     * @return array<string, mixed>
     */
    public function pricingRuleAttributes(): array
    {
        $attributes = $this->validated();

        foreach (self::$amountFields as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = (string) $attributes[$field];
            }
        }

        foreach (['mode_id', 'vehicle_type_id'] as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = (int) $attributes[$field];
            }
        }

        return $attributes;
    }
}
