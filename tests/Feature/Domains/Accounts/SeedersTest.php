<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessType;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_permissions_are_seeded_on_the_web_guard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(['admin', 'ops', 'super_admin', 'support'], Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all());
        $this->assertSame($this->permissionNames(StaffRole::Admin), $this->rolePermissions('admin'));
        $this->assertNotContains(StaffPermission::StaffManage->value, $this->rolePermissions('admin'));
        $this->assertSame(['business_profiles.review', 'business_profiles.view', 'business_types.view', 'national_ids.review', 'national_ids.view', 'users.block', 'users.view'], $this->rolePermissions('ops'));
        $this->assertSame(['business_profiles.view', 'business_types.view', 'national_ids.view', 'users.view'], $this->rolePermissions('support'));
        $this->assertSame([], $this->rolePermissions('super_admin'));
    }

    public function test_running_the_seeder_again_adds_new_permissions_to_an_existing_database(): void
    {
        // The database as the first release seeded it: four permissions, and support with only the two view ones.
        foreach (['business_profiles.view', 'business_profiles.review', 'national_ids.view', 'national_ids.review'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('support', 'web')->syncPermissions(['business_profiles.view', 'national_ids.view']);
        $support = User::factory()->create()->assignRole('support');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(count(StaffPermission::cases()), Permission::query()->where('guard_name', 'web')->count());
        $this->assertSame($this->permissionNames(StaffRole::Support), $this->rolePermissions('support'));
        $this->assertSame($this->permissionNames(StaffRole::Ops), $this->rolePermissions('ops'));
        $this->assertTrue($support->refresh()->hasRole('support'));
        $this->assertTrue($support->can(StaffPermission::UsersView->value));
    }

    /**
     * @return list<string>
     */
    private function rolePermissions(string $role): array
    {
        return Role::findByName($role, 'web')->permissions->pluck('name')->sort()->values()->all();
    }

    /**
     * @return list<string>
     */
    private function permissionNames(StaffRole $role): array
    {
        return collect($role->permissions())->map(fn (StaffPermission $permission): string => $permission->value)->sort()->values()->all();
    }

    public function test_the_super_admin_comes_from_env(): void
    {
        config(['tayari.super_admin' => ['name' => 'Tayari Owner', 'phone' => '010 1234 5678', 'password' => 'owner-secret']]);
        $this->seed([RolesAndPermissionsSeeder::class, SuperAdminSeeder::class]);
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->sole();
        $this->assertSame('+201012345678', $user->phone);
        $this->assertTrue(Hash::check('owner-secret', $user->password));
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue($user->hasRole(StaffRole::SuperAdmin->value));
    }

    public function test_the_super_admin_is_skipped_without_env_values(): void
    {
        config(['tayari.super_admin' => ['name' => null, 'phone' => null, 'password' => null]]);

        $this->seed([RolesAndPermissionsSeeder::class, SuperAdminSeeder::class]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_business_types_are_seeded_once_in_order(): void
    {
        $this->seed(BusinessTypeSeeder::class);
        $this->seed(BusinessTypeSeeder::class);

        $types = BusinessType::query()->orderBy('sort_order')->get();
        $this->assertCount(10, $types);
        $this->assertSame('مطعم', $types->first()->getTranslation('name', 'ar'));
        $this->assertSame('Restaurant', $types->first()->getTranslation('name', 'en'));
        $this->assertSame('Other', $types->last()->getTranslation('name', 'en'));
        $this->assertTrue($types->every(fn (BusinessType $type): bool => $type->is_active));
    }

    public function test_the_database_seeder_runs_all_three(): void
    {
        config(['tayari.super_admin' => ['name' => 'Owner', 'phone' => '01012345678', 'password' => 'owner-secret']]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', count(StaffRole::cases()));
        $this->assertDatabaseCount('business_types', 10);
        $this->assertTrue(User::query()->sole()->hasRole(StaffRole::SuperAdmin->value));
    }

    public function test_an_existing_non_staff_account_is_never_promoted(): void
    {
        $customer = User::factory()->create(['phone' => '+201012345678', 'name' => 'A Customer']);
        config(['tayari.super_admin' => ['name' => 'Tayari Owner', 'phone' => '01012345678', 'password' => 'owner-secret']]);

        $this->seed([RolesAndPermissionsSeeder::class, SuperAdminSeeder::class]);

        $customer->refresh();
        $this->assertFalse($customer->isStaff());
        $this->assertSame('A Customer', $customer->name);
        $this->assertTrue(Hash::check('password', $customer->password));
    }

    public function test_an_existing_staff_account_is_promoted(): void
    {
        $admin = $this->createStaffUser();
        config(['tayari.super_admin' => ['name' => 'Tayari Owner', 'phone' => $admin->phone, 'password' => 'owner-secret']]);

        $this->seed(SuperAdminSeeder::class);

        $this->assertTrue($admin->refresh()->hasRole(StaffRole::SuperAdmin->value));
    }
}
