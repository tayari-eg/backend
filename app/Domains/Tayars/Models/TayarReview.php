<?php

namespace App\Domains\Tayars\Models;

use App\Domains\Tayars\Enums\ReviewDecision;
use App\Models\User;
use Database\Factories\TayarReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One approval or rejection, kept so a re-application still shows the earlier reasons.
 */
#[Fillable(['decision', 'reason', 'reviewed_by'])]
#[UseFactory(TayarReviewFactory::class)]
class TayarReview extends Model
{
    /** @use HasFactory<TayarReviewFactory> */
    use HasFactory;

    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => ReviewDecision::class,
        ];
    }

    /**
     * @return BelongsTo<TayarProfile, $this>
     */
    public function tayarProfile(): BelongsTo
    {
        return $this->belongsTo(TayarProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
