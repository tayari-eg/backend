<?php

namespace App\Http\Resources\Api\V1\Client;

use App\Domains\Accounts\Models\BusinessLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BusinessLink
 */
class BusinessLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}
