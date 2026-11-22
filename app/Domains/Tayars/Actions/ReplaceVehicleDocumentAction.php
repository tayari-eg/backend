<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Enums\VehicleDocument;
use App\Domains\Tayars\Events\TayarApplicationSubmitted;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplaceVehicleDocumentAction
{
    public function __construct(private GoOfflineAction $goOffline) {}

    /**
     * Replaces the licence, or all the photos at once, and sends an approved or rejected application back to pending
     * (the tayar goes offline). New files are stored before the old ones are removed, so a failed upload loses nothing;
     * the status is then decided from the locked profile row, so a review that lands at the same moment is never
     * overwritten.
     *
     * @param  list<UploadedFile>  $files  one file for the licence, 1 to 4 for the photos
     *
     * @throws ValidationException while a rejected applicant still has to wait
     */
    public function handle(Vehicle $vehicle, VehicleDocument $document, array $files): Vehicle
    {
        // A fresh read, not the caller's copy, so a rejection that just landed stops the upload.
        TayarProfile::query()->findOrFail($vehicle->tayar_profile_id)->ensureCanChangeApplication();

        $oldMedia = $vehicle->getMedia($document->value);

        foreach ($files as $file) {
            $vehicle->addMedia($file)->toMediaCollection($document->value);
        }

        if (! $document->isSingleFile()) {
            $oldMedia->each->delete();
        }

        [$profile, $returnedToPending] = DB::transaction(function () use ($vehicle): array {
            $profile = TayarProfile::query()->lockForUpdate()->findOrFail($vehicle->tayar_profile_id);
            $profile->ensureCanChangeApplication();

            $returnedToPending = $profile->returnToPendingReview();
            $profile->save();

            if ($returnedToPending) {
                $this->goOffline->handle($profile);
            }

            return [$profile, $returnedToPending];
        });

        if ($returnedToPending) {
            TayarApplicationSubmitted::dispatch($profile);
        }

        return $vehicle->refresh();
    }
}
