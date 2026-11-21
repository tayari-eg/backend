<?php

namespace App\Domains\Accounts\Enums;

enum StaffRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Ops = 'ops';
    case Support = 'support';

    /**
     * @return list<StaffPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            // Super admins pass every check through Gate::before, so they need no direct permissions.
            self::SuperAdmin => [],
            self::Admin, self::Ops => StaffPermission::cases(),
            self::Support => [StaffPermission::BusinessProfilesView, StaffPermission::NationalIdsView],
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
