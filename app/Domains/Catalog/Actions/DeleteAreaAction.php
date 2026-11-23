<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Area;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteAreaAction
{
    /**
     * SQLSTATE codes for a foreign key violation on MySQL (23000) and PostgreSQL (23503).
     */
    private const array FOREIGN_KEY_VIOLATIONS = ['23000', '23503'];

    /**
     * @throws ValidationException when a work session used the area
     */
    public function handle(Area $area): void
    {
        try {
            DB::transaction(function () use ($area): void {
                $area = Area::query()->lockForUpdate()->findOrFail($area->id);

                if ($area->workSessions()->exists()) {
                    throw $this->inUse();
                }

                $area->delete();
            });
        } catch (QueryException $exception) {
            // A tayar going online in this area at the same moment still holds the foreign key.
            if (! in_array((string) $exception->getCode(), self::FOREIGN_KEY_VIOLATIONS, true)) {
                throw $exception;
            }

            throw $this->inUse();
        }
    }

    private function inUse(): ValidationException
    {
        return ValidationException::withMessages(['area' => __('admin.area_in_use')]);
    }
}
