<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Events\BusinessProfileSubmitted;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Models\User;
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
     * @throws Throwable when a document cannot be stored; the profile and the documents already stored are removed
     */
    public function handle(User $user, array $attributes, array $documents, array $links): BusinessProfile
    {
        $profile = DB::transaction(function () use ($user, $attributes, $links): BusinessProfile {
            $profile = $user->businessProfile()->create($attributes);
            $profile->links()->createMany($links);

            return $profile;
        });

        // Files are never written inside a DB transaction: a rollback can't remove them.
        $storedMedia = [];

        try {
            foreach ($documents as $collection => $file) {
                $storedMedia[] = $profile->addMedia($file)->toMediaCollection($collection);
            }
        } catch (Throwable $exception) {
            foreach ($storedMedia as $media) {
                $media->delete();
            }

            $profile->delete();

            throw $exception;
        }

        BusinessProfileSubmitted::dispatch($profile);

        return $profile;
    }
}
