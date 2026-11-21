<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Events\BusinessProfileSubmitted;
use App\Domains\Accounts\Models\BusinessProfile;
use Illuminate\Http\UploadedFile;

class ReplaceBusinessDocumentAction
{
    /**
     * Any document change sends an approved or rejected profile back to pending. The file is stored first, outside
     * any DB transaction, so a failed upload leaves the status unchanged.
     */
    public function handle(BusinessProfile $profile, DocumentCollection $collection, UploadedFile $file): BusinessProfile
    {
        $profile->addMedia($file)->toMediaCollection($collection->value);

        $returnedToPending = $profile->returnToPendingReview();
        $profile->save();

        if ($returnedToPending) {
            BusinessProfileSubmitted::dispatch($profile);
        }

        return $profile;
    }
}
