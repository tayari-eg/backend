<?php

namespace Tests\Feature\Domains\Tayars;

use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NearestOnlineTayarsTest extends TestCase
{
    use RefreshDatabase;

    private const array TAHRIR = [30.0444, 31.2357];

    /**
     * Known points around Tahrir Square, with the haversine distance from it in km.
     *
     * @var array<string, array{float, float, float}>
     */
    private const array POINTS = [
        'garden_city' => [30.0355, 31.2310, 1.088],
        'zamalek' => [30.0609, 31.2197, 2.395],
        'maadi' => [29.9602, 31.2569, 9.583],
        'nasr_city' => [30.0729, 31.3456, 11.041],
        'pyramids' => [29.9792, 31.1342, 12.169],
    ];

    /**
     * @return array<string, TayarProfile>
     */
    private function onlineTayarsAtEveryPoint(): array
    {
        return collect(self::POINTS)
            ->map(fn (array $point): TayarProfile => TayarProfile::factory()->approved()->onlineAt($point[0], $point[1])->create())
            ->all();
    }

    public function test_it_returns_online_tayars_within_the_radius_nearest_first_with_the_distance(): void
    {
        $tayars = $this->onlineTayarsAtEveryPoint();

        $nearest = TayarProfile::query()->nearestOnline(self::TAHRIR[0], self::TAHRIR[1], 10)->get();

        $this->assertSame(
            [$tayars['garden_city']->id, $tayars['zamalek']->id, $tayars['maadi']->id],
            $nearest->pluck('id')->all(),
        );

        foreach (['garden_city', 'zamalek', 'maadi'] as $index => $name) {
            $this->assertEqualsWithDelta(self::POINTS[$name][2], (float) $nearest[$index]->distance_km, 0.1, "Distance to [{$name}].");
        }
    }

    public function test_the_radius_cuts_off_points_inside_the_box_but_outside_the_circle(): void
    {
        $tayars = $this->onlineTayarsAtEveryPoint();

        // The Pyramids (12.2 km) fall inside the 11.5 km box's corner area but outside the circle; Nasr City (11.0 km) is in.
        $this->assertSame(
            [$tayars['garden_city']->id, $tayars['zamalek']->id, $tayars['maadi']->id, $tayars['nasr_city']->id],
            TayarProfile::query()->nearestOnline(self::TAHRIR[0], self::TAHRIR[1], 11.5)->pluck('id')->all(),
        );
    }

    public function test_the_default_radius_comes_from_config(): void
    {
        config(['tayari.tayars.nearest_radius_km' => 2]);
        $tayars = $this->onlineTayarsAtEveryPoint();

        $this->assertSame([$tayars['garden_city']->id], TayarProfile::query()->nearestOnline(self::TAHRIR[0], self::TAHRIR[1])->pluck('id')->all());
    }

    public function test_offline_tayars_and_blocked_accounts_are_left_out(): void
    {
        $online = TayarProfile::factory()->approved()->onlineAt(30.0355, 31.2310)->create();
        TayarProfile::factory()->approved()->create(['current_lat' => 30.0450, 'current_lng' => 31.2360]);
        TayarProfile::factory()->approved()->onlineAt(30.0446, 31.2359)->create([
            'user_id' => User::factory()->create(['status' => UserStatus::Blocked]),
        ]);

        $this->assertSame([$online->id], TayarProfile::query()->nearestOnline(self::TAHRIR[0], self::TAHRIR[1], 5)->pluck('id')->all());
    }

    public function test_a_tayar_sent_back_to_review_but_still_online_is_left_out(): void
    {
        $approved = TayarProfile::factory()->approved()->onlineAt(30.0355, 31.2310)->create();
        TayarProfile::factory()->onlineAt(30.0446, 31.2359)->create();

        $this->assertSame([$approved->id], TayarProfile::query()->nearestOnline(self::TAHRIR[0], self::TAHRIR[1], 5)->pluck('id')->all());
    }

    public function test_a_tayar_at_the_exact_point_is_at_distance_zero(): void
    {
        $here = TayarProfile::factory()->approved()->onlineAt(self::TAHRIR[0], self::TAHRIR[1])->create();

        $nearest = TayarProfile::query()->nearestOnline(self::TAHRIR[0], self::TAHRIR[1], 1)->first();

        $this->assertSame($here->id, $nearest->id);
        $this->assertEqualsWithDelta(0.0, (float) $nearest->distance_km, 0.001);
    }
}
