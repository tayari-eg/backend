<?php

namespace App\Http\Resources\Api\V1\Tayar;

use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The tayar's rank, 30-day numbers and the full ladder, as of the last nightly run. Before the first run the tayar
 * shows at the lowest rank.
 *
 * @mixin TayarProfile
 */
class RankStandingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ladder = TayarRank::ladder();
        $current = $ladder->firstWhere('id', $this->rank_id) ?? $ladder->first();
        $next = $current === null ? null : $ladder->first(fn (TayarRank $rank): bool => $rank->level > $current->level);

        return [
            'rank' => $current === null ? null : self::rank($current),
            'next_rank' => $next === null ? null : self::rank($next),
            'delivered_orders_30d' => $this->delivered_orders_30d,
            'ratings_count_30d' => $this->ratings_count_30d,
            // Null until min_ratings_for_average ratings are in.
            'rating_avg' => $this->rating_avg,
            'min_ratings_for_average' => config()->integer('tayari.ratings.min_ratings_for_average'),
            // While below the rank's needs: when that started and when the rank drops unless they are met again.
            'at_risk' => $this->rank_below_since === null ? null : [
                'since' => $this->rank_below_since->toIso8601ZuluString(),
                'drops_at' => TayarRank::dropsAt($this->rank_below_since)->toIso8601ZuluString(),
            ],
            'ranks' => $ladder->map(fn (TayarRank $rank): array => self::rank($rank))->values()->all(),
            'updated_at' => $this->standing_updated_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * @return array{code: string, name: string, level: int, min_delivered_orders_30d: int, min_rating: ?string, color: string}
     */
    private static function rank(TayarRank $rank): array
    {
        return [
            'code' => $rank->code,
            'name' => $rank->getTranslation('name', app()->getLocale()),
            'level' => $rank->level,
            'min_delivered_orders_30d' => $rank->min_delivered_orders_30d,
            'min_rating' => $rank->min_rating,
            'color' => $rank->color,
        ];
    }
}
