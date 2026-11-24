<?php

namespace Tests\Feature\Api\V1\Tayar;

use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Database\Seeders\TayarRankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RankTest extends TestCase
{
    use RefreshDatabase;

    private TayarProfile $tayar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TayarRankSeeder::class);
        $this->tayar = $this->onlineTayar(30.0450, 31.2360);
        Sanctum::actingAs(User::query()->findOrFail($this->tayar->user_id));
    }

    public function test_before_the_first_nightly_run_the_tayar_shows_at_the_lowest_rank_with_the_ladder(): void
    {
        $this->withHeader('Accept-Language', 'ar')
            ->getJson(route('api.v1.tayar.rank.show'))
            ->assertOk()
            ->assertJsonPath('data.rank.code', 'trainee')
            ->assertJsonPath('data.rank.name', 'متدرب')
            ->assertJsonPath('data.next_rank.code', 'tayar')
            ->assertJsonPath('data.next_rank.min_delivered_orders_30d', 30)
            ->assertJsonPath('data.next_rank.min_rating', '4.30')
            ->assertJsonPath('data.rating_avg', null)
            ->assertJsonPath('data.min_ratings_for_average', 5)
            ->assertJsonPath('data.at_risk', null)
            ->assertJsonPath('data.updated_at', null)
            ->assertJsonCount(4, 'data.ranks');
    }

    public function test_the_tayar_sees_the_saved_rank_numbers_and_the_risk(): void
    {
        $this->freezeSecond();
        $this->tayar->forceFill([
            'rank_id' => TayarRank::query()->where('code', 'senior_captain')->value('id'),
            'rank_below_since' => now()->subDays(2),
            'rating_avg' => '4.75',
            'ratings_count_30d' => 40,
            'delivered_orders_30d' => 120,
            'standing_updated_at' => now(),
        ])->save();

        $this->getJson(route('api.v1.tayar.rank.show'))
            ->assertOk()
            ->assertJsonPath('data.rank.code', 'senior_captain')
            ->assertJsonPath('data.next_rank', null)
            ->assertJsonPath('data.rating_avg', '4.75')
            ->assertJsonPath('data.delivered_orders_30d', 120)
            ->assertJsonPath('data.at_risk.drops_at', now(config('app.business_timezone'))->subDays(2)->startOfDay()->addDays(7)->utc()->toIso8601ZuluString());
    }

    public function test_the_ratings_list_shows_only_revealed_business_ratings_without_the_order_or_business(): void
    {
        $order = fn (): Order => Order::factory()->onTheWayWith($this->tayar)->create(['status' => OrderStatus::Delivered, 'delivered_at' => now()->subDays(3)]);
        // Created first but revealed later: the list follows the rating id, never the time.
        $first = OrderRating::factory()->forOrder($order())->revealedAt(now()->subDay())->create(['stars' => 4, 'comment' => 'Good']);
        OrderRating::factory()->forOrder($order())->revealedAt(now()->subDays(2))->withHiddenComment()->create(['stars' => 1]);
        OrderRating::factory()->forOrder($order())->revealedAt(now()->addHour())->create(['stars' => 5]);
        OrderRating::factory()->forOrder($order())->byTayar()->create();
        OrderRating::factory()->create();

        $response = $this->getJson(route('api.v1.tayar.ratings.index'))->assertOk();

        $this->assertSame([1, 4], collect($response->json('data'))->pluck('stars')->all());
        $response->assertJsonPath('data.0.comment', null)
            ->assertJsonPath('data.1.comment', 'Good')
            ->assertJsonPath('data.1.date', $first->revealed_at->timezone(config('app.business_timezone'))->format('Y-m-d'));
        $this->assertSame(['stars', 'comment', 'date'], array_keys($response->json('data.0')));
    }

    public function test_the_ratings_cursor_carries_no_time(): void
    {
        $order = fn (): Order => Order::factory()->onTheWayWith($this->tayar)->create(['status' => OrderStatus::Delivered, 'delivered_at' => now()->subDays(3)]);
        OrderRating::factory()->forOrder($order())->revealedAt(now()->subDays(2))->create(['stars' => 3]);
        $newest = OrderRating::factory()->forOrder($order())->revealedAt(now()->subDay())->create(['stars' => 5]);

        $firstPage = $this->getJson(route('api.v1.tayar.ratings.index', ['per_page' => 1]))->assertOk()->assertJsonPath('data.0.stars', 5);
        $cursor = $firstPage->json('meta.next_cursor');

        $this->assertSame(['id' => $newest->id, '_pointsToNextItems' => true], json_decode(base64_decode(strtr($cursor, '-_', '+/')), true));
        $this->getJson(route('api.v1.tayar.ratings.index', ['per_page' => 1, 'cursor' => $cursor]))->assertOk()->assertJsonPath('data.0.stars', 3);
    }
}
