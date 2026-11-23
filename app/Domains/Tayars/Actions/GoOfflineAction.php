<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoOfflineAction
{
    /**
     * Closes the current work session. Already offline is fine. With $staleBefore, the tayar only goes offline when the
     * last location is still older than that time once the row is locked, so a location that just arrived wins, and a
     * tayar holding an accepted, picked-up or ongoing order is left online. With $refuseWhileHoldingOrders (the tayar's
     * own offline and switching to client mode), holding such an order is refused, even when the tayar is already
     * offline. Returns whether the tayar went offline.
     *
     * @throws ValidationException (errors.status) when refused because the tayar holds orders
     */
    public function handle(TayarProfile $profile, ?CarbonInterface $staleBefore = null, bool $refuseWhileHoldingOrders = false): bool
    {
        $wentOffline = DB::transaction(function () use ($profile, $staleBefore, $refuseWhileHoldingOrders): bool {
            $current = TayarProfile::query()->lockForUpdate()->findOrFail($profile->id);

            // Checked before "already offline": a tayar set offline by a review reset can still hold orders.
            if ($refuseWhileHoldingOrders && $current->holdsActiveOrders()) {
                throw ValidationException::withMessages(['status' => __('tayars.finish_orders_first')]);
            }

            if (! $current->is_online) {
                return false;
            }

            if ($staleBefore !== null && $current->location_updated_at !== null && $current->location_updated_at->gte($staleBefore)) {
                return false;
            }

            if ($staleBefore !== null && $current->holdsActiveOrders()) {
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
