<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Events\NationalIdRejected;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectNationalIdAction
{
    /**
     * @throws ValidationException when the national ID is not waiting for review
     */
    public function handle(User $user, User $reviewer, string $reason): User
    {
        $user = DB::transaction(function () use ($user, $reviewer, $reason): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->nationalIdStatus() !== VerificationStatus::Pending) {
                throw ValidationException::withMessages(['status' => __('business.national_id_not_pending')]);
            }

            $user->forceFill([
                'id_verified_at' => null,
                'id_rejection_reason' => $reason,
                'id_reviewed_by' => $reviewer->id,
                'id_reviewed_at' => now(),
            ])->save();

            return $user;
        });

        NationalIdRejected::dispatch($user);

        return $user;
    }
}
