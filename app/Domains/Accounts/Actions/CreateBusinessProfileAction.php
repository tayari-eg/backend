<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Events\BusinessProfileSubmitted;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateBusinessProfileAction
{
    /**
     * @param  array{business_type_id: int, business_name: string, description?: ?string, avg_daily_orders?: ?int, tax_registration_number?: ?string}  $attributes
     * @param  array<string, UploadedFile>  $documents  keyed by DocumentCollection value
     * @param  list<array{type: string, url: string}>  $links
     *
     * @throws AuthorizationException when a parallel request already created the profile (a double submit)
     * @throws Throwable when a document cannot be stored; the profile and the documents already stored are removed
     */
    public function handle(User $user, array $attributes, array $documents, array $links): BusinessProfile
    {
        try {
            $profile = DB::transaction(function () use ($user, $attributes, $links): BusinessProfile {
                $profile = $user->businessProfile()->create($attributes);
                $profile->links()->createMany($links);

                return $profile;
            });
        } catch (UniqueConstraintViolationException) {
            // Same answer as the policy gives a second, sequential create.
            throw new AuthorizationException(__('business.profile_exists'));
        }

        // Files are never written inside a DB transaction: a rollback can't remove them.
        try {
            foreach ($documents as $collection => $file) {
                $profile->addMedia($file)->toMediaCollection($collection);
            }
        } catch (Throwable $exception) {
            // Deleting the profile also deletes the media already stored for it.
            $profile->delete();

            throw $exception;
        }

        BusinessProfileSubmitted::dispatch($profile);

        return $profile;
    }
}
