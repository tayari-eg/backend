<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\RaterRole;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RatingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_list_ratings_newest_first_with_filters(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));
        $order = Order::factory()->onTheWayWith(TayarProfile::factory()->approved()->create())->create(['status' => OrderStatus::Delivered, 'delivered_at' => now()->subHour()]);
        $byBusiness = OrderRating::factory()->forOrder($order)->create(['stars' => 5, 'comment' => null]);
        $byTayar = OrderRating::factory()->forOrder($order)->byTayar()->withHiddenComment()->create(['stars' => 2]);
        $other = OrderRating::factory()->create(['stars' => 2, 'comment' => 'Fine']);

        $ids = fn (array $query): array => collect($this->getJson(route('api.v1.admin.ratings.index', $query))->assertOk()->json('data'))->pluck('id')->all();

        $this->assertSame([$other->id, $byTayar->id, $byBusiness->id], $ids([]));
        $this->assertSame([$byTayar->id], $ids(['rater_role' => RaterRole::Tayar->value]));
        $this->assertSame([$other->id, $byTayar->id], $ids(['stars' => 2]));
        $this->assertSame([$byTayar->id, $byBusiness->id], $ids(['order_id' => $order->id]));
        $this->assertSame([$byBusiness->id], $ids(['rated_user_id' => $order->tayar_id, 'has_comment' => 'false']));
        $this->assertSame([$byTayar->id], $ids(['comment_hidden' => 'true']));
        $this->getJson(route('api.v1.admin.ratings.index', ['rater_role' => 'receiver', 'stars' => 9]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rater_role', 'stars']);
    }

    public function test_staff_see_the_full_comment_and_both_parties(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));
        $rating = OrderRating::factory()->withHiddenComment()->create();

        $this->getJson(route('api.v1.admin.ratings.show', $rating))
            ->assertOk()
            ->assertJsonPath('data.comment', 'Rude words')
            ->assertJsonPath('data.comment_hidden_reason', 'Abusive language')
            ->assertJsonPath('data.rater.id', $rating->rater_id)
            ->assertJsonPath('data.rated_user.id', $rating->rated_user_id)
            ->assertJsonPath('data.order.id', $rating->order_id)
            ->assertJsonPath('data.is_revealed', true);
    }

    public function test_staff_hide_a_comment_with_a_reason_and_unhide_it(): void
    {
        $staff = $this->createStaffUser(StaffRole::Ops);
        Sanctum::actingAs($staff);
        $rating = OrderRating::factory()->create(['comment' => 'Rude words']);

        $this->postJson(route('api.v1.admin.ratings.hide-comment', $rating), [])->assertUnprocessable()->assertJsonValidationErrors('reason');

        $this->postJson(route('api.v1.admin.ratings.hide-comment', $rating), ['reason' => 'Insults'])
            ->assertOk()
            ->assertJsonPath('data.comment_hidden_reason', 'Insults')
            ->assertJsonPath('data.comment_hidden_by.id', $staff->id);
        $this->assertNotNull($rating->refresh()->comment_hidden_at);
        $this->postJson(route('api.v1.admin.ratings.hide-comment', $rating), ['reason' => 'Again'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.rating.0', __('admin.rating_comment_already_hidden'));

        $this->postJson(route('api.v1.admin.ratings.unhide-comment', $rating))
            ->assertOk()
            ->assertJsonPath('data.comment_hidden_at', null)
            ->assertJsonPath('data.comment_hidden_reason', null);
        $this->postJson(route('api.v1.admin.ratings.unhide-comment', $rating))
            ->assertUnprocessable()
            ->assertJsonPath('errors.rating.0', __('admin.rating_comment_not_hidden'));
    }

    public function test_a_rating_without_a_comment_has_nothing_to_hide(): void
    {
        Sanctum::actingAs($this->createStaffUser());
        $rating = OrderRating::factory()->create(['comment' => null]);

        $this->postJson(route('api.v1.admin.ratings.hide-comment', $rating), ['reason' => 'Insults'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.rating.0', __('admin.rating_has_no_comment'));
    }

    public function test_the_order_detail_lists_both_ratings(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));
        $rating = OrderRating::factory()->create(['stars' => 3]);

        $this->getJson(route('api.v1.admin.orders.show', $rating->order_id))
            ->assertOk()
            ->assertJsonPath('data.ratings.0.id', $rating->id)
            ->assertJsonPath('data.ratings.0.rater_role', 'business')
            ->assertJsonPath('data.ratings.0.stars', 3);
    }
}
