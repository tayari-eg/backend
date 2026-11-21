<?php

namespace App\Domains\Accounts\Policies;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Models\User;

class UserPolicy
{
    public function submitNationalId(User $user, User $model): bool
    {
        return $user->is($model);
    }

    public function reviewNationalId(User $user, User $model): bool
    {
        return ! $user->is($model) && $user->can(StaffPermission::NationalIdsReview->value);
    }
}
