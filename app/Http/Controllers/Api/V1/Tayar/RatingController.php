<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Domains\Orders\Enums\RaterRole;
use App\Domains\Orders\Models\OrderRating;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tayar\ListRatingsRequest;
use App\Http\Resources\Api\V1\Tayar\ReceivedRatingResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RatingController extends Controller
{
    /**
     * The ratings businesses gave me, newest first, once each rating window has ended. Cursor pages, sorted by id only,
     * so no time is in the order or the cursor.
     */
    public function index(ListRatingsRequest $request): AnonymousResourceCollection
    {
        $ratings = OrderRating::query()
            ->where('rated_user_id', $request->user()->id)
            ->where('rater_role', RaterRole::Business)
            ->where('revealed_at', '<=', now())
            ->orderByDesc('id')
            ->cursorPaginate($request->perPage());

        return ReceivedRatingResource::collection($ratings);
    }
}
