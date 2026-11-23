<?php

namespace App\Domains\Tayars\Actions;

use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteVehicleTypeAction
{
    /**
     * SQLSTATE codes for a foreign key violation on MySQL (23000) and PostgreSQL (23503).
     */
    private const array FOREIGN_KEY_VIOLATIONS = ['23000', '23503'];

    /**
     * @throws ValidationException when a vehicle or a pricing rule uses the type
     */
    public function handle(VehicleType $vehicleType): void
    {
        try {
            DB::transaction(function () use ($vehicleType): void {
                $vehicleType = VehicleType::query()->lockForUpdate()->findOrFail($vehicleType->id);

                if ($vehicleType->vehicles()->exists()) {
                    throw $this->inUse();
                }

                if ($vehicleType->pricingRules()->exists()) {
                    throw ValidationException::withMessages(['vehicle_type' => __('admin.vehicle_type_has_pricing_rules')]);
                }

                $vehicleType->delete();
            });
        } catch (QueryException $exception) {
            // A vehicle or pricing rule created at the same moment still holds the foreign key.
            if (! in_array((string) $exception->getCode(), self::FOREIGN_KEY_VIOLATIONS, true)) {
                throw $exception;
            }

            throw $this->inUse();
        }
    }

    private function inUse(): ValidationException
    {
        return ValidationException::withMessages(['vehicle_type' => __('admin.vehicle_type_in_use')]);
    }
}
