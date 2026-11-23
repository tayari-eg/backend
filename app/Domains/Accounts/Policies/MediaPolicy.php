<?php

namespace App\Domains\Accounts\Policies;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Orders\Models\Order;
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
            (new Order)->getMorphClass() => $user->can(StaffPermission::OrdersView->value) || $this->isOrderParty($user, $media),
            default => false,
        };
    }

    /**
     * The sender or the tayar of the photo's order. Checked on the order the resource already loaded when there is
     * one, so a list of orders doesn't run a query per photo.
     */
    private function isOrderParty(User $user, Media $media): bool
    {
        if ($media->relationLoaded('model') && $media->model instanceof Order) {
            return $media->model->sender_id === $user->id || $media->model->tayar_id === $user->id;
        }

        return Order::query()
            ->whereKey($media->model_id)
            ->where(fn ($orders) => $orders->where('sender_id', $user->id)->orWhere('tayar_id', $user->id))
            ->exists();
    }
}
