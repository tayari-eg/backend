<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Events\TayarApplicationSubmitted;
use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTayarApplicationAction
{
    public function __construct(private GoOfflineAction $goOffline) {}

    /**
     * Changing a reviewed attribute sends an approved or rejected application back to pending (the tayar goes offline).
     * Decided from the locked row, so a review that lands at the same moment is never overwritten.
     *
     * @param  array{national_id_number?: string}  $attributes
     *
     * @throws ValidationException while a rejected applicant still has to wait, or when another application took the number
     */
    public function handle(TayarProfile $profile, array $attributes): TayarProfile
    {
        try {
            $returnedToPending = DB::transaction(function () use ($profile, $attributes): bool {
                $locked = TayarProfile::query()->lockForUpdate()->findOrFail($profile->id);
                $locked->ensureCanChangeApplication();

                $locked->fill($attributes);
                $returnedToPending = $locked->isDirty(TayarProfile::REVIEWED_ATTRIBUTES) && $locked->returnToPendingReview();
                $locked->save();

                if ($returnedToPending) {
                    $this->goOffline->handle($locked);
                }

                return $returnedToPending;
            });
        } catch (UniqueConstraintViolationException) {
            // The request rule passed before another application saved the same national ID number.
            throw ValidationException::withMessages(['national_id_number' => __('tayars.national_id_number_taken')]);
        }

        $profile->refresh();

        if ($returnedToPending) {
            TayarApplicationSubmitted::dispatch($profile);
        }

        return $profile;
    }
}
