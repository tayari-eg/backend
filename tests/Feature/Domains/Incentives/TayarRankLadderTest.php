<?php

namespace Tests\Feature\Domains\Incentives;

use App\Domains\Incentives\Models\TayarRank;
use Database\Seeders\TayarRankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TayarRankLadderTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_ladder_is_read_back_from_a_store_that_serializes_values_without_objects(): void
    {
        config(['cache.stores.array.serialize' => true, 'cache.serializable_classes' => false]);
        Cache::forgetDriver('array');
        $this->seed(TayarRankSeeder::class);

        $first = TayarRank::ladder();
        $second = TayarRank::ladder();

        $this->assertIsArray(Cache::get('incentives:tayar-ranks:ladder'));
        foreach ([$first, $second] as $ladder) {
            $this->assertContainsOnlyInstancesOf(TayarRank::class, $ladder);
            $this->assertSame(['trainee', 'tayar', 'captain', 'senior_captain'], $ladder->pluck('code')->all());
            $this->assertSame('كابتن أول', $ladder->last()->getTranslation('name', 'ar'));
            $this->assertSame('4.30', $ladder[1]->min_rating);
            $this->assertSame(30, $ladder[1]->min_delivered_orders_30d);
            $this->assertTrue($ladder[1]->exists);
        }
    }
}
