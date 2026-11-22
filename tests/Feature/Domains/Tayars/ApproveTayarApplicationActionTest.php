<?php

namespace Tests\Feature\Domains\Tayars;

use App\Domains\Tayars\Actions\ApproveTayarApplicationAction;
use App\Domains\Tayars\Actions\RejectTayarApplicationAction;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Enums\VehicleStatus;
use App\Domains\Tayars\Events\TayarApplicationApproved;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApproveTayarApplicationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_activates_the_vehicle_and_records_the_review(): void
    {
        Event::fake([TayarApplicationApproved::class]);
        $profile = TayarProfile::factory()->has(Vehicle::factory())->create();
        $reviewer = $this->createStaffUser();

        app(ApproveTayarApplicationAction::class)->handle($profile, $reviewer);

        $profile->refresh();
        $this->assertSame(TayarStatus::Approved, $profile->status);
        $this->assertSame($reviewer->id, $profile->reviewed_by);
        $this->assertSame(VehicleStatus::Approved, $profile->vehicle->status);
        $this->assertTrue($profile->vehicle->is_active);
        $this->assertDatabaseHas('tayar_reviews', ['tayar_profile_id' => $profile->id, 'decision' => 'approved', 'reviewed_by' => $reviewer->id]);
        Event::assertDispatched(TayarApplicationApproved::class);
    }

    public function test_a_rejection_is_kept_in_the_history_after_a_later_approval(): void
    {
        $profile = TayarProfile::factory()->has(Vehicle::factory())->create();
        $reviewer = $this->createStaffUser();

        app(RejectTayarApplicationAction::class)->handle($profile, $reviewer, 'Blurry criminal record');
        $profile->refresh()->forceFill(['status' => TayarStatus::Pending])->save();
        app(ApproveTayarApplicationAction::class)->handle($profile, $reviewer);

        $this->assertSame(['rejected', 'approved'], $profile->reviews()->orderBy('id')->pluck('decision')->map->value->all());
        $this->assertSame('Blurry criminal record', $profile->reviews()->orderBy('id')->first()->reason);
    }

    public function test_only_pending_applications_can_be_approved(): void
    {
        $profile = TayarProfile::factory()->approved()->create();

        $this->expectException(ValidationException::class);

        app(ApproveTayarApplicationAction::class)->handle($profile, $this->createStaffUser());
    }
}
