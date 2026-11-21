<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Stream a private document. Only reachable through the short signed URL in API responses.
     *
     * @unauthenticated
     */
    public function __invoke(Request $request, Media $media): StreamedResponse
    {
        return $media->toInlineResponse($request);
    }
}
