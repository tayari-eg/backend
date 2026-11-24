<?php

namespace Tests\Feature\Domains\Accounts;

use App\Domains\Accounts\Enums\AppMode;
use App\Domains\Accounts\Enums\RegisterAs;
use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\BusinessType;
use App\Domains\Catalog\Enums\ModeCode;
use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Incentives\Models\TayarRankHistory;
use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderReasonType;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\RaterRole;
use App\Domains\Orders\Enums\ShippingPaidBy;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Orders\Models\OrderReason;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Enums\VehicleStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\VehicleType;
use App\Domains\Wallet\Enums\SystemWallet;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Domains\Wallet\Models\WalletPosting;
use App\Models\User;
use Brick\Math\BigDecimal;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\ModeSeeder;
use Database\Seeders\OrderReasonSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Database\Seeders\SystemWalletSeeder;
use Database\Seeders\TayarRankSeeder;
use Database\Seeders\VehicleTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        $this->assertSame(['areas.view', 'business_profiles.review', 'business_profiles.view', 'business_types.view', 'categories.view', 'modes.view', 'national_ids.review', 'national_ids.view', 'orders.manage', 'orders.view', 'pricing_rules.view', 'ranks.manage', 'ranks.view', 'ratings.moderate', 'ratings.view', 'tayars.review', 'tayars.view', 'users.block', 'users.view', 'vehicle_types.view', 'wallets.settle', 'wallets.view'], $this->rolePermissions('ops'));
        $this->assertSame(['areas.view', 'business_profiles.view', 'business_types.view', 'categories.view', 'modes.view', 'national_ids.view', 'orders.view', 'pricing_rules.view', 'ranks.view', 'ratings.view', 'tayars.view', 'users.view', 'vehicle_types.view', 'wallets.view'], $this->rolePermissions('support'));
        $this->assertContains(StaffPermission::PricingRulesManage->value, $this->rolePermissions('admin'));
        $this->assertContains(StaffPermission::OrdersManage->value, $this->rolePermissions('admin'));
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

    public function test_the_cancel_and_not_delivered_reasons_are_seeded_once_in_order_and_keep_what_staff_changed(): void
    {
        $this->seed(OrderReasonSeeder::class);
        OrderReason::query()->ofType(OrderReasonType::Cancel)->orderBy('sort_order')->first()->update(['is_active' => false]);

        $this->seed(OrderReasonSeeder::class);

        foreach ([OrderReasonType::Cancel, OrderReasonType::NotDelivered] as $type) {
            $reasons = OrderReason::query()->ofType($type)->orderBy('sort_order')->get();
            $this->assertCount(5, $reasons, $type->value);
            $this->assertSame('Other', $reasons->last()->getTranslation('name', 'en'));
        }

        $this->assertFalse(OrderReason::query()->ofType(OrderReasonType::Cancel)->orderBy('sort_order')->first()->is_active);
        $this->assertTrue(OrderReason::query()->ofType(OrderReasonType::NotDelivered)->where('name->en', 'The receiver can\'t be reached')->exists());
    }

    public function test_the_system_wallets_are_seeded_once_and_a_second_run_changes_nothing(): void
    {
        $this->seed(SystemWalletSeeder::class);
        Wallet::query()->where('system_code', SystemWallet::Treasury)->update(['balance' => '-50.00']);
        $before = Wallet::query()->orderBy('id')->get()->toArray();

        $this->seed(SystemWalletSeeder::class);

        $this->assertSame(array_map(fn (SystemWallet $code): string => $code->value, SystemWallet::cases()), Wallet::query()->orderBy('id')->pluck('system_code')->map(fn (SystemWallet $code): string => $code->value)->all());
        $this->assertSame($before, Wallet::query()->orderBy('id')->get()->toArray());
        $this->assertSame([WalletType::System], Wallet::query()->distinct()->pluck('type')->all());
    }

    public function test_the_four_tayar_ranks_are_seeded_once_in_order_and_keep_what_staff_changed(): void
    {
        $this->seed(TayarRankSeeder::class);
        TayarRank::query()->where('code', 'captain')->update(['min_delivered_orders_30d' => 70]);

        $this->seed(TayarRankSeeder::class);

        $ranks = TayarRank::query()->orderBy('level')->get();
        $this->assertSame(['trainee', 'tayar', 'captain', 'senior_captain'], $ranks->pluck('code')->all());
        $this->assertSame(['متدرب', 'طيار', 'كابتن', 'كابتن أول'], $ranks->map(fn (TayarRank $rank): string => $rank->getTranslation('name', 'ar'))->all());
        $this->assertSame([0, 30, 70, 150], $ranks->pluck('min_delivered_orders_30d')->all());
        $this->assertSame([null, '4.30', '4.60', '4.80'], $ranks->pluck('min_rating')->all());
    }

    public function test_the_database_seeder_runs_all_nine(): void
    {
        config(['tayari.super_admin' => ['name' => 'Owner', 'phone' => '01012345678', 'password' => 'owner-secret']]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('roles', count(StaffRole::cases()));
        $this->assertDatabaseCount('business_types', 10);
        $this->assertDatabaseCount('vehicle_types', 4);
        $this->assertDatabaseCount('modes', 3);
        $this->assertDatabaseCount('order_reasons', 10);
        $this->assertSame(count(SystemWallet::cases()), Wallet::query()->where('type', WalletType::System)->count());
        $this->assertSame(['Correction', 'Compensation', 'Penalty', 'Other'], WalletAdjustmentReason::query()->orderBy('sort_order')->get()->map(fn (WalletAdjustmentReason $reason): string => $reason->getTranslation('name', 'en'))->all());
        $this->assertDatabaseCount('tayar_ranks', 4);
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

    public function test_demo_data_refuses_to_run_without_a_password_and_creates_nothing(): void
    {
        config(['tayari.demo.users_password' => '']);
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('areas', 0);
        $this->assertDatabaseCount('pricing_rules', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_demo_data_creates_the_catalog_and_accounts_that_look_like_real_data(): void
    {
        config(['tayari.demo.users_password' => 'demo-secret']);
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('roles', count(StaffRole::cases()));
        $this->assertDatabaseCount('modes', 3);
        $this->assertSame(11, Category::query()->count());
        // Every main category needs an active subcategory to show in GET /api/v1/categories.
        $this->assertTrue(Category::query()->main()->withCount('children')->get()->every(fn (Category $category): bool => $category->children_count > 0));
        $this->assertFalse(Category::query()->whereNameIs('en', 'Medicines')->sole()->is_food);
        $this->assertSame(['Food', 'Documents', 'Parcels', 'Pharmacy'], Category::query()->main()->orderBy('sort_order')->get()->map(fn (Category $category): string => $category->getTranslation('name', 'en'))->all());
        $this->assertTrue(Category::query()->where('parent_id', Category::query()->main()->whereNameIs('en', 'Food')->value('id'))->get()->every(fn (Category $category): bool => $category->is_food));
        $this->assertFalse(Category::query()->main()->whereNameIs('en', 'Pharmacy')->sole()->is_food);
        $this->assertSame(8, Area::query()->count());
        $this->assertSame('الجيزة', Area::query()->whereNameIs('en', 'Sheikh Zayed')->sole()->getTranslation('city', 'ar'));
        $this->assertSame(4, PricingRule::query()->count());
        $this->assertSame(1, PricingRule::query()->whereNotNull('vehicle_type_id')->count());
        $this->assertNull(PricingRule::query()->whereHas('mode', fn ($query) => $query->where('code', ModeCode::LongDistance))->sole()->food_extra);

        $this->assertDatabaseCount('users', 11);
        $this->assertSame([
            '+201000000001' => 'ready',
            '+201000000002' => 'ready',
            '+201000000003' => 'ready',
            '+201000000004' => 'ready',
            '+201000000005' => 'business_under_review',
            '+201000000006' => 'business_rejected',
            '+201000000007' => 'ready',
            '+201000000008' => 'tayar_under_review',
            '+201000000009' => 'tayar_rejected',
            '+201000000010' => 'complete_business_profile',
            '+201000000011' => 'complete_tayar_application',
        ], User::query()->with(['businessProfile', 'tayarProfile'])->orderBy('phone')->get()->mapWithKeys(fn (User $user): array => [$user->phone => $user->onboardingStep()->value])->all());
        $businessNew = User::query()->where('phone', '+201000000010')->sole();
        $this->assertSame([RegisterAs::Business, 'business'], [$businessNew->register_as, $businessNew->account_type->value]);
        $tayarNew = User::query()->where('phone', '+201000000011')->sole();
        $this->assertSame([RegisterAs::Tayar, 'individual'], [$tayarNew->register_as, $tayarNew->account_type->value]);
        $this->assertTrue(User::query()->get()->every(fn (User $user): bool => $user->hasVerifiedPhone() && Hash::check('demo-secret', $user->password)));
        $this->assertTrue(User::query()->where('phone', '+201000000001')->sole()->hasRole(StaffRole::Admin->value));
        $this->assertTrue(User::query()->where('phone', '+201000000002')->sole()->hasRole(StaffRole::Ops->value));
        $this->assertTrue(User::query()->where('phone', '+201000000003')->sole()->hasRole(StaffRole::Support->value));
        $admin = User::query()->where('phone', '+201000000001')->sole();

        $businesses = BusinessProfile::query()->with('user')->orderBy('id')->get();
        $this->assertSame([VerificationStatus::Approved, VerificationStatus::Pending, VerificationStatus::Rejected], $businesses->pluck('verification_status')->all());
        $this->assertTrue($businesses->every(fn (BusinessProfile $profile): bool => $profile->user->register_as === RegisterAs::Business && $profile->user->account_type->value === 'business'));
        $this->assertSame($admin->id, $businesses[0]->reviewed_by);
        $this->assertNotNull($businesses[2]->rejection_reason);
        $this->assertNull($businesses[1]->reviewed_at);

        $tayars = TayarProfile::query()->with(['user', 'vehicle'])->orderBy('id')->get();
        $this->assertSame([TayarStatus::Approved, TayarStatus::Pending, TayarStatus::Rejected], $tayars->pluck('status')->all());
        $this->assertSame([VehicleStatus::Approved, VehicleStatus::Pending, VehicleStatus::Rejected], $tayars->map(fn (TayarProfile $profile): VehicleStatus => $profile->vehicle->status)->all());
        $this->assertTrue($tayars[0]->vehicle->is_active);
        $this->assertSame(AppMode::Tayar, $tayars[0]->user->last_mode);
        $this->assertSame($admin->id, $tayars[0]->reviewed_by);
        $this->assertNotNull($tayars[2]->can_reapply_at);
        $this->assertNotNull($tayars[2]->rejection_reason);
        $this->assertDatabaseCount('tayar_reviews', 2);

        // Businesses: 4 + 2 + 2 documents; tayars: 5 + 1 + 2, 4 + 2 (a bicycle has no licences), 5 + 1 + 2; 1 pickup photo.
        $this->assertDatabaseCount('media', 31);
        $this->assertSame(['local'], DB::table('media')->distinct()->pluck('disk')->all());

        // The approved business has an order looking for a tayar (area mode, offered to the online demo tayar), a
        // cancelled food order (zone mode, nobody to offer it to), an order the demo tayar accepted (area mode) and an
        // order the demo tayar delivered (area mode). Then come the rated history orders.
        $allOrders = Order::query()->with(['mode', 'statusHistories', 'cancelReason', 'offers', 'media'])->orderBy('id')->get();
        $orders = $allOrders->take(4)->values();
        $history = $allOrders->slice(4)->values();
        $this->assertSame(array_fill(0, 4, $businesses[0]->user_id), $orders->pluck('sender_id')->all());
        $this->assertSame([OrderStatus::LookingForTayar, OrderStatus::Cancelled, OrderStatus::Ongoing, OrderStatus::Delivered], $orders->pluck('status')->all());
        $this->assertSame([ModeCode::Area, ModeCode::Zone, ModeCode::Area, ModeCode::Area], $orders->map(fn (Order $order): ModeCode => $order->mode->code)->all());
        $this->assertSame(['0.00', '10.00', '0.00', '0.00'], $orders->pluck('food_extra')->all());
        $this->assertSame(ShippingPaidBy::Receiver, $orders[0]->shipping_paid_by);
        $this->assertSame([1, 2, 4, 5], $orders->map(fn (Order $order): int => $order->statusHistories->count())->all());
        // Picked up, then on the way: the receiver's codes went out (hashes only) and the photo is stored.
        $this->assertNotNull($orders[2]->delivery_code_hash);
        $this->assertNotNull($orders[2]->codes_sent_at);
        $this->assertCount(1, $orders[2]->getMedia(Order::PICKUP_PHOTOS));
        $this->assertSame('I no longer need the order', $orders[1]->cancelReason->getTranslation('name', 'en'));

        $demoTayar = $tayars[0];
        $this->assertTrue($demoTayar->is_online);
        $this->assertSame(ModeCode::Area, $demoTayar->currentWorkSession()->with('mode')->sole()->mode->code);
        // Withdrawn once the demo tayar accepted order 3 and reached the area mode's limit.
        $this->assertSame([[$demoTayar->user_id, OfferStatus::Withdrawn]], $orders[0]->offers->map(fn (OrderOffer $offer): array => [$offer->tayar_id, $offer->status])->all());
        $this->assertSame([], $orders[1]->offers->all());
        $this->assertSame([[$demoTayar->user_id, OfferStatus::Accepted]], $orders[2]->offers->map(fn (OrderOffer $offer): array => [$offer->tayar_id, $offer->status])->all());
        $this->assertSame([$demoTayar->user_id, $demoTayar->current_work_session_id], [$orders[2]->tayar_id, $orders[2]->work_session_id]);
        $this->assertSame([], $orders[3]->offers->all());
        $this->assertSame($demoTayar->user_id, $orders[3]->tayar_id);

        // Wallets: the signup bonus, order 4's delivery (cash, shipping paid by the receiver), EGP 500 cash handed in
        // and a EGP 300 payout to the business, then the history deliveries; every balance adds up to zero.
        $this->assertSame(['signup_bonus', 'order_delivered', 'cash_handed_in', 'payout', ...array_fill(0, 30, 'order_delivered')], WalletPosting::query()->orderBy('id')->pluck('type')->map->value->all());
        $tayarBalance = BigDecimal::of('100.00')->plus((string) $orders[3]->tayar_earning)->minus((string) $orders[3]->amount_to_collect)->plus('500.00');
        foreach ($history as $order) {
            $tayarBalance = $tayarBalance->plus((string) $order->tayar_earning)->minus((string) $order->amount_to_collect);
        }
        $this->assertSame((string) $tayarBalance->toScale(2), Wallet::ensureFor($demoTayar->user_id, WalletType::Tayar)->balance);
        $this->assertSame('500.00', Wallet::ensureFor($businesses[0]->user_id, WalletType::Business)->balance);
        $this->assertSame('0.00', (string) BigDecimal::of((string) Wallet::query()->sum('balance'))->toScale(2));

        // Ratings: 30 past orders delivered by the demo tayar over the last weeks, rated both ways and revealed; order 4
        // is rated too but still in its window. One business comment is hidden by staff. The demo tayar is ranked as the
        // nightly job would: 30 orders delivered before today and a 4.60 average reach the second rank, from the starting
        // rank given on approval.
        $this->assertCount(30, $history);
        $this->assertTrue($history->every(fn (Order $order): bool => $order->status === OrderStatus::Delivered && $order->tayar_id === $demoTayar->user_id && $order->delivered_at->lt(now()->subDays(2))));
        $this->assertDatabaseCount('order_ratings', 62);
        $this->assertSame(2, OrderRating::query()->where('order_id', $orders[3]->id)->where('revealed_at', '>', now())->count());
        $hidden = OrderRating::query()->whereNotNull('comment_hidden_at')->sole();
        $this->assertSame([$admin->id, RaterRole::Business], [$hidden->comment_hidden_by, $hidden->rater_role]);
        $demoTayar->refresh()->load('rank');
        $this->assertSame(['tayar', 'طيار', '4.60', 30, 30], [$demoTayar->rank->code, $demoTayar->rank->getTranslation('name', 'ar'), $demoTayar->rating_avg, $demoTayar->ratings_count_30d, $demoTayar->delivered_orders_30d]);
        $this->assertSame([['trainee', null], ['tayar', 'trainee']], TayarRankHistory::query()->with(['toRank', 'fromRank'])->orderBy('id')->get()->map(fn (TayarRankHistory $row): array => [$row->toRank->code, $row->fromRank?->code])->all());
        $this->assertSame('4.73', $businesses[0]->refresh()->rating_avg);
    }

    public function test_running_the_demo_data_seeder_twice_creates_nothing_twice(): void
    {
        config(['tayari.demo.users_password' => 'demo-secret']);
        Storage::fake('local');

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('users', 11);
        $this->assertDatabaseCount('categories', 11);
        $this->assertDatabaseCount('areas', 8);
        $this->assertDatabaseCount('pricing_rules', 4);
        $this->assertDatabaseCount('business_profiles', 3);
        $this->assertDatabaseCount('tayar_profiles', 3);
        $this->assertDatabaseCount('vehicles', 3);
        $this->assertDatabaseCount('tayar_reviews', 2);
        $this->assertDatabaseCount('media', 31);
        $this->assertDatabaseCount('modes', 3);
        $this->assertDatabaseCount('vehicle_types', 4);
        $this->assertDatabaseCount('business_types', 10);
        $this->assertDatabaseCount('order_reasons', 10);
        $this->assertDatabaseCount('orders', 34);
        $this->assertDatabaseCount('order_status_histories', 162);
        $this->assertDatabaseCount('wallet_postings', 34);
        $this->assertDatabaseCount('order_ratings', 62);
        $this->assertDatabaseCount('tayar_rank_histories', 2);
        $this->assertDatabaseCount('wallet_adjustment_reasons', 4);
        $this->assertDatabaseCount('order_offers', 2);
        $this->assertDatabaseCount('tayar_work_sessions', 1);
    }

    public function test_a_second_demo_run_writes_no_placeholder_files_for_existing_applications(): void
    {
        config(['tayari.demo.users_password' => 'demo-secret']);
        Storage::fake('local');
        $seeder = new class extends DemoDataSeeder
        {
            public int $placeholderFiles = 0;

            protected function placeholderFile(string $fileName): UploadedFile
            {
                $this->placeholderFiles++;

                return parent::placeholderFile($fileName);
            }
        };
        $this->app->instance(DemoDataSeeder::class, $seeder);

        $this->seed(DemoDataSeeder::class);
        $this->assertSame(31, $seeder->placeholderFiles);

        $seeder->placeholderFiles = 0;
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(0, $seeder->placeholderFiles);
        $this->assertDatabaseCount('media', 31);
    }

    public function test_demo_accounts_stop_when_the_reviewer_phone_belongs_to_a_non_staff_user(): void
    {
        config(['tayari.demo.users_password' => 'demo-secret']);
        Storage::fake('local');
        $customer = User::factory()->create(['phone' => '+201000000001']);

        $this->seed(DemoDataSeeder::class);

        $this->assertFalse($customer->refresh()->isStaff());
        $this->assertDatabaseMissing('business_profiles', ['reviewed_by' => $customer->id]);
        $this->assertDatabaseMissing('tayar_profiles', ['reviewed_by' => $customer->id]);
        $this->assertDatabaseMissing('tayar_reviews', ['reviewed_by' => $customer->id]);
        $this->assertDatabaseCount('business_profiles', 0);
        $this->assertDatabaseCount('tayar_profiles', 0);
        $this->assertDatabaseCount('media', 0);
    }

    public function test_the_database_seeder_never_creates_demo_data(): void
    {
        config(['tayari.demo.users_password' => 'demo-secret', 'tayari.super_admin' => ['name' => null, 'phone' => null, 'password' => null]]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('areas', 0);
        $this->assertDatabaseCount('pricing_rules', 0);
        $this->assertDatabaseCount('business_profiles', 0);
        $this->assertDatabaseCount('tayar_profiles', 0);
    }
}
