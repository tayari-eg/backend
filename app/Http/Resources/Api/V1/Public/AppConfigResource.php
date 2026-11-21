<?php

namespace App\Http\Resources\Api\V1\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Fluent;

/**
 * Wraps the app versions from config('tayari'): min_supported_versions and latest_versions, each keyed by android and ios.
 *
 * @property Fluent $resource
 */
class AppConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{min_supported_version: array{android: ?string, ios: ?string}, latest_version: array{android: ?string, ios: ?string}, phone_verification_required: bool, server_time: string}
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
            'phone_verification_required' => config()->boolean('tayari.auth.require_phone_verification', true),
            'server_time' => now('UTC')->toIso8601ZuluString(),
        ];
    }
}
