<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Domains\Accounts\Enums\Locale;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A wallet adjustment reason as staff edit it: the name in every language.
 *
 * @mixin WalletAdjustmentReason
 */
class WalletAdjustmentReasonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => collect(Locale::values())
                ->mapWithKeys(fn (string $locale): array => [$locale => $this->getTranslation('name', $locale, false)])
                ->all(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'postings_count' => $this->whenCounted('postings'),
            'created_at' => $this->created_at?->toIso8601ZuluString(),
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
        ];
    }
}
