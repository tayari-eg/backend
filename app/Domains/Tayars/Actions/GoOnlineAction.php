<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Accounts\Enums\AppMode;
use App\Domains\Tayars\Data\WorkSessionSettings;
use App\Domains\Tayars\Enums\VehicleStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoOnlineAction
{
    /**
     * Starts a work session in the picked mode at the given position. Already online with the same settings keeps the
     * session and only moves the position; different settings end it and start a new one.
     *
     * @throws AuthorizationException when the application is not approved, or the app is not in tayar mode
     * @throws ValidationException when the tayar has no approved, active vehicle
     */
    public function handle(TayarProfile $profile, float $lat, float $lng, WorkSessionSettings $settings): TayarProfile
    {
        return DB::transaction(function () use ($profile, $lat, $lng, $settings): TayarProfile {
            $profile = TayarProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if (! $profile->isApproved()) {
                throw new AuthorizationException(__('tayars.not_approved'));
            }

            // Read under the profile lock that SwitchAppModeAction also takes, so a switch to client mode can't interleave.
            if (User::query()->whereKey($profile->user_id)->value('last_mode') !== AppMode::Tayar) {
                throw new AuthorizationException(__('tayars.switch_to_tayar_mode'));
            }

            $position = ['current_lat' => $lat, 'current_lng' => $lng, 'location_updated_at' => now()];
            $current = $profile->is_online ? TayarWorkSession::query()->find($profile->current_work_session_id) : null;

            if ($current !== null && $current->ended_at === null && $settings->matches($current)) {
                $profile->forceFill($position)->save();

                return $profile;
            }

            $vehicle = $profile->vehicles()
                ->where('is_active', true)
                ->where('status', VehicleStatus::Approved)
                ->first();

            if ($vehicle === null) {
                throw ValidationException::withMessages(['vehicle' => __('tayars.no_active_vehicle')]);
            }

            if ($current !== null) {
                TayarWorkSession::query()->whereKey($current->id)->whereNull('ended_at')->update(['ended_at' => now()]);
            }

            $session = $profile->workSessions()->create([
                ...$settings->toAttributes(),
                'vehicle_id' => $vehicle->id,
                'started_at' => now(),
            ]);

            $profile->forceFill([...$position, 'is_online' => true, 'current_work_session_id' => $session->id])->save();

            return $profile;
        });
    }
}
