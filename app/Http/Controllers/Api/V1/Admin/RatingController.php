<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Orders\Actions\HideRatingCommentAction;
use App\Domains\Orders\Actions\UnhideRatingCommentAction;
use App\Domains\Orders\Models\OrderRating;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\HideRatingCommentRequest;
use App\Http\Requests\Api\V1\Admin\ListRatingsRequest;
use App\Http\Resources\Api\V1\Admin\OrderRatingResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RatingController extends Controller
{
    /**
     * @var list<string>
     */
    private const array RELATIONS = ['order', 'rater', 'ratedUser', 'commentHider'];

    /**
     * Every rating in both directions, newest first, with filters.
     */
    public function index(ListRatingsRequest $request): AnonymousResourceCollection
    {
        $ratings = OrderRating::query()
            ->with(self::RELATIONS)
            ->when($request->raterRole(), fn ($query, $role) => $query->where('rater_role', $role))
            ->when($request->integerFilter('rated_user_id'), fn ($query, int $userId) => $query->where('rated_user_id', $userId))
            ->when($request->integerFilter('rater_id'), fn ($query, int $userId) => $query->where('rater_id', $userId))
            ->when($request->integerFilter('order_id'), fn ($query, int $orderId) => $query->where('order_id', $orderId))
            ->when($request->integerFilter('stars'), fn ($query, int $stars) => $query->where('stars', $stars))
            ->when($request->booleanFilter('has_comment') !== null, fn ($query) => $request->booleanFilter('has_comment')
                ? $query->whereNotNull('comment')
                : $query->whereNull('comment'))
            ->when($request->booleanFilter('comment_hidden') !== null, fn ($query) => $request->booleanFilter('comment_hidden')
                ? $query->whereNotNull('comment_hidden_at')
                : $query->whereNull('comment_hidden_at'))
            ->when($request->createdFrom(), fn ($query, $from) => $query->where('created_at', '>=', $from))
            ->when($request->createdTo(), fn ($query, $to) => $query->where('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return OrderRatingResource::collection($ratings);
    }

    /**
     * One rating with both parties and the full comment.
     */
    public function show(OrderRating $rating): OrderRatingResource
    {
        return OrderRatingResource::make($rating->load(self::RELATIONS));
    }

    /**
     * Hide an abusive comment from the rated party, with a reason. The stars still count.
     */
    public function hideComment(HideRatingCommentRequest $request, OrderRating $rating, HideRatingCommentAction $hideComment): OrderRatingResource
    {
        return OrderRatingResource::make($hideComment->handle($rating, $request->user(), $request->validated('reason'))->load(self::RELATIONS));
    }

    /**
     * Show a hidden comment to the rated party again.
     */
    public function unhideComment(OrderRating $rating, UnhideRatingCommentAction $unhideComment): OrderRatingResource
    {
        return OrderRatingResource::make($unhideComment->handle($rating)->load(self::RELATIONS));
    }
}
