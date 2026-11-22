<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Enums\ReviewDecision;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Enums\VehicleStatus;
use App\Domains\Tayars\Events\TayarApplicationRejected;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectTayarApplicationAction
{
    /**
     * Rejects the application and its vehicle with a reason the applicant sees, and records the review. The applicant
     * may change the application again after the configured wait.
     *
     * @throws ValidationException when the application is not pending
     */
    public function handle(TayarProfile $profile, User $reviewer, string $reason): TayarProfile
    {
        $profile = DB::transaction(function () use ($profile, $reviewer, $reason): TayarProfile {
            $profile = TayarProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if (! $profile->isPending()) {
                throw ValidationException::withMessages(['status' => __('tayars.not_pending')]);
            }

            $profile->forceFill([
                'status' => TayarStatus::Rejected,
                'rejection_reason' => $reason,
                'can_reapply_at' => now()->addHours(config('tayari.tayars.reapply_after_hours')),
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $profile->vehicles()->update(['status' => VehicleStatus::Rejected, 'is_active' => false]);

            $profile->reviews()->create(['decision' => ReviewDecision::Rejected, 'reason' => $reason, 'reviewed_by' => $reviewer->id]);

            return $profile;
        });

        TayarApplicationRejected::dispatch($profile);

        return $profile;
    }
}
