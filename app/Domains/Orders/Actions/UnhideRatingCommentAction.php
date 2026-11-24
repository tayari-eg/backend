<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Models\OrderRating;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UnhideRatingCommentAction
{
    /**
     * Shows a hidden comment to the rated party again and clears who hid it and why.
     *
     * @throws ValidationException (errors.rating) when the comment is not hidden
     */
    public function handle(OrderRating $rating): OrderRating
    {
        return DB::transaction(function () use ($rating): OrderRating {
            $rating = OrderRating::query()->lockForUpdate()->findOrFail($rating->id);

            if (! $rating->isCommentHidden()) {
                throw ValidationException::withMessages(['rating' => __('admin.rating_comment_not_hidden')]);
            }

            $rating->forceFill(['comment_hidden_at' => null, 'comment_hidden_by' => null, 'comment_hidden_reason' => null])->save();

            return $rating;
        });
    }
}
