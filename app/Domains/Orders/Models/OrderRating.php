<?php

namespace App\Domains\Orders\Models;

use App\Domains\Orders\Enums\RaterRole;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Database\Factories\OrderRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One party's rating of the other on a delivered order. It can be changed until revealed_at (the end of the rating
 * window); from then on the rated party sees it and it counts toward their average.
 */
#[UseFactory(OrderRatingFactory::class)]
class OrderRating extends Model
{
    /** @use HasFactory<OrderRatingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'rater_id' => 'integer',
            'rated_user_id' => 'integer',
            'rater_role' => RaterRole::class,
            'stars' => 'integer',
            'comment_hidden_at' => 'datetime',
            'comment_hidden_by' => 'integer',
            'revealed_at' => 'datetime',
        ];
    }

    public function isRevealed(CarbonInterface $now): bool
    {
        return $this->revealed_at->lte($now);
    }

    public function isCommentHidden(): bool
    {
        return $this->comment_hidden_at !== null;
    }

    /**
     * The comment the rated party may read: none while staff hide it.
     */
    public function visibleComment(): ?string
    {
        return $this->isCommentHidden() ? null : $this->comment;
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function ratedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function commentHider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comment_hidden_by');
    }

    /**
     * How many ratings the user received from that side, revealed in [$since, $until), and their average with two
     * decimals: null below tayari.ratings.min_ratings_for_average ratings.
     *
     * @return array{count: int, average: ?string}
     */
    public static function averageFor(int $ratedUserId, RaterRole $raterRole, CarbonInterface $since, CarbonInterface $until): array
    {
        $totals = self::query()
            ->revealedFor($ratedUserId, $raterRole, $since, $until)
            ->toBase()
            ->selectRaw('count(*) as ratings_count, coalesce(sum(stars), 0) as stars_sum')
            ->first();

        $count = (int) $totals->ratings_count;

        if ($count === 0 || $count < config()->integer('tayari.ratings.min_ratings_for_average')) {
            return ['count' => $count, 'average' => null];
        }

        return ['count' => $count, 'average' => (string) BigDecimal::of((string) $totals->stars_sum)->dividedBy($count, 2, RoundingMode::HalfUp)];
    }

    /**
     * The ratings a user received from the given side, revealed in [$since, $until).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function revealedFor(Builder $query, int $ratedUserId, RaterRole $raterRole, CarbonInterface $since, CarbonInterface $until): void
    {
        $query->where('rated_user_id', $ratedUserId)
            ->where('rater_role', $raterRole)
            ->where('revealed_at', '>=', $since)
            ->where('revealed_at', '<', $until);
    }
}
