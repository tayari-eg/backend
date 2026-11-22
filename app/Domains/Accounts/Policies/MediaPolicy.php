<?php

namespace App\Domains\Accounts\Policies;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Decides who may receive a signed URL for a private document.
 */
class MediaPolicy
{
    public function view(User $user, Media $media): bool
    {
        return match ($media->model_type) {
            $user->getMorphClass() => (int) $media->model_id === $user->id
                || $user->can(StaffPermission::NationalIdsView->value),
            (new BusinessProfile)->getMorphClass() => $user->businessProfile?->id === (int) $media->model_id
                || $user->can(StaffPermission::BusinessProfilesView->value),
            (new TayarProfile)->getMorphClass() => $user->tayarProfile?->id === (int) $media->model_id
                || $user->can(StaffPermission::TayarsView->value),
            (new Vehicle)->getMorphClass() => $user->can(StaffPermission::TayarsView->value)
                || Vehicle::query()->whereKey($media->model_id)->where('tayar_profile_id', $user->tayarProfile?->id)->exists(),
            default => false,
        };
    }
}
