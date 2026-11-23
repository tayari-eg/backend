<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Database\Eloquent\Builder;

class SetStaleTayarsOfflineAction
{
    public function __construct(private GoOfflineAction $goOffline) {}

    /**
     * Sets offline every online tayar with no location for the configured minutes, except tayars holding an accepted,
     * picked-up or ongoing order (the sender sees how old the last position is). Returns how many went offline.
     */
    public function handle(): int
    {
        $staleBefore = now()->subMinutes(config('tayari.tayars.offline_after_minutes'));
        $count = 0;

        TayarProfile::query()
            ->where('is_online', true)
            ->where(fn (Builder $query) => $query->whereNull('location_updated_at')->orWhere('location_updated_at', '<', $staleBefore))
            ->chunkById(100, function ($profiles) use ($staleBefore, &$count): void {
                foreach ($profiles as $profile) {
                    $count += (int) $this->goOffline->handle($profile, $staleBefore);
                }
            });

        return $count;
    }
}
