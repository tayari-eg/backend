<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Pricing\Actions\UpdatePricingRuleAction;
use App\Domains\Pricing\Models\PricingRule;
use App\Domains\Tayars\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PricingRuleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs($this->createStaffUser(StaffRole::Admin));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function rule(Mode $mode, array $overrides = []): array
    {
        return [
            'mode_id' => $mode->id,
            'base_fare' => 20,
            'price_per_km' => 4.5,
            'minimum_fare' => '30.00',
            'tayar_share_percent' => 80,
            ...$overrides,
        ];
    }

    public function test_a_rule_for_every_vehicle_is_created_with_amounts_as_decimal_strings(): void
    {
        $mode = Mode::factory()->zone()->create();

        $this->postJson(route('api.v1.admin.pricing-rules.store'), $this->rule($mode, ['food_extra' => 7.25]))
            ->assertCreated()
            ->assertJsonPath('data.mode.id', $mode->id)
            ->assertJsonPath('data.mode.code', 'zone')
            ->assertJsonPath('data.vehicle_type', null)
            ->assertJsonPath('data.base_fare', '20.00')
            ->assertJsonPath('data.price_per_km', '4.50')
            ->assertJsonPath('data.minimum_fare', '30.00')
            ->assertJsonPath('data.food_extra', '7.25')
            ->assertJsonPath('data.tayar_share_percent', '80.00')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('pricing_rules', ['mode_id' => $mode->id, 'vehicle_type_id' => null, 'price_per_km' => '4.50', 'food_extra' => '7.25']);
    }

    public function test_a_rule_for_one_vehicle_type_is_created(): void
    {
        $mode = Mode::factory()->zone()->create();
        $bicycle = VehicleType::factory()->create(['name' => ['ar' => 'عجلة', 'en' => 'Bicycle']]);

        $this->postJson(route('api.v1.admin.pricing-rules.store'), $this->rule($mode, ['vehicle_type_id' => $bicycle->id, 'is_active' => false]))
            ->assertCreated()
            ->assertJsonPath('data.vehicle_type.id', $bicycle->id)
            ->assertJsonPath('data.vehicle_type.name', ['ar' => 'عجلة', 'en' => 'Bicycle'])
            ->assertJsonPath('data.food_extra', null)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_amounts_and_the_share_are_bounded_decimals(): void
    {
        $mode = Mode::factory()->zone()->create();

        $this->postJson(route('api.v1.admin.pricing-rules.store'), $this->rule($mode, ['base_fare' => -1, 'price_per_km' => 1.234, 'minimum_fare' => 100000000, 'tayar_share_percent' => 100.01]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['base_fare', 'price_per_km', 'minimum_fare', 'tayar_share_percent']);

        $this->postJson(route('api.v1.admin.pricing-rules.store'), ['mode_id' => 999, 'vehicle_type_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mode_id', 'vehicle_type_id', 'base_fare', 'price_per_km', 'minimum_fare', 'tayar_share_percent']);
    }

    public function test_one_rule_per_mode_and_vehicle_type_including_every_vehicle(): void
    {
        $mode = Mode::factory()->zone()->create();
        $car = VehicleType::factory()->create();
        PricingRule::factory()->create(['mode_id' => $mode->id]);
        PricingRule::factory()->inactive()->create(['mode_id' => $mode->id, 'vehicle_type_id' => $car->id]);

        $response = $this->postJson(route('api.v1.admin.pricing-rules.store'), $this->rule($mode), ['Accept-Language' => 'en'])
            ->assertUnprocessable();
        $this->assertSame(['This mode already has a pricing rule for this vehicle type. Edit that rule instead.'], $response->json('errors.vehicle_type_id'));

        $this->postJson(route('api.v1.admin.pricing-rules.store'), $this->rule($mode, ['vehicle_type_id' => $car->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vehicle_type_id']);

        $this->postJson(route('api.v1.admin.pricing-rules.store'), $this->rule(Mode::factory()->area()->create()))
            ->assertCreated();

        $this->assertDatabaseCount('pricing_rules', 3);
    }

    public function test_update_changes_only_the_fields_sent_and_keeps_one_rule_per_pair(): void
    {
        $mode = Mode::factory()->zone()->create();
        $car = VehicleType::factory()->create();
        $allVehicles = PricingRule::factory()->create(['mode_id' => $mode->id, 'base_fare' => '20.00']);
        $carRule = PricingRule::factory()->create(['mode_id' => $mode->id, 'vehicle_type_id' => $car->id]);

        $this->patchJson(route('api.v1.admin.pricing-rules.update', $allVehicles), ['base_fare' => 25.5, 'food_extra' => null])
            ->assertOk()
            ->assertJsonPath('data.base_fare', '25.50')
            ->assertJsonPath('data.price_per_km', '5.00')
            ->assertJsonPath('data.food_extra', null);

        $this->patchJson(route('api.v1.admin.pricing-rules.update', $carRule), ['vehicle_type_id' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vehicle_type_id']);

        $this->patchJson(route('api.v1.admin.pricing-rules.update', $carRule), ['tayar_share_percent' => 75])
            ->assertOk()
            ->assertJsonPath('data.tayar_share_percent', '75.00');
    }

    public function test_the_uniqueness_check_uses_the_rule_as_it_is_in_the_database_not_a_stale_copy(): void
    {
        $zone = Mode::factory()->zone()->create();
        $area = Mode::factory()->area()->create();
        PricingRule::factory()->create(['mode_id' => $zone->id]);
        $carRule = PricingRule::factory()->forVehicleType()->create(['mode_id' => $area->id]);
        PricingRule::query()->whereKey($carRule->id)->update(['mode_id' => $zone->id]);

        try {
            app(UpdatePricingRuleAction::class)->handle($carRule, ['vehicle_type_id' => null]);
            $this->fail('A second "all vehicles" rule for the zone mode was expected to be refused.');
        } catch (ValidationException $exception) {
            $this->assertSame(422, $exception->status);
            $this->assertSame([__('admin.pricing_rule_exists')], $exception->errors()['vehicle_type_id']);
        }

        $this->assertSame(1, PricingRule::query()->where('mode_id', $zone->id)->whereNull('vehicle_type_id')->count());
        $this->assertNotNull($carRule->refresh()->vehicle_type_id);
    }

    public function test_the_list_filters_by_mode_vehicle_type_and_is_active(): void
    {
        $zone = Mode::factory()->zone()->create();
        $area = Mode::factory()->area()->create();
        $car = VehicleType::factory()->create();
        $zoneAll = PricingRule::factory()->create(['mode_id' => $zone->id]);
        $zoneCar = PricingRule::factory()->inactive()->create(['mode_id' => $zone->id, 'vehicle_type_id' => $car->id]);
        $areaAll = PricingRule::factory()->create(['mode_id' => $area->id]);

        $this->getJson(route('api.v1.admin.pricing-rules.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
        $this->assertEqualsCanonicalizing([$zoneAll->id, $zoneCar->id], $this->getJson(route('api.v1.admin.pricing-rules.index', ['mode_id' => $zone->id]))->json('data.*.id'));
        $this->assertSame([$zoneCar->id], $this->getJson(route('api.v1.admin.pricing-rules.index', ['vehicle_type_id' => $car->id]))->json('data.*.id'));
        $this->assertSame([$zoneCar->id], $this->getJson(route('api.v1.admin.pricing-rules.index', ['is_active' => 'false']))->json('data.*.id'));
        $this->assertEqualsCanonicalizing([$zoneAll->id, $areaAll->id], $this->getJson(route('api.v1.admin.pricing-rules.index', ['is_active' => 'true']))->json('data.*.id'));
    }

    public function test_show_activate_deactivate_and_delete(): void
    {
        $rule = PricingRule::factory()->create();

        $this->getJson(route('api.v1.admin.pricing-rules.show', $rule))
            ->assertOk()
            ->assertJsonPath('data.id', $rule->id);

        $this->postJson(route('api.v1.admin.pricing-rules.deactivate', $rule))
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->postJson(route('api.v1.admin.pricing-rules.activate', $rule))
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->deleteJson(route('api.v1.admin.pricing-rules.destroy', $rule))->assertNoContent();
        $this->assertModelMissing($rule);
    }

    public function test_an_unknown_rule_is_404(): void
    {
        $this->getJson(route('api.v1.admin.pricing-rules.show', 999))->assertNotFound();
    }
}
