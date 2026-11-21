<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Models\BusinessType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteBusinessTypeAction
{
    /**
     * SQLSTATE codes for a foreign key violation on MySQL (23000) and PostgreSQL (23503).
     */
    private const array FOREIGN_KEY_VIOLATIONS = ['23000', '23503'];

    /**
     * @throws ValidationException when a business profile uses the type
     */
    public function handle(BusinessType $businessType): void
    {
        try {
            DB::transaction(function () use ($businessType): void {
                $businessType = BusinessType::query()->lockForUpdate()->findOrFail($businessType->id);

                if ($businessType->businessProfiles()->exists()) {
                    throw $this->inUse();
                }

                $businessType->delete();
            });
        } catch (QueryException $exception) {
            // A profile created at the same moment still holds the foreign key.
            if (! in_array((string) $exception->getCode(), self::FOREIGN_KEY_VIOLATIONS, true)) {
                throw $exception;
            }

            throw $this->inUse();
        }
    }

    private function inUse(): ValidationException
    {
        return ValidationException::withMessages(['business_type' => __('admin.business_type_in_use')]);
    }
}
