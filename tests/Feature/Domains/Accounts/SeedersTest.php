<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessType;
use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Tayars\Models\VehicleType;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ModeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Database\Seeders\VehicleTypeSeeder;
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
        $this->assertSame(['areas.view', 'business_profiles.review', 'business_profiles.view', 'business_types.view', 'categories.view', 'modes.view', 'national_ids.review', 'national_ids.view', 'pricing_rules.view', 'tayars.review', 'tayars.view', 'users.block', 'users.view', 'vehicle_types.view'], $this->rolePermissions('ops'));
        $this->assertSame(['areas.view', 'business_profiles.view', 'business_types.view', 'categories.view', 'modes.view', 'national_ids.view', 'pricing_rules.view', 'tayars.view', 'users.view', 'vehicle_types.view'], $this->rolePermissions('support'));
        $this->assertContains(StaffPermission::PricingRulesManage->value, $this->rolePermissions('admin'));
        $this->assertContains(StaffPermission::TayarsReview->value, $this->rolePermissions('admin'));
        $this->assertContains(StaffPermission::VehicleTypesManage->value, $this->rolePermissions('admin'));
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

    public function test_vehicle_types_are_seeded_once_in_order_and_only_the_bicycle_needs_no_licence(): void
    {
        $this->seed(VehicleTypeSeeder::class);
        $this->seed(VehicleTypeSeeder::class);

        $types = VehicleType::query()->orderBy('sort_order')->get();
        $this->assertSame(['Car', 'Motorcycle', 'Scooter', 'Bicycle'], $types->map(fn (VehicleType $type): string => $type->getTranslation('name', 'en'))->all());
        $this->assertSame([true, true, true, false], $types->pluck('requires_license')->all());
        $this->assertTrue($types->every(fn (VehicleType $type): bool => $type->can_carry_food && $type->is_active));
    }

    public function test_the_three_modes_are_seeded_once_in_order_and_only_long_distance_refuses_food(): void
    {
        $this->seed(ModeSeeder::class);
        $this->seed(ModeSeeder::class);

        $modes = Mode::query()->orderBy('sort_order')->get();
        $this->assertSame([ModeCode::LongDistance, ModeCode::Zone, ModeCode::Area], $modes->pluck('code')->all());
        $this->assertSame([false, true, true], $modes->pluck('allows_food')->all());
        $this->assertSame('منطقة', $modes->last()->getTranslation('name', 'ar'));
        $this->assertTrue($modes->every(fn (Mode $mode): bool => $mode->is_active && $mode->getTranslation('description', 'en') !== ''));
    }

    public function test_running_the_mode_seeder_again_keeps_what_staff_changed(): void
    {
        $this->seed(ModeSeeder::class);
        $zone = Mode::query()->where('code', ModeCode::Zone)->sole();
        $zone->update(['name' => ['ar' => 'قريب', 'en' => 'Nearby'], 'is_active' => false]);

        $this->seed(ModeSeeder::class);

        $this->assertSame('Nearby', $zone->refresh()->getTranslation('name', 'en'));
        $this->assertFalse($zone->is_active);
        $this->assertDatabaseCount('modes', 3);
    }

    public function test_the_database_seeder_runs_all_five(): void
    {
        config(['tayari.super_admin' => ['name' => 'Owner', 'phone' => '01012345678', 'password' => 'owner-secret']]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', count(StaffRole::cases()));
        $this->assertDatabaseCount('business_types', 10);
        $this->assertDatabaseCount('vehicle_types', 4);
        $this->assertDatabaseCount('modes', 3);
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
