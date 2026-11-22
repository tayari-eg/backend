<?php

namespace App\Domains\Tayars\Policies;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TayarProfilePolicy
{
    public function view(User $user, TayarProfile $tayarProfile): bool
    {
        return $tayarProfile->user_id === $user->id || $user->can(StaffPermission::TayarsView->value);
    }

    public function create(User $user): Response
    {
        return $user->tayarProfile()->exists() ? Response::deny(__('tayars.application_exists')) : Response::allow();
    }

    public function update(User $user, TayarProfile $tayarProfile): bool
    {
        return $tayarProfile->user_id === $user->id;
    }

    public function review(User $user, TayarProfile $tayarProfile): bool
    {
        return $tayarProfile->user_id !== $user->id && $user->can(StaffPermission::TayarsReview->value);
    }
}
