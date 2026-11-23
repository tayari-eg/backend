<?php

namespace Tests\Feature\Api\V1\Tayar;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TayarOrderTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        Sanctum::actingAs(User::query()->findOrFail($this->tayar->user_id));
    }

    public function test_my_accepted_orders_list_active_by_default_and_past_on_request_newest_first(): void
    {
        $older = Order::factory()->acceptedBy($this->tayar)->create();
        $newer = Order::factory()->acceptedBy($this->tayar)->create();
        $cancelled = Order::factory()->acceptedBy($this->tayar)->create(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
        Order::factory()->create();
        Order::factory()->acceptedBy($this->onlineTayar(30.0460, 31.2370))->create();

        $this->assertSame([$newer->id, $older->id], collect($this->getJson(route('api.v1.tayar.orders.index'))->assertOk()->json('data'))->pluck('id')->all());
        $this->assertSame([$cancelled->id], collect($this->getJson(route('api.v1.tayar.orders.index', ['status' => 'past']))->assertOk()->json('data'))->pluck('id')->all());
        $this->getJson(route('api.v1.tayar.orders.index', ['status' => 'all']))->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_the_phones_show_only_while_the_order_is_active_and_the_names_stay(): void
    {
        $attributes = ['pickup_contact_phone' => '+201011111111', 'receiver_name' => 'Mona', 'receiver_phone' => '+201022222222'];
        $active = Order::factory()->acceptedBy($this->tayar)->create($attributes);
        $past = Order::factory()->acceptedBy($this->tayar)->create([...$attributes, 'status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);

        $this->getJson(route('api.v1.tayar.orders.show', $active))
            ->assertOk()
            ->assertJsonPath('data.sender.phone', User::query()->whereKey($active->sender_id)->value('phone'))
            ->assertJsonPath('data.pickup.contact_phone', '+201011111111')
            ->assertJsonPath('data.receiver.phone', '+201022222222');

        $this->getJson(route('api.v1.tayar.orders.show', $past))
            ->assertOk()
            ->assertJsonPath('data.sender.phone', null)
            ->assertJsonPath('data.sender.name', User::query()->whereKey($past->sender_id)->value('name'))
            ->assertJsonPath('data.pickup.contact_phone', null)
            ->assertJsonPath('data.receiver.phone', null)
            ->assertJsonPath('data.receiver.name', 'Mona');
    }

    public function test_a_cancel_fee_owed_to_me_shows_on_my_order(): void
    {
        $owed = Order::factory()->acceptedBy($this->tayar)->create([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_fee' => '20.00',
            'cancel_fee_owed_to_tayar_id' => $this->tayar->user_id,
        ]);
        $free = Order::factory()->acceptedBy($this->tayar)->create(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);

        $this->getJson(route('api.v1.tayar.orders.show', $owed))->assertOk()->assertJsonPath('data.cancel_fee', '20.00');
        $this->getJson(route('api.v1.tayar.orders.show', $free))->assertOk()->assertJsonPath('data.cancel_fee', '0.00');
    }

    public function test_i_see_my_order_with_the_sender_and_receiver_but_not_another_tayars(): void
    {
        $mine = Order::factory()->acceptedBy($this->tayar)->create(['receiver_name' => 'Mona']);
        $theirs = Order::factory()->acceptedBy($this->onlineTayar(30.0460, 31.2370))->create();

        $this->getJson(route('api.v1.tayar.orders.show', $mine))
            ->assertOk()
            ->assertJsonPath('data.receiver.name', 'Mona')
            ->assertJsonPath('data.sender.business_name', fn (?string $name): bool => $name !== null)
            ->assertJsonPath('data.mode.code', 'zone');
        $this->getJson(route('api.v1.tayar.orders.show', $theirs))->assertForbidden();
        $this->getJson(route('api.v1.tayar.orders.show', Order::factory()->create()))->assertForbidden();
    }

    public function test_my_orders_list_runs_the_same_queries_however_many_orders_and_photos_it_has(): void
    {
        Storage::fake('local');
        $this->createPickedUpOrdersWithPhotos(Order::factory()->pickedUpBy($this->tayar), 2);
        $this->getJson(route('api.v1.tayar.orders.index'))->assertOk();

        $fewer = $this->countQueries(fn () => $this->getJson(route('api.v1.tayar.orders.index'))->assertOk()->assertJsonCount(2, 'data'));
        $this->createPickedUpOrdersWithPhotos(Order::factory()->pickedUpBy($this->tayar), 3);
        $more = $this->countQueries(fn () => $this->getJson(route('api.v1.tayar.orders.index'))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.pickup_photos.1.url', fn (?string $url): bool => $url !== null));

        $this->assertSame($fewer, $more);
    }
}
