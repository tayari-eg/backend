<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Events\NationalIdVerified;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifyNationalIdAction
{
    /**
     * @throws ValidationException when the national ID is not waiting for review
     */
    public function handle(User $user, User $reviewer): User
    {
        $user = DB::transaction(function () use ($user, $reviewer): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->nationalIdStatus() !== VerificationStatus::Pending) {
                throw ValidationException::withMessages(['status' => __('business.national_id_not_pending')]);
            }

            $user->forceFill([
                'id_verified_at' => now(),
                'id_rejection_reason' => null,
                'id_reviewed_by' => $reviewer->id,
                'id_reviewed_at' => now(),
            ])->save();

            return $user;
        });

        NationalIdVerified::dispatch($user);

        return $user;
    }
}
