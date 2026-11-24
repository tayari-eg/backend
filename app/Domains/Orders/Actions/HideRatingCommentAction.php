<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Models\OrderRating;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HideRatingCommentAction
{
    /**
     * Hides an abusive comment from the rated party, with the staff member and the reason. The stars still count.
     *
     * @throws ValidationException (errors.rating) when there is no comment or it is already hidden
     */
    public function handle(OrderRating $rating, User $staff, string $reason): OrderRating
    {
        return DB::transaction(function () use ($rating, $staff, $reason): OrderRating {
            $rating = OrderRating::query()->lockForUpdate()->findOrFail($rating->id);

            if ($rating->comment === null) {
                throw ValidationException::withMessages(['rating' => __('admin.rating_has_no_comment')]);
            }

            if ($rating->isCommentHidden()) {
                throw ValidationException::withMessages(['rating' => __('admin.rating_comment_already_hidden')]);
            }

            $rating->forceFill([
                'comment_hidden_at' => now(),
                'comment_hidden_by' => $staff->id,
                'comment_hidden_reason' => $reason,
            ])->save();

            return $rating;
        });
    }
}
