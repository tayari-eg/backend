<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Pricing\Models\PricingRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * A pricing rule. Amounts are decimal strings with two decimals (EGP); vehicle_type null means every vehicle.
 *
 * @mixin PricingRule
 */
class PricingRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mode' => $this->whenLoaded('mode', fn (): array => [
                'id' => $this->mode->id,
                'code' => $this->mode->code->value,
                'name' => self::locales()->mapWithKeys(fn (string $locale): array => [$locale => $this->mode->getTranslation('name', $locale, false)])->all(),
            ]),
            'vehicle_type' => $this->whenLoaded('vehicleType', fn (): ?array => $this->vehicleType === null ? null : [
                'id' => $this->vehicleType->id,
                'name' => self::locales()->mapWithKeys(fn (string $locale): array => [$locale => $this->vehicleType->getTranslation('name', $locale, false)])->all(),
            ]),
            'base_fare' => $this->base_fare,
            'price_per_km' => $this->price_per_km,
            'minimum_fare' => $this->minimum_fare,
            'food_extra' => $this->food_extra,
            'tayar_share_percent' => $this->tayar_share_percent,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private static function locales(): Collection
    {
        return collect(Locale::values());
    }
}
