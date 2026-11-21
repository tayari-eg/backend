<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Events\BusinessProfileApproved;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveBusinessProfileAction
{
    /**
     * @throws ValidationException when the profile is not pending
     */
    public function handle(BusinessProfile $profile, User $reviewer): BusinessProfile
    {
        $profile = DB::transaction(function () use ($profile, $reviewer): BusinessProfile {
            $profile = BusinessProfile::query()->lockForUpdate()->findOrFail($profile->id);

            if (! $profile->isPending()) {
                throw ValidationException::withMessages(['status' => __('business.not_pending')]);
            }

            $profile->forceFill([
                'verification_status' => VerificationStatus::Approved,
                'rejection_reason' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            return $profile;
        });

        BusinessProfileApproved::dispatch($profile);

        return $profile;
    }
}
