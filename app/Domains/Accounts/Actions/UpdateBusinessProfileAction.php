<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Events\BusinessProfileSubmitted;
use App\Domains\Accounts\Models\BusinessProfile;
use Illuminate\Support\Facades\DB;

class UpdateBusinessProfileAction
{
    /**
     * Only a change to a reviewed attribute sends an approved or rejected profile back to pending.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array{type: string, url: string}>|null  $links  null keeps the current links
     */
    public function handle(BusinessProfile $profile, array $attributes, ?array $links): BusinessProfile
    {
        $returnedToPending = DB::transaction(function () use ($profile, $attributes, $links): bool {
            $profile->fill($attributes);

            $returnedToPending = $profile->isDirty(BusinessProfile::REVIEWED_ATTRIBUTES) && $profile->returnToPendingReview();

            $profile->save();

            if ($links !== null) {
                $profile->links()->delete();
                $profile->links()->createMany($links);
            }

            return $returnedToPending;
        });

        if ($returnedToPending) {
            BusinessProfileSubmitted::dispatch($profile);
        }

        return $profile;
    }
}
