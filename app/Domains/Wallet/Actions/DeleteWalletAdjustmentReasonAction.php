<?php

namespace App\Domains\Wallet\Actions;

use App\Domains\Wallet\Models\WalletAdjustmentReason;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteWalletAdjustmentReasonAction
{
    /**
     * SQLSTATE codes for a foreign key violation on MySQL (23000) and PostgreSQL (23503).
     */
    private const array FOREIGN_KEY_VIOLATIONS = ['23000', '23503'];

    /**
     * @throws ValidationException when a posting uses the reason
     */
    public function handle(WalletAdjustmentReason $reason): void
    {
        try {
            DB::transaction(function () use ($reason): void {
                $reason = WalletAdjustmentReason::query()->lockForUpdate()->findOrFail($reason->id);

                if ($reason->postings()->exists()) {
                    throw $this->inUse();
                }

                $reason->delete();
            });
        } catch (QueryException $exception) {
            // A posting made at the same moment still holds the foreign key.
            if (! in_array((string) $exception->getCode(), self::FOREIGN_KEY_VIOLATIONS, true)) {
                throw $exception;
            }

            throw $this->inUse();
        }
    }

    private function inUse(): ValidationException
    {
        return ValidationException::withMessages(['wallet_adjustment_reason' => __('admin.wallet_adjustment_reason_in_use')]);
    }
}
