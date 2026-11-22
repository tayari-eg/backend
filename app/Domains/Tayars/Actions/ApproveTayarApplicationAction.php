<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Enums\ReviewDecision;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Enums\VehicleStatus;
use App\Domains\Tayars\Events\TayarApplicationApproved;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveTayarApplicationAction
{
    /**
     * Approves the application with its vehicle, which becomes the active one, and records the review.
     *
     * @throws ValidationException when the application is not pending
     */
    public function handle(TayarProfile $profile, User $reviewer): TayarProfile
    {
        $profile = DB::transaction(function () use ($profile, $reviewer): TayarProfile {
            $profile = TayarProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if (! $profile->isPending()) {
                throw ValidationException::withMessages(['status' => __('tayars.not_pending')]);
            }

            $profile->forceFill([
                'status' => TayarStatus::Approved,
                'rejection_reason' => null,
                'can_reapply_at' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $profile->vehicles()->update(['status' => VehicleStatus::Approved]);
            $profile->vehicle?->forceFill(['is_active' => true])->save();

            $profile->reviews()->create(['decision' => ReviewDecision::Approved, 'reviewed_by' => $reviewer->id]);

            return $profile;
        });

        TayarApplicationApproved::dispatch($profile);

        return $profile;
    }
}
