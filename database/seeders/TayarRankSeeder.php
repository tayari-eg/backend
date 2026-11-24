<?php

namespace Database\Seeders;

use App\Domains\Incentives\Models\TayarRank;
use Illuminate\Database\Seeder;

class TayarRankSeeder extends Seeder
{
    /**
     * @var list<array{code: string, name: array{ar: string, en: string}, level: int, min_delivered_orders_30d: int, min_rating: ?string, color: string}>
     */
    private const array RANKS = [
        ['code' => 'trainee', 'name' => ['ar' => 'متدرب', 'en' => 'Trainee'], 'level' => 1, 'min_delivered_orders_30d' => 0, 'min_rating' => null, 'color' => '#9E9E9E'],
        ['code' => 'tayar', 'name' => ['ar' => 'طيار', 'en' => 'Tayar'], 'level' => 2, 'min_delivered_orders_30d' => 30, 'min_rating' => '4.30', 'color' => '#2E7D32'],
        ['code' => 'captain', 'name' => ['ar' => 'كابتن', 'en' => 'Captain'], 'level' => 3, 'min_delivered_orders_30d' => 80, 'min_rating' => '4.60', 'color' => '#1565C0'],
        ['code' => 'senior_captain', 'name' => ['ar' => 'كابتن أول', 'en' => 'Senior Captain'], 'level' => 4, 'min_delivered_orders_30d' => 150, 'min_rating' => '4.80', 'color' => '#F9A825'],
    ];

    /**
     * Idempotent: creates a missing rank by its code and never overwrites what staff have edited.
     */
    public function run(): void
    {
        foreach (self::RANKS as $rank) {
            if (! TayarRank::query()->where('code', $rank['code'])->exists()) {
                (new TayarRank)->forceFill($rank)->save();
            }
        }

        // DatabaseSeeder runs without model events, so the saved hook doesn't clear the cache.
        TayarRank::forgetLadder();
    }
}
