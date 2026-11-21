<?php

namespace Database\Seeders;

use App\Domains\Accounts\Data\PhoneNumber;
use App\Domains\Accounts\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Creates or updates the first super admin from SUPER_ADMIN_NAME, SUPER_ADMIN_PHONE and SUPER_ADMIN_PASSWORD.
     */
    public function run(): void
    {
        $name = config('tayari.super_admin.name');
        $phone = PhoneNumber::tryFrom(config('tayari.super_admin.phone'));
        $password = config('tayari.super_admin.password');

        if (blank($name) || $phone === null || blank($password)) {
            $this->command?->warn('Super admin skipped: set SUPER_ADMIN_NAME, a valid SUPER_ADMIN_PHONE and SUPER_ADMIN_PASSWORD.');

            return;
        }

        $user = User::query()->firstOrNew(['phone' => $phone->value]);

        // Never turn an existing customer or business account into a super admin by accident.
        if ($user->exists && ! $user->isStaff()) {
            $this->command?->warn("Super admin skipped: {$phone->value} already belongs to a non-staff account. Use another SUPER_ADMIN_PHONE.");

            return;
        }
        $user->fill(['name' => $name, 'password' => $password]);
        $user->forceFill(['phone_verified_at' => $user->phone_verified_at ?? now()])->save();

        $user->assignRole(StaffRole::SuperAdmin->value);
    }
}
