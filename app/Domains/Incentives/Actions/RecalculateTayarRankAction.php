<?php

namespace App\Domains\Incentives\Actions;

use App\Domains\Incentives\Data\StandingWindow;
use App\Domains\Incentives\Enums\RankChangeSource;
use App\Domains\Incentives\Events\TayarRankAtRisk;
use App\Domains\Incentives\Events\TayarRankChanged;
use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Incentives\Models\TayarRankHistory;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\RaterRole;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Tayars\Actions\UpdateTayarStandingAction;
use App\Domains\Tayars\Models\TayarProfile;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RecalculateTayarRankAction
{
    public function __construct(private UpdateTayarStandingAction $updateStanding) {}

    /**
     * Works out one approved tayar's delivered orders and revealed business ratings over the tayari.ranks.window_days
     * whole business days before today (StandingWindow), and the highest rank they meet (the lowest rank at least). A
     * tayar without a rank gets it at once; moving up happens at once; moving down waits until the business day
     * TayarRank::dropsAt() gives, then drops to the rank they meet. Every change writes a history row. Safe to run again
     * on the same day.
     */
    public function handle(TayarProfile $profile, CarbonInterface $now): TayarProfile
    {
        $now = CarbonImmutable::instance($now);
        $ladder = TayarRank::ladder();

        return DB::transaction(function () use ($profile, $now, $ladder): TayarProfile {
            $profile = TayarProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if (! $profile->isApproved() || $ladder->isEmpty()) {
                return $profile;
            }

            $window = StandingWindow::before($now);
            $delivered = Order::query()
                ->where('tayar_id', $profile->user_id)
                ->where('status', OrderStatus::Delivered)
                ->where('delivered_at', '>=', $window->since)
                ->where('delivered_at', '<', $window->until)
                ->count();
            $rating = OrderRating::averageFor($profile->user_id, RaterRole::Business, $window->since, $window->until);

            $earned = $ladder->filter(fn (TayarRank $rank): bool => $rank->isMetBy($delivered, $rating['average']))->last() ?? $ladder->first();
            $current = $ladder->firstWhere('id', $profile->rank_id);
            $belowSince = $profile->rank_below_since?->toImmutable();
            $next = $current;

            if ($current === null || $earned->level > $current->level) {
                $next = $earned;
                $belowSince = null;
            } elseif ($earned->level === $current->level) {
                $belowSince = null;
            } elseif ($belowSince === null) {
                $belowSince = $now;
                TayarRankAtRisk::dispatch($profile, $current, TayarRank::dropsAt($now));
            } elseif (TayarRank::dropsAt($belowSince)->lte($now)) {
                $next = $earned;
                $belowSince = null;
            }

            $this->updateStanding->handle($profile, [
                'rank_id' => $next->id,
                'rank_below_since' => $belowSince,
                'rating_avg' => $rating['average'],
                'ratings_count_30d' => $rating['count'],
                'delivered_orders_30d' => $delivered,
                'standing_updated_at' => $now,
            ]);

            if ($next->id !== $current?->id) {
                TayarRankHistory::query()->create([
                    'tayar_profile_id' => $profile->id,
                    'from_rank_id' => $current?->id,
                    'to_rank_id' => $next->id,
                    'source' => RankChangeSource::Auto,
                ]);

                if ($current !== null) {
                    TayarRankChanged::dispatch($profile, $current, $next);
                }
            }

            return $profile;
        });
    }
}
