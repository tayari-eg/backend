<?php

namespace App\Domains\Accounts\Actions;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\StaffRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateStaffUserAction
{
    /**
     * The phone counts as verified: a super admin vouches for it, and login needs a verified phone.
     */
    public function handle(string $name, PhoneNumber $phone, string $password, StaffRole $role): User
    {
        return DB::transaction(function () use ($name, $phone, $password, $role): User {
            $user = new User(['name' => $name, 'phone' => $phone->value, 'password' => $password]);
            $user->forceFill(['phone_verified_at' => now()])->save();

            return $user->assignRole($role->value);
        });
    }
}
