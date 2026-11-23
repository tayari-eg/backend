<?php

namespace Tests\Feature\Domains\Orders;

use App\Contracts\SmsSender;
use App\Domains\Accounts\Enums\Locale;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Jobs\SendReceiverCodes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SendReceiverCodesTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_different_codes_go_to_the_receiver_with_the_link_and_only_their_hashes_are_stored(): void
    {
        config(['tayari.tracking.url' => 'https://tayari.test/track/{token}']);
        $order = Order::factory()->pickedUpBy($this->onlineTayar(30.0450, 31.2360))->create(['receiver_phone' => '+201099999999']);

        $codes = $this->sendReceiverCodes($order);

        $this->assertNotSame($codes['delivery'], $codes['refusal']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $codes['delivery']);
        $order->refresh();
        $this->assertTrue(Hash::check($codes['delivery'], $order->delivery_code_hash));
        $this->assertTrue(Hash::check($codes['refusal'], $order->refusal_code_hash));
        $this->assertSame(0, $order->code_attempts);
        $this->assertTrue($order->codes_expire_at->equalTo($order->codes_sent_at->copy()->addHours(config()->integer('tayari.tracking.codes_valid_hours'))));

        $row = (array) DB::table('orders')->where('id', $order->id)->first();
        $this->assertNotContains($codes['delivery'], $row);
        $this->assertNotContains($codes['refusal'], $row);
        $this->assertArrayNotHasKey('delivery_code_hash', $order->toArray());

        $sms = app(SmsSender::class)->messages[0];
        $this->assertSame('+201099999999', $sms['to']);
        $this->assertStringContainsString("https://tayari.test/track/{$order->tracking_token}", $sms['message']);
    }

    public function test_the_sms_is_in_the_receivers_language_when_they_have_an_account_and_arabic_otherwise(): void
    {
        $tayar = $this->onlineTayar(30.0450, 31.2360);
        $receiver = User::factory()->create(['locale' => Locale::En]);
        $english = Order::factory()->pickedUpBy($tayar)->create(['receiver_user_id' => $receiver->id]);
        $arabic = Order::factory()->pickedUpBy($tayar)->create();

        $englishCodes = $this->sendReceiverCodes($english);
        $this->assertStringContainsString("delivery code {$englishCodes['delivery']}", end(app(SmsSender::class)->messages)['message']);

        $arabicCodes = $this->sendReceiverCodes($arabic);
        $this->assertStringContainsString("كود التسليم {$arabicCodes['delivery']}", end(app(SmsSender::class)->messages)['message']);
    }

    public function test_nothing_is_sent_once_the_goods_are_no_longer_with_the_tayar(): void
    {
        $smsSender = $this->fakeSmsSender();

        foreach ([OrderStatus::Pending, OrderStatus::Delivered, OrderStatus::Cancelled] as $status) {
            $order = Order::factory()->withStatus($status)->create();
            (new SendReceiverCodes($order->id))->handle($smsSender);
            $this->assertNull($order->refresh()->delivery_code_hash);
        }

        $this->assertSame([], $smsSender->messages);
    }
}
