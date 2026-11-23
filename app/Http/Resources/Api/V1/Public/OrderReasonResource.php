<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Domains\Orders\Models\OrderReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderReason
 */
class OrderReasonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
        ];
    }
}
