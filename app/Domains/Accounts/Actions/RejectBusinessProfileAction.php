<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Events\BusinessProfileRejected;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectBusinessProfileAction
{
    /**
     * @throws ValidationException when the profile is not pending
     */
    public function handle(BusinessProfile $profile, User $reviewer, string $reason): BusinessProfile
    {
        $profile = DB::transaction(function () use ($profile, $reviewer, $reason): BusinessProfile {
            $profile = BusinessProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if (! $profile->isPending()) {
                throw ValidationException::withMessages(['status' => __('business.not_pending')]);
            }

            $profile->forceFill([
                'verification_status' => VerificationStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            return $profile;
        });

        BusinessProfileRejected::dispatch($profile);

        return $profile;
    }
}
