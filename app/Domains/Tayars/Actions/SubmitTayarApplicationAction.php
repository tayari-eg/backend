<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Enums\VehicleDocument;
use App\Domains\Tayars\Events\TayarApplicationSubmitted;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubmitTayarApplicationAction
{
    /**
     * @param  array{vehicle_type_id: int, plate_number?: ?string, model?: ?string, color?: ?string, year?: ?int}  $vehicleAttributes
     * @param  array<string, UploadedFile>  $documents  keyed by TayarDocument value
     * @param  array{vehicle_license?: ?UploadedFile, vehicle_photos: list<UploadedFile>}  $vehicleDocuments
     *
     * @throws AuthorizationException when the user already applied (also for a double submit)
     * @throws ValidationException when a parallel application took the national ID number or the plate number
     * @throws Throwable when a document cannot be stored; the application and the files already stored are removed
     */
    public function handle(User $user, string $nationalIdNumber, array $vehicleAttributes, array $documents, array $vehicleDocuments): TayarProfile
    {
        try {
            [$profile, $vehicle] = DB::transaction(function () use ($user, $nationalIdNumber, $vehicleAttributes): array {
                $profile = $user->tayarProfile()->create(['national_id_number' => $nationalIdNumber]);

                return [$profile, $profile->vehicles()->create($vehicleAttributes)];
            });
        } catch (UniqueConstraintViolationException) {
            if ($user->tayarProfile()->exists()) {
                throw new AuthorizationException(__('tayars.application_exists'));
            }

            $plateNumber = $vehicleAttributes['plate_number'] ?? null;

            if ($plateNumber !== null && Vehicle::query()->where('plate_number', $plateNumber)->exists()) {
                throw ValidationException::withMessages([
                    'plate_number' => __('validation.unique', ['attribute' => __('validation.attributes.plate_number')]),
                ]);
            }

            throw ValidationException::withMessages(['national_id_number' => __('tayars.national_id_number_taken')]);
        }

        // Files are never written inside a DB transaction: a rollback can't remove them.
        try {
            foreach ($documents as $collection => $file) {
                $profile->addMedia($file)->toMediaCollection($collection);
            }

            if (($vehicleDocuments['vehicle_license'] ?? null) !== null) {
                $vehicle->addMedia($vehicleDocuments['vehicle_license'])->toMediaCollection(VehicleDocument::VehicleLicense->value);
            }

            foreach ($vehicleDocuments['vehicle_photos'] as $photo) {
                $vehicle->addMedia($photo)->toMediaCollection(VehicleDocument::VehiclePhotos->value);
            }
        } catch (Throwable $exception) {
            // Deleting the models also deletes the media already stored for them.
            $vehicle->delete();
            $profile->delete();

            throw $exception;
        }

        TayarApplicationSubmitted::dispatch($profile);

        return $profile;
    }
}
