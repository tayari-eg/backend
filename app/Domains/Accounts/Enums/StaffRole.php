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
            self::Admin => array_values(array_filter(
                StaffPermission::cases(),
                fn (StaffPermission $permission): bool => $permission !== StaffPermission::StaffManage,
            )),
            self::Ops => [
                StaffPermission::BusinessTypesView,
                StaffPermission::UsersView,
                StaffPermission::UsersBlock,
                StaffPermission::BusinessProfilesView,
                StaffPermission::BusinessProfilesReview,
                StaffPermission::NationalIdsView,
                StaffPermission::NationalIdsReview,
            ],
            self::Support => [
                StaffPermission::BusinessTypesView,
                StaffPermission::UsersView,
                StaffPermission::BusinessProfilesView,
                StaffPermission::NationalIdsView,
            ],
        };
    }

    /**
     * Roles staff may be given through the API; super_admin only comes from the seeder.
     *
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [self::Admin, self::Ops, self::Support];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
