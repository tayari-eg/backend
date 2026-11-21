<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A private document. The short signed URL is only included for users the MediaPolicy allows.
 *
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    public static function optional(?Media $media): ?self
    {
        return $media === null ? null : new self($media);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'collection' => $this->collection_name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'uploaded_at' => $this->created_at?->toIso8601ZuluString(),
            'url' => $this->when(
                $request->user()?->can('view', $this->resource) === true,
                fn (): string => URL::temporarySignedRoute(
                    'api.v1.public.media.show',
                    now()->addMinutes(config('tayari.media.signed_url_minutes')),
                    ['media' => $this->id],
                ),
            ),
        ];
    }
}
