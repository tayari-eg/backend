<?php

namespace Tests\Feature\Domains\Incentives;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Incentives\Models\TayarRank;
use App\Domains\Incentives\Models\TayarRankHistory;
use App\Domains\Tayars\Actions\ApproveTayarApplicationAction;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use Database\Seeders\TayarRankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignStartingRankActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_gives_the_lowest_rank_with_a_history_row_and_a_second_approval_keeps_the_rank(): void
    {
        $this->seed(TayarRankSeeder::class);
        $staff = $this->createStaffUser(StaffRole::Ops);
        $profile = TayarProfile::factory()->has(Vehicle::factory())->create();

        $profile = app(ApproveTayarApplicationAction::class)->handle($profile, $staff);

        $trainee = TayarRank::query()->where('code', 'trainee')->sole();
        $this->assertSame($trainee->id, $profile->refresh()->rank_id);
        $history = TayarRankHistory::query()->sole();
        $this->assertSame([$profile->id, null, $trainee->id], [$history->tayar_profile_id, $history->from_rank_id, $history->to_rank_id]);

        $captain = TayarRank::query()->where('code', 'captain')->sole();
        $profile->forceFill(['status' => TayarStatus::Pending, 'rank_id' => $captain->id])->save();
        app(ApproveTayarApplicationAction::class)->handle($profile, $staff);

        $this->assertSame($captain->id, $profile->refresh()->rank_id);
        $this->assertDatabaseCount('tayar_rank_histories', 1);
    }
}
