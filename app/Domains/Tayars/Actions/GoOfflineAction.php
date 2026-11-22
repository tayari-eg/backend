<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class GoOfflineAction
{
    /**
     * Closes the current work session. Already offline is fine. With $staleBefore, the tayar only goes offline when the
     * last location is still older than that time once the row is locked, so a location that just arrived wins.
     * Returns whether the tayar went offline.
     */
    public function handle(TayarProfile $profile, ?CarbonInterface $staleBefore = null): bool
    {
        $wentOffline = DB::transaction(function () use ($profile, $staleBefore): bool {
            $current = TayarProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if (! $current->is_online) {
                return false;
            }

            if ($staleBefore !== null && $current->location_updated_at !== null && $current->location_updated_at->gte($staleBefore)) {
                return false;
            }

            TayarWorkSession::query()
                ->whereKey($current->current_work_session_id)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            $current->forceFill(['is_online' => false, 'current_work_session_id' => null])->save();

            return true;
        });

        if ($wentOffline) {
            $profile->forceFill(['is_online' => false, 'current_work_session_id' => null])->syncOriginalAttributes(['is_online', 'current_work_session_id']);
        }

        return $wentOffline;
    }
}
