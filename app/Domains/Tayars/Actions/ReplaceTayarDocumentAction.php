<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Enums\TayarDocument;
use App\Domains\Tayars\Events\TayarApplicationSubmitted;
use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplaceTayarDocumentAction
{
    public function __construct(private GoOfflineAction $goOffline) {}

    /**
     * Any document change sends an approved or rejected application back to pending (the tayar goes offline). The file
     * is stored first, outside any DB transaction, so a failed upload leaves the status unchanged; the status is then
     * decided from the locked row, so a review that lands at the same moment is never overwritten.
     *
     * @throws ValidationException while a rejected applicant still has to wait
     */
    public function handle(TayarProfile $profile, TayarDocument $document, UploadedFile $file): TayarProfile
    {
        // A fresh read, not the caller's copy, so a rejection that just landed stops the upload.
        TayarProfile::query()->findOrFail($profile->id)->ensureCanChangeApplication();

        $profile->addMedia($file)->toMediaCollection($document->value);

        $returnedToPending = DB::transaction(function () use ($profile): bool {
            $locked = TayarProfile::query()->lockForUpdate()->findOrFail($profile->id);
            $locked->ensureCanChangeApplication();

            $returnedToPending = $locked->returnToPendingReview();
            $locked->save();

            if ($returnedToPending) {
                $this->goOffline->handle($locked);
            }

            return $returnedToPending;
        });

        $profile->refresh();

        if ($returnedToPending) {
            TayarApplicationSubmitted::dispatch($profile);
        }

        return $profile;
    }
}
