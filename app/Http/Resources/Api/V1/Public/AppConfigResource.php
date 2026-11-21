<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{min_supported_versions: array{android: ?string, ios: ?string}, latest_versions: array{android: ?string, ios: ?string}} $resource
 */
class AppConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{min_supported_version: array{android: ?string, ios: ?string}, latest_version: array{android: ?string, ios: ?string}, server_time: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'min_supported_version' => [
                'android' => $this->resource['min_supported_versions']['android'] ?? null,
                'ios' => $this->resource['min_supported_versions']['ios'] ?? null,
            ],
            'latest_version' => [
                'android' => $this->resource['latest_versions']['android'] ?? null,
                'ios' => $this->resource['latest_versions']['ios'] ?? null,
            ],
            'server_time' => now('UTC')->toIso8601ZuluString(),
        ];
    }
}
