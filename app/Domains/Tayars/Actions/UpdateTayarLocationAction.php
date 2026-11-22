<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Validation\ValidationException;

class UpdateTayarLocationAction
{
    /**
     * Stores the current position only while online. One conditional update, so it can't race the offline job.
     *
     * @throws ValidationException when the tayar is offline
     */
    public function handle(TayarProfile $profile, float $lat, float $lng): TayarProfile
    {
        $position = ['current_lat' => $lat, 'current_lng' => $lng, 'location_updated_at' => now()];

        $online = TayarProfile::query()->whereKey($profile->id)->where('is_online', true);

        // MySQL counts changed rows, not matched ones: the same point twice in one second updates 0 rows while online.
        if ($online->clone()->update($position) === 0 && ! $online->exists()) {
            throw ValidationException::withMessages(['status' => __('tayars.not_online')]);
        }

        return $profile->forceFill($position)->syncOriginalAttributes(array_keys($position));
    }
}
