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
        $this->assertCount(count(StaffPermission::cases()), Role::findByName('admin', 'web')->permissions);
        $this->assertSame(['business_profiles.view', 'national_ids.view'], Role::findByName('support', 'web')->permissions->pluck('name')->sort()->values()->all());
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
