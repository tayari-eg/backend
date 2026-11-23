<?php

namespace Tests\Feature\Api\V1\Client;

use App\Domains\Accounts\Enums\RegisterAs;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Catalog\Models\Category;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderEstimateTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'en');

        config(['tayari.orders.road_distance_multiplier' => '1.00']);
        PricingRule::factory()->create(['mode_id' => $this->zoneMode()->id, 'food_extra' => '10.00']);
        $this->category = Category::factory()->childOf(Category::factory()->create())->create();
    }

    /**
     * A 4 km trip due north.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'category_id' => $this->category->id,
            'pickup_lat' => 30.0,
            'pickup_lng' => 31.2,
            'dropoff_lat' => 30.0359729,
            'dropoff_lng' => 31.2,
            ...$overrides,
        ];
    }

    public function test_an_approved_business_gets_the_price_without_creating_an_order(): void
    {
        Sanctum::actingAs(BusinessProfile::factory()->approved()->create()->user);

        $this->postJson(route('api.v1.client.orders.estimate'), $this->payload())
            ->assertOk()
            ->assertExactJson(['data' => ['distance_km' => '4.00', 'food_extra' => '0.00', 'shipping_fee' => '40.00']]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_food_subcategory_adds_the_food_extra(): void
    {
        Sanctum::actingAs(BusinessProfile::factory()->approved()->create()->user);
        $food = Category::factory()->food()->childOf(Category::factory()->food()->create())->create();

        $this->postJson(route('api.v1.client.orders.estimate'), $this->payload(['category_id' => $food->id]))
            ->assertOk()
            ->assertJsonPath('data.food_extra', '10.00')
            ->assertJsonPath('data.shipping_fee', '50.00');
    }

    public function test_only_an_active_subcategory_of_an_active_main_category_is_accepted(): void
    {
        Sanctum::actingAs(BusinessProfile::factory()->approved()->create()->user);
        $main = Category::factory()->create();
        $inactiveParent = Category::factory()->inactive()->create();

        foreach ([$main->id, Category::factory()->inactive()->childOf($main)->create()->id, Category::factory()->childOf($inactiveParent)->create()->id, 999999] as $categoryId) {
            $this->postJson(route('api.v1.client.orders.estimate'), $this->payload(['category_id' => $categoryId]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['category_id']);
        }
    }

    public function test_coordinates_are_required_and_bounded(): void
    {
        Sanctum::actingAs(BusinessProfile::factory()->approved()->create()->user);

        $this->postJson(route('api.v1.client.orders.estimate'), ['pickup_lat' => 91, 'pickup_lng' => 181, 'dropoff_lat' => 30.12345678])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'pickup_lat', 'pickup_lng', 'dropoff_lat', 'dropoff_lng']);
    }

    public function test_a_route_no_mode_serves_is_a_route_error(): void
    {
        Sanctum::actingAs(BusinessProfile::factory()->approved()->create()->user);

        $this->postJson(route('api.v1.client.orders.estimate'), $this->payload(['dropoff_lat' => 30.1798643]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.route.0', __('orders.route_unavailable', locale: 'en'));
    }

    public function test_points_less_than_100_m_apart_are_a_route_error(): void
    {
        Sanctum::actingAs(BusinessProfile::factory()->approved()->create()->user);

        $this->postJson(route('api.v1.client.orders.estimate'), $this->payload(['dropoff_lat' => 30.0004497]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.route.0', __('orders.route_too_short', ['min' => '100'], 'en'));
    }

    public function test_pending_rejected_individual_and_tayar_accounts_are_refused(): void
    {
        $refused = [
            [BusinessProfile::factory()->create()->user, __('orders.business_not_approved', locale: 'en')],
            [BusinessProfile::factory()->rejected()->create()->user, __('orders.business_not_approved', locale: 'en')],
            [User::factory()->business()->create(), __('orders.business_not_approved', locale: 'en')],
            [User::factory()->create(), __('orders.business_account_only', locale: 'en')],
            [TayarProfile::factory()->approved()->create(['user_id' => User::factory()->registeredAs(RegisterAs::Tayar)])->user, __('orders.business_account_only', locale: 'en')],
        ];

        foreach ($refused as [$user, $message]) {
            Sanctum::actingAs($user);

            $this->postJson(route('api.v1.client.orders.estimate'), $this->payload())
                ->assertForbidden()
                ->assertJsonPath('message', $message);
        }
    }

    public function test_guests_are_refused(): void
    {
        $this->postJson(route('api.v1.client.orders.estimate'), $this->payload())->assertUnauthorized();
    }
}
