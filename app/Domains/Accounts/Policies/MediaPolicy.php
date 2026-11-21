<?php

namespace App\Domains\Accounts\Policies;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Models\BusinessProfile;
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
            default => false,
        };
    }
}
