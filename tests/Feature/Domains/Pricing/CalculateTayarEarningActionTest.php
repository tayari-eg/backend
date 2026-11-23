<?php

namespace Tests\Feature\Domains\Pricing;

use App\Domains\Orders\Models\Order;
use App\Domains\Pricing\Actions\CalculateTayarEarningAction;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateTayarEarningActionTest extends TestCase
{
    use RefreshDatabase;

    private VehicleType $bicycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bicycle = VehicleType::factory()->withoutLicense()->create();
    }

    /**
     * @return array{tayar_share_percent: string, tayar_earning: string, platform_fee: string}
     */
    private function earning(Order $order): array
    {
        return app(CalculateTayarEarningAction::class)->handle($order, $this->bicycle->id)->toAttributes();
    }

    public function test_the_mode_rule_for_the_vehicle_type_wins_over_the_all_vehicles_rule(): void
    {
        $order = Order::factory()->create();
        PricingRule::factory()->create(['mode_id' => $order->mode_id, 'tayar_share_percent' => '70.00']);
        PricingRule::factory()->forVehicleType($this->bicycle)->create(['mode_id' => $order->mode_id, 'tayar_share_percent' => '85.00']);

        $this->assertSame(['tayar_share_percent' => '85.00', 'tayar_earning' => '34.00', 'platform_fee' => '6.00'], $this->earning($order));
    }

    public function test_without_an_active_vehicle_rule_the_all_vehicles_rule_is_used(): void
    {
        $order = Order::factory()->create();
        PricingRule::factory()->create(['mode_id' => $order->mode_id, 'tayar_share_percent' => '70.00']);
        PricingRule::factory()->forVehicleType($this->bicycle)->inactive()->create(['mode_id' => $order->mode_id, 'tayar_share_percent' => '85.00']);
        PricingRule::factory()->forVehicleType()->create(['mode_id' => $order->mode_id, 'tayar_share_percent' => '95.00']);

        $this->assertSame(['tayar_share_percent' => '70.00', 'tayar_earning' => '28.00', 'platform_fee' => '12.00'], $this->earning($order));
    }

    public function test_without_any_active_rule_the_share_the_order_was_priced_with_stays(): void
    {
        $order = Order::factory()->create();

        $this->assertSame(['tayar_share_percent' => '80.00', 'tayar_earning' => '32.00', 'platform_fee' => '8.00'], $this->earning($order));
    }

    public function test_the_earning_rounds_half_up_and_the_platform_fee_takes_the_rest(): void
    {
        $order = Order::factory()->create(['shipping_fee' => '10.05']);
        PricingRule::factory()->forVehicleType($this->bicycle)->create(['mode_id' => $order->mode_id, 'tayar_share_percent' => '50.00']);

        $this->assertSame(['tayar_share_percent' => '50.00', 'tayar_earning' => '5.03', 'platform_fee' => '5.02'], $this->earning($order));
    }
}
