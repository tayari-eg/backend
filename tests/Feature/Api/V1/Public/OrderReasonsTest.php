<?php

namespace Tests\Feature\Api\V1\Public;

use App\Domains\Orders\Models\OrderReason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReasonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_cancel_reasons_are_listed_in_order_in_the_request_language_without_login(): void
    {
        OrderReason::factory()->create(['name' => ['ar' => 'ثاني', 'en' => 'Second'], 'sort_order' => 2]);
        OrderReason::factory()->create(['name' => ['ar' => 'أول', 'en' => 'First'], 'sort_order' => 1]);
        OrderReason::factory()->inactive()->create();
        OrderReason::factory()->notDelivered()->create();

        $this->getJson(route('api.v1.public.order-reasons.index', ['type' => 'cancel']), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'First')
            ->assertJsonPath('data.1.name', 'Second')
            ->assertJsonStructure(['data' => [['id', 'name']], 'meta', 'links']);

        $this->getJson(route('api.v1.public.order-reasons.index', ['type' => 'cancel']), ['Accept-Language' => 'ar'])
            ->assertJsonPath('data.0.name', 'أول');
    }

    public function test_the_type_is_required_and_must_be_known(): void
    {
        $this->getJson(route('api.v1.public.order-reasons.index'))->assertUnprocessable()->assertJsonValidationErrors(['type']);
        $this->getJson(route('api.v1.public.order-reasons.index', ['type' => 'other']))->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }
}
