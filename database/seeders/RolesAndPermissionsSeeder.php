<?php

namespace Database\Seeders;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Enums\StaffRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    private const string GUARD = 'web';

    /**
     * Idempotent: safe to run again after adding a role or permission.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (StaffPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, self::GUARD);
        }

        foreach (StaffRole::cases() as $staffRole) {
            Role::findOrCreate($staffRole->value, self::GUARD)
                ->syncPermissions(array_map(fn (StaffPermission $permission): string => $permission->value, $staffRole->permissions()));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
