<?php

namespace Tests\Feature\Domains\Orders;

use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Orders\Actions\RateOrderAction;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\RaterRole;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RateOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private function deliveredOrder(string $deliveredAt = '-1 hour'): Order
    {
        return Order::factory()
            ->onTheWayWith(TayarProfile::factory()->approved()->create())
            ->create(['status' => OrderStatus::Delivered, 'delivered_at' => now()->modify($deliveredAt)]);
    }

    private function rate(Order $order, RaterRole $role, int $stars, ?string $comment = null): OrderRating
    {
        $rater = User::query()->findOrFail($role === RaterRole::Business ? $order->sender_id : $order->tayar_id);

        return app(RateOrderAction::class)->handle($order, $rater, $role, $stars, $comment);
    }

    private function assertRefused(callable $callback, string $message): void
    {
        try {
            $callback();
            $this->fail('The rating should have been refused.');
        } catch (ValidationException $exception) {
            $this->assertSame([$message], $exception->errors()['order']);
        }
    }

    public function test_the_business_and_the_tayar_rate_each_other_once_each_hidden_until_the_window_ends(): void
    {
        $order = $this->deliveredOrder();

        $business = $this->rate($order, RaterRole::Business, 5, 'Fast and careful');
        $tayar = $this->rate($order, RaterRole::Tayar, 4);

        $windowEnd = $order->delivered_at->addHours(config()->integer('tayari.ratings.window_hours'));
        $this->assertSame([$order->sender_id, $order->tayar_id, 5, 'Fast and careful'], [$business->rater_id, $business->rated_user_id, $business->stars, $business->comment]);
        $this->assertSame([$order->tayar_id, $order->sender_id, 4, null], [$tayar->rater_id, $tayar->rated_user_id, $tayar->stars, $tayar->comment]);
        $this->assertTrue($business->revealed_at->equalTo($windowEnd));
        $this->assertFalse($business->isRevealed(now()));
        $this->assertDatabaseCount('order_ratings', 2);
    }

    public function test_rating_again_within_the_window_changes_the_same_rating(): void
    {
        $order = $this->deliveredOrder();
        $first = $this->rate($order, RaterRole::Business, 2, 'Slow');

        $changed = $this->rate($order, RaterRole::Business, 4, 'Sorry, it was fine');

        $this->assertSame($first->id, $changed->id);
        $this->assertSame([4, 'Sorry, it was fine'], [$changed->stars, $changed->comment]);
        $this->assertDatabaseCount('order_ratings', 1);
    }

    public function test_a_comment_staff_hid_stays_hidden_when_the_rater_changes_the_rating(): void
    {
        $this->freezeSecond();
        $order = $this->deliveredOrder();
        $staff = $this->createStaffUser();
        $first = $this->rate($order, RaterRole::Business, 2, 'Rude words');
        $first->forceFill(['comment_hidden_at' => now(), 'comment_hidden_by' => $staff->id, 'comment_hidden_reason' => 'Abusive'])->save();

        $sameComment = $this->rate($order, RaterRole::Business, 3, 'Rude words');
        $newComment = $this->rate($order, RaterRole::Business, 4, 'Still rude words');

        foreach ([$sameComment, $newComment] as $rating) {
            $this->assertTrue($rating->comment_hidden_at->equalTo(now()));
            $this->assertSame([$staff->id, 'Abusive'], [$rating->comment_hidden_by, $rating->comment_hidden_reason]);
        }

        $saved = OrderRating::query()->sole();
        $this->assertSame([4, 'Still rude words', $staff->id, 'Abusive', null], [$saved->stars, $saved->comment, $saved->comment_hidden_by, $saved->comment_hidden_reason, $saved->visibleComment()]);
        $this->assertTrue($saved->isCommentHidden());
    }

    public function test_a_rating_is_refused_once_the_window_is_over_and_the_old_rating_stays(): void
    {
        $order = $this->deliveredOrder();
        $this->rate($order, RaterRole::Business, 5);

        $this->travel(config()->integer('tayari.ratings.window_hours'))->hours();

        $this->assertRefused(fn () => $this->rate($order, RaterRole::Business, 1), __('orders.rating_closed'));
        $this->assertSame(5, OrderRating::query()->sole()->stars);
    }

    public function test_only_delivered_orders_are_rated(): void
    {
        $profile = TayarProfile::factory()->approved()->create();

        foreach ([OrderStatus::NotDelivered, OrderStatus::Cancelled, OrderStatus::Ongoing] as $status) {
            $order = Order::factory()->onTheWayWith($profile)->create(['status' => $status, 'not_delivered_at' => now()]);

            $this->assertRefused(fn () => $this->rate($order, RaterRole::Business, 5), __('orders.rating_not_delivered'));
        }

        $this->assertDatabaseCount('order_ratings', 0);
    }

    public function test_an_order_from_an_individual_sender_is_not_rated(): void
    {
        $order = $this->deliveredOrder();
        User::query()->whereKey($order->sender_id)->update(['account_type' => AccountType::Individual]);

        $this->assertRefused(fn () => $this->rate($order, RaterRole::Tayar, 5), __('orders.rating_business_only'));
    }
}
