<?php

namespace App\Domains\Accounts\Policies;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BusinessProfilePolicy
{
    public function view(User $user, BusinessProfile $businessProfile): bool
    {
        return $businessProfile->user_id === $user->id || $user->can(StaffPermission::BusinessProfilesView->value);
    }

    public function create(User $user): Response
    {
        if (! $user->isBusiness()) {
            return Response::deny(__('business.business_account_only'));
        }

        if ($user->businessProfile()->exists()) {
            return Response::deny(__('business.profile_exists'));
        }

        return Response::allow();
    }

    public function update(User $user, BusinessProfile $businessProfile): bool
    {
        return $businessProfile->user_id === $user->id;
    }

    public function review(User $user, BusinessProfile $businessProfile): bool
    {
        return $businessProfile->user_id !== $user->id && $user->can(StaffPermission::BusinessProfilesReview->value);
    }
}
