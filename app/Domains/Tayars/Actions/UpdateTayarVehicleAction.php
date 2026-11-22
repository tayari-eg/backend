<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Events\TayarApplicationSubmitted;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTayarVehicleAction
{
    public function __construct(private GoOfflineAction $goOffline) {}

    /**
     * Changing the type or the plate sends an approved or rejected application back to pending (the tayar goes offline).
     * Decided from the locked profile and vehicle rows (profile first, then vehicle), so a review or another edit that
     * lands at the same moment is never overwritten.
     *
     * @param  array{vehicle_type_id?: int, plate_number?: ?string, model?: ?string, color?: ?string, year?: ?int}  $attributes
     *
     * @throws ValidationException while a rejected applicant still has to wait, or when another vehicle took the plate
     */
    public function handle(Vehicle $vehicle, array $attributes): Vehicle
    {
        try {
            [$profile, $returnedToPending] = DB::transaction(function () use ($vehicle, $attributes): array {
                $profile = TayarProfile::query()->lockForUpdate()->findOrFail($vehicle->tayar_profile_id);
                $lockedVehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicle->id);
                $profile->ensureCanChangeApplication();

                $lockedVehicle->fill($attributes);
                $returnedToPending = $lockedVehicle->isDirty(Vehicle::REVIEWED_ATTRIBUTES) && $profile->returnToPendingReview();
                $lockedVehicle->save();
                $profile->save();

                if ($returnedToPending) {
                    $this->goOffline->handle($profile);
                }

                return [$profile, $returnedToPending];
            });
        } catch (UniqueConstraintViolationException) {
            // The request rule passed before another vehicle saved the same plate.
            throw ValidationException::withMessages([
                'plate_number' => __('validation.unique', ['attribute' => __('validation.attributes.plate_number')]),
            ]);
        }

        if ($returnedToPending) {
            TayarApplicationSubmitted::dispatch($profile);
        }

        return $vehicle->refresh();
    }
}
