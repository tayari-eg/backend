<?php

namespace Tests\Feature\Console;

use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Tayars\Models\TayarProfile;
use App\Jobs\RecalculateBusinessRatingsChunk;
use App\Jobs\RecalculateTayarRanksChunk;
use App\Models\User;
use Database\Seeders\TayarRankSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecalculateTayarRanksCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_one_job_per_chunk_all_with_the_same_now_and_a_timeout_below_retry_after(): void
    {
        $this->freezeSecond();
        Queue::fake();
        $approved = TayarProfile::factory()->approved()->count(101)->create();
        TayarProfile::factory()->create();
        $business = BusinessProfile::factory()->approved()->create();

        $this->artisan('tayars:recalculate-ranks')
            ->expectsOutputToContain('3 rank recalculation job(s) queued.')
            ->assertSuccessful();

        $tayarJobs = Queue::pushed(RecalculateTayarRanksChunk::class)->values();
        $this->assertSame([100, 1], $tayarJobs->map(fn (RecalculateTayarRanksChunk $job): int => count($job->profileIds))->all());
        $this->assertEqualsCanonicalizing($approved->modelKeys(), $tayarJobs->flatMap(fn (RecalculateTayarRanksChunk $job): array => $job->profileIds)->all());
        $businessJob = Queue::pushed(RecalculateBusinessRatingsChunk::class)->sole();
        $this->assertSame([$business->id], $businessJob->profileIds);

        $retryAfter = config()->integer('queue.connections.database.retry_after');
        foreach ([...$tayarJobs, $businessJob] as $job) {
            $this->assertTrue($job->now->equalTo(now()));
            $this->assertLessThan($retryAfter, $job->timeout);
        }
    }

    public function test_with_now_it_ranks_every_approved_tayar_saves_every_business_average_and_a_rerun_changes_nothing(): void
    {
        $this->seed(TayarRankSeeder::class);
        config(['tayari.ratings.min_ratings_for_average' => 2]);
        $approved = TayarProfile::factory()->approved()->create();
        $pending = TayarProfile::factory()->create();
        $business = BusinessProfile::factory()->approved()->create();
        foreach ([5, 4] as $stars) {
            $order = Order::factory()->onTheWayWith($approved)->forSender(User::query()->findOrFail($business->user_id))->create(['status' => OrderStatus::Delivered, 'delivered_at' => now()->subDays(3)]);
            OrderRating::factory()->forOrder($order)->byTayar()->create(['stars' => $stars]);
        }

        $this->artisan('tayars:recalculate-ranks --now')
            ->expectsOutputToContain('Ranks and ratings recalculated in 2 chunk(s).')
            ->assertSuccessful();
        $this->artisan('tayars:recalculate-ranks --now')->assertSuccessful();

        $this->assertSame('trainee', $approved->refresh()->load('rank')->rank->code);
        $this->assertSame(2, $approved->delivered_orders_30d);
        $this->assertNull($pending->refresh()->rank_id);
        $this->assertSame(['4.50', 2], [$business->refresh()->rating_avg, $business->ratings_count_30d]);
        $this->assertDatabaseCount('tayar_rank_histories', 1);
    }

    public function test_it_is_scheduled_nightly_at_three_in_business_time(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => str_contains($event->command, 'tayars:recalculate-ranks'));

        $this->assertNotNull($event);
        $this->assertSame('0 3 * * *', $event->expression);
        $this->assertSame(config('app.business_timezone'), $event->timezone);
    }
}
