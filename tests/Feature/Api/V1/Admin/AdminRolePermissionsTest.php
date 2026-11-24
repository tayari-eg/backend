<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\BusinessType;
use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use App\Domains\Tayars\Models\VehicleType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every admin route: the HTTP method, the route parameter it needs, and the permission it requires.
     *
     * @var array<string, array{string, ?string, StaffPermission}>
     */
    private const array ROUTES = [
        'api.v1.admin.business-profiles.index' => ['GET', null, StaffPermission::BusinessProfilesView],
        'api.v1.admin.business-profiles.show' => ['GET', 'businessProfile', StaffPermission::BusinessProfilesView],
        'api.v1.admin.business-profiles.approve' => ['POST', 'businessProfile', StaffPermission::BusinessProfilesReview],
        'api.v1.admin.business-profiles.reject' => ['POST', 'businessProfile', StaffPermission::BusinessProfilesReview],
        'api.v1.admin.national-ids.index' => ['GET', null, StaffPermission::NationalIdsView],
        'api.v1.admin.national-ids.verify' => ['POST', 'user', StaffPermission::NationalIdsReview],
        'api.v1.admin.national-ids.reject' => ['POST', 'user', StaffPermission::NationalIdsReview],
        'api.v1.admin.business-types.index' => ['GET', null, StaffPermission::BusinessTypesView],
        'api.v1.admin.business-types.show' => ['GET', 'businessType', StaffPermission::BusinessTypesView],
        'api.v1.admin.business-types.store' => ['POST', null, StaffPermission::BusinessTypesManage],
        'api.v1.admin.business-types.update' => ['PATCH', 'businessType', StaffPermission::BusinessTypesManage],
        'api.v1.admin.business-types.activate' => ['POST', 'businessType', StaffPermission::BusinessTypesManage],
        'api.v1.admin.business-types.deactivate' => ['POST', 'businessType', StaffPermission::BusinessTypesManage],
        'api.v1.admin.business-types.destroy' => ['DELETE', 'businessType', StaffPermission::BusinessTypesManage],
        'api.v1.admin.users.index' => ['GET', null, StaffPermission::UsersView],
        'api.v1.admin.users.show' => ['GET', 'customer', StaffPermission::UsersView],
        'api.v1.admin.users.block' => ['POST', 'customer', StaffPermission::UsersBlock],
        'api.v1.admin.users.unblock' => ['POST', 'customer', StaffPermission::UsersBlock],
        'api.v1.admin.tayar-applications.index' => ['GET', null, StaffPermission::TayarsView],
        'api.v1.admin.tayar-applications.show' => ['GET', 'tayarProfile', StaffPermission::TayarsView],
        'api.v1.admin.tayar-applications.approve' => ['POST', 'tayarProfile', StaffPermission::TayarsReview],
        'api.v1.admin.tayar-applications.reject' => ['POST', 'tayarProfile', StaffPermission::TayarsReview],
        'api.v1.admin.tayars.index' => ['GET', null, StaffPermission::TayarsView],
        'api.v1.admin.tayars.show' => ['GET', 'tayar', StaffPermission::TayarsView],
        'api.v1.admin.vehicle-types.index' => ['GET', null, StaffPermission::VehicleTypesView],
        'api.v1.admin.vehicle-types.show' => ['GET', 'vehicleType', StaffPermission::VehicleTypesView],
        'api.v1.admin.vehicle-types.store' => ['POST', null, StaffPermission::VehicleTypesManage],
        'api.v1.admin.vehicle-types.update' => ['PATCH', 'vehicleType', StaffPermission::VehicleTypesManage],
        'api.v1.admin.vehicle-types.activate' => ['POST', 'vehicleType', StaffPermission::VehicleTypesManage],
        'api.v1.admin.vehicle-types.deactivate' => ['POST', 'vehicleType', StaffPermission::VehicleTypesManage],
        'api.v1.admin.vehicle-types.destroy' => ['DELETE', 'vehicleType', StaffPermission::VehicleTypesManage],
        'api.v1.admin.categories.index' => ['GET', null, StaffPermission::CategoriesView],
        'api.v1.admin.categories.show' => ['GET', 'category', StaffPermission::CategoriesView],
        'api.v1.admin.categories.store' => ['POST', null, StaffPermission::CategoriesManage],
        'api.v1.admin.categories.update' => ['PATCH', 'category', StaffPermission::CategoriesManage],
        'api.v1.admin.categories.activate' => ['POST', 'category', StaffPermission::CategoriesManage],
        'api.v1.admin.categories.deactivate' => ['POST', 'category', StaffPermission::CategoriesManage],
        'api.v1.admin.categories.destroy' => ['DELETE', 'category', StaffPermission::CategoriesManage],
        'api.v1.admin.categories.icon.store' => ['POST', 'category', StaffPermission::CategoriesManage],
        'api.v1.admin.categories.icon.destroy' => ['DELETE', 'category', StaffPermission::CategoriesManage],
        'api.v1.admin.modes.index' => ['GET', null, StaffPermission::ModesView],
        'api.v1.admin.modes.show' => ['GET', 'mode', StaffPermission::ModesView],
        'api.v1.admin.modes.update' => ['PATCH', 'mode', StaffPermission::ModesManage],
        'api.v1.admin.modes.activate' => ['POST', 'mode', StaffPermission::ModesManage],
        'api.v1.admin.modes.deactivate' => ['POST', 'mode', StaffPermission::ModesManage],
        'api.v1.admin.areas.index' => ['GET', null, StaffPermission::AreasView],
        'api.v1.admin.areas.show' => ['GET', 'area', StaffPermission::AreasView],
        'api.v1.admin.areas.store' => ['POST', null, StaffPermission::AreasManage],
        'api.v1.admin.areas.update' => ['PATCH', 'area', StaffPermission::AreasManage],
        'api.v1.admin.areas.activate' => ['POST', 'area', StaffPermission::AreasManage],
        'api.v1.admin.areas.deactivate' => ['POST', 'area', StaffPermission::AreasManage],
        'api.v1.admin.areas.destroy' => ['DELETE', 'area', StaffPermission::AreasManage],
        'api.v1.admin.pricing-rules.index' => ['GET', null, StaffPermission::PricingRulesView],
        'api.v1.admin.pricing-rules.show' => ['GET', 'pricingRule', StaffPermission::PricingRulesView],
        'api.v1.admin.pricing-rules.store' => ['POST', null, StaffPermission::PricingRulesManage],
        'api.v1.admin.pricing-rules.update' => ['PATCH', 'pricingRule', StaffPermission::PricingRulesManage],
        'api.v1.admin.pricing-rules.activate' => ['POST', 'pricingRule', StaffPermission::PricingRulesManage],
        'api.v1.admin.pricing-rules.deactivate' => ['POST', 'pricingRule', StaffPermission::PricingRulesManage],
        'api.v1.admin.pricing-rules.destroy' => ['DELETE', 'pricingRule', StaffPermission::PricingRulesManage],
        'api.v1.admin.orders.index' => ['GET', null, StaffPermission::OrdersView],
        'api.v1.admin.orders.show' => ['GET', 'order', StaffPermission::OrdersView],
        'api.v1.admin.orders.cancel' => ['POST', 'order', StaffPermission::OrdersManage],
        'api.v1.admin.orders.offers.index' => ['GET', 'order', StaffPermission::OrdersView],
        'api.v1.admin.orders.not-delivered' => ['POST', 'order', StaffPermission::OrdersManage],
        'api.v1.admin.orders.resend-codes' => ['POST', 'order', StaffPermission::OrdersManage],
        'api.v1.admin.wallets.index' => ['GET', null, StaffPermission::WalletsView],
        'api.v1.admin.wallets.show' => ['GET', 'wallet', StaffPermission::WalletsView],
        'api.v1.admin.wallets.transactions' => ['GET', 'wallet', StaffPermission::WalletsView],
        'api.v1.admin.wallets.settlements.store' => ['POST', 'wallet', StaffPermission::WalletsSettle],
        'api.v1.admin.wallets.adjustments.store' => ['POST', 'wallet', StaffPermission::WalletsAdjust],
        'api.v1.admin.wallet-postings.reverse' => ['POST', 'walletPosting', StaffPermission::WalletsAdjust],
        'api.v1.admin.wallet-adjustment-reasons.index' => ['GET', null, StaffPermission::WalletsView],
        'api.v1.admin.wallet-adjustment-reasons.show' => ['GET', 'walletAdjustmentReason', StaffPermission::WalletsView],
        'api.v1.admin.wallet-adjustment-reasons.store' => ['POST', null, StaffPermission::WalletsAdjust],
        'api.v1.admin.wallet-adjustment-reasons.update' => ['PATCH', 'walletAdjustmentReason', StaffPermission::WalletsAdjust],
        'api.v1.admin.wallet-adjustment-reasons.activate' => ['POST', 'walletAdjustmentReason', StaffPermission::WalletsAdjust],
        'api.v1.admin.wallet-adjustment-reasons.deactivate' => ['POST', 'walletAdjustmentReason', StaffPermission::WalletsAdjust],
        'api.v1.admin.wallet-adjustment-reasons.destroy' => ['DELETE', 'walletAdjustmentReason', StaffPermission::WalletsAdjust],
        'api.v1.admin.ratings.index' => ['GET', null, StaffPermission::RatingsView],
        'api.v1.admin.ratings.show' => ['GET', 'rating', StaffPermission::RatingsView],
        'api.v1.admin.ratings.hide-comment' => ['POST', 'rating', StaffPermission::RatingsModerate],
        'api.v1.admin.ratings.unhide-comment' => ['POST', 'rating', StaffPermission::RatingsModerate],
        'api.v1.admin.tayar-ranks.index' => ['GET', null, StaffPermission::RanksView],
        'api.v1.admin.tayar-ranks.show' => ['GET', 'tayarRank', StaffPermission::RanksView],
        'api.v1.admin.tayar-ranks.update' => ['PATCH', 'tayarRank', StaffPermission::RanksManage],
        'api.v1.admin.tayars.rank-history' => ['GET', 'tayar', StaffPermission::RanksView],
        'api.v1.admin.staff.index' => ['GET', null, StaffPermission::StaffManage],
        'api.v1.admin.staff.store' => ['POST', null, StaffPermission::StaffManage],
        'api.v1.admin.staff.role.update' => ['PUT', 'staff', StaffPermission::StaffManage],
        'api.v1.admin.staff.block' => ['POST', 'staff', StaffPermission::StaffManage],
        'api.v1.admin.staff.unblock' => ['POST', 'staff', StaffPermission::StaffManage],
    ];

    /**
     * @return array<string, array{StaffRole, string}>
     */
    public static function rolesAndRoutes(): array
    {
        $cases = [];

        foreach (StaffRole::cases() as $role) {
            foreach (array_keys(self::ROUTES) as $routeName) {
                $cases["{$role->value} {$routeName}"] = [$role, $routeName];
            }
        }

        return $cases;
    }

    private function routeModel(?string $parameter): ?Model
    {
        return match ($parameter) {
            'businessProfile' => BusinessProfile::factory()->create(),
            'businessType' => BusinessType::factory()->create(),
            'customer', 'user' => User::factory()->create(),
            'staff' => $this->createStaffUser(StaffRole::Support),
            'tayarProfile' => TayarProfile::factory()->has(Vehicle::factory())->create(),
            'tayar' => TayarProfile::factory()->approved()->create(),
            'vehicleType' => VehicleType::factory()->create(),
            'category' => Category::factory()->create(),
            'mode' => Mode::factory()->create(),
            'area' => Area::factory()->create(),
            'pricingRule' => PricingRule::factory()->create(),
            'order' => Order::factory()->create(),
            'wallet' => Wallet::factory()->create(),
            'walletPosting' => $this->postToWallet(Wallet::factory()->create(), '10.00'),
            'walletAdjustmentReason' => WalletAdjustmentReason::factory()->create(),
            'rating' => OrderRating::factory()->create(),
            'tayarRank' => TayarRank::factory()->create(),
            null => null,
        };
    }

    #[DataProvider('rolesAndRoutes')]
    public function test_each_role_gets_exactly_its_permissions(StaffRole $role, string $routeName): void
    {
        [$method, $parameter, $permission] = self::ROUTES[$routeName];
        $model = $this->routeModel($parameter);
        Sanctum::actingAs($this->createStaffUser($role));

        $status = $this->json($method, route($routeName, $model === null ? [] : [$parameter => $model]))->getStatusCode();

        $isAllowed = $role === StaffRole::SuperAdmin || in_array($permission, $role->permissions(), true);

        if ($isAllowed) {
            $this->assertNotSame(Response::HTTP_FORBIDDEN, $status, "[{$role->value}] should be allowed on [{$routeName}].");
        } else {
            $this->assertSame(Response::HTTP_FORBIDDEN, $status, "[{$role->value}] should be forbidden on [{$routeName}].");
        }
    }

    public function test_every_admin_route_is_in_the_matrix(): void
    {
        $adminRoutes = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($route): ?string => $route->getName())
            ->filter(fn (?string $name): bool => str_starts_with((string) $name, 'api.v1.admin.'))
            ->sort()
            ->values()
            ->all();

        $this->assertSame($adminRoutes, collect(array_keys(self::ROUTES))->sort()->values()->all());
    }
}
