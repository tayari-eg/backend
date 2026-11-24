<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Incentives\Enums\RankChangeSource;
use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Incentives\Models\TayarRankHistory;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Database\Seeders\TayarRankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TayarRankManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TayarRankSeeder::class);
    }

    private function rank(string $code): TayarRank
    {
        return TayarRank::query()->where('code', $code)->firstOrFail();
    }

    public function test_staff_list_the_ranks_lowest_first_with_how_many_tayars_hold_each(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));
        TayarProfile::factory()->approved()->create()->forceFill(['rank_id' => $this->rank('captain')->id])->save();

        $this->getJson(route('api.v1.admin.tayar-ranks.index'))
            ->assertOk()
            ->assertJsonPath('data.0.code', 'trainee')
            ->assertJsonPath('data.0.name.ar', 'متدرب')
            ->assertJsonPath('data.2.code', 'captain')
            ->assertJsonPath('data.2.tayars_count', 1)
            ->assertJsonPath('data.3.name.en', 'Senior Captain');
    }

    public function test_staff_edit_the_name_thresholds_and_colour_but_never_the_code_or_level(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Ops));
        $captain = $this->rank('captain');
        TayarRank::ladder();

        $this->patchJson(route('api.v1.admin.tayar-ranks.update', $captain), [
            'name' => ['ar' => 'كابتن طيار', 'en' => 'Flight Captain'],
            'min_delivered_orders_30d' => 70,
            'min_rating' => '4.5',
            'color' => '#123ABC',
            'code' => 'boss',
            'level' => 9,
        ])
            ->assertOk()
            ->assertJsonPath('data.code', 'captain')
            ->assertJsonPath('data.level', 3)
            ->assertJsonPath('data.name.en', 'Flight Captain')
            ->assertJsonPath('data.min_delivered_orders_30d', 70)
            ->assertJsonPath('data.min_rating', '4.50')
            ->assertJsonPath('data.color', '#123ABC');

        // The cached ladder is cleared on save.
        $this->assertSame(70, TayarRank::ladder()->firstWhere('code', 'captain')->min_delivered_orders_30d);

        $this->patchJson(route('api.v1.admin.tayar-ranks.update', $captain), ['name' => ['ar' => 'طيار', 'en' => 'X'], 'min_rating' => 6, 'color' => 'blue'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.ar', 'min_rating', 'color']);
    }

    public function test_each_threshold_stays_between_the_previous_and_next_levels(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Ops));
        $captain = $this->rank('captain');
        $update = fn (TayarRank $rank, array $body) => $this->patchJson(route('api.v1.admin.tayar-ranks.update', $rank), $body);

        $update($captain, ['min_delivered_orders_30d' => 29, 'min_rating' => '4.29'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.min_delivered_orders_30d.0', __('admin.tayar_rank_below_previous', ['rank' => 'Tayar']))
            ->assertJsonPath('errors.min_rating.0', __('admin.tayar_rank_below_previous', ['rank' => 'Tayar']));
        $update($captain, ['min_delivered_orders_30d' => 151, 'min_rating' => '4.81'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.min_delivered_orders_30d.0', __('admin.tayar_rank_above_next', ['rank' => 'Senior Captain']))
            ->assertJsonPath('errors.min_rating.0', __('admin.tayar_rank_above_next', ['rank' => 'Senior Captain']));
        // No rating needed counts as 0, so only the lowest rank may need none.
        $update($captain, ['min_rating' => null])->assertUnprocessable()->assertJsonValidationErrors('min_rating');
        $update($this->rank('trainee'), ['min_delivered_orders_30d' => 31])->assertUnprocessable()->assertJsonValidationErrors('min_delivered_orders_30d');

        $update($captain, ['min_delivered_orders_30d' => 30, 'min_rating' => '4.80'])->assertOk();
        $update($captain, ['min_delivered_orders_30d' => 150, 'min_rating' => '4.30'])->assertOk();
        $update($this->rank('trainee'), ['min_delivered_orders_30d' => 30, 'min_rating' => null])->assertOk();
    }

    public function test_the_tayar_list_and_detail_show_the_standing_and_filter_by_rank(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));
        $captain = TayarProfile::factory()->approved()->create();
        $captain->forceFill(['rank_id' => $this->rank('captain')->id, 'rating_avg' => '4.70', 'delivered_orders_30d' => 90, 'ratings_count_30d' => 30])->save();
        TayarProfile::factory()->approved()->create();

        $this->getJson(route('api.v1.admin.tayars.index', ['rank_id' => $this->rank('captain')->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.standing.rank.code', 'captain');

        $this->getJson(route('api.v1.admin.tayars.show', $captain))
            ->assertOk()
            ->assertJsonPath('data.standing.rating_avg', '4.70')
            ->assertJsonPath('data.standing.delivered_orders_30d', 90);
    }

    public function test_staff_see_a_tayars_rank_history_newest_first(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));
        $profile = TayarProfile::factory()->approved()->create();
        TayarRankHistory::query()->create(['tayar_profile_id' => $profile->id, 'from_rank_id' => null, 'to_rank_id' => $this->rank('trainee')->id, 'source' => RankChangeSource::Auto]);
        TayarRankHistory::query()->create(['tayar_profile_id' => $profile->id, 'from_rank_id' => $this->rank('trainee')->id, 'to_rank_id' => $this->rank('tayar')->id, 'source' => RankChangeSource::Auto]);

        $this->getJson(route('api.v1.admin.tayars.rank-history', $profile))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.from_rank.code', 'trainee')
            ->assertJsonPath('data.0.to_rank.code', 'tayar')
            ->assertJsonPath('data.1.from_rank', null)
            ->assertJsonPath('data.1.source', 'auto');
    }

    public function test_the_business_score_shows_to_staff_only(): void
    {
        $profile = BusinessProfile::factory()->approved()->create();
        $profile->forceFill(['rating_avg' => '4.20', 'ratings_count_30d' => 12])->save();

        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));
        $this->getJson(route('api.v1.admin.business-profiles.show', $profile))
            ->assertOk()
            ->assertJsonPath('data.rating.rating_avg', '4.20')
            ->assertJsonPath('data.rating.ratings_count_30d', 12);

        Sanctum::actingAs(User::query()->findOrFail($profile->user_id));
        $this->getJson(route('api.v1.client.business-profile.show'))
            ->assertOk()
            ->assertJsonMissingPath('data.rating');
    }
}
