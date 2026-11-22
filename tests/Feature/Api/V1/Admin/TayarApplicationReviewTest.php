<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Tayars\Events\TayarApplicationApproved;
use App\Domains\Tayars\Events\TayarApplicationRejected;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use App\Domains\Tayars\Models\VehicleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TayarApplicationReviewTest extends TestCase
{
    use RefreshDatabase;

    private function pendingApplication(array $attributes = []): TayarProfile
    {
        return TayarProfile::factory()->has(Vehicle::factory())->create($attributes);
    }

    public function test_staff_list_pending_applications_oldest_first(): void
    {
        $older = $this->pendingApplication(['created_at' => now()->subDays(2)]);
        $newer = $this->pendingApplication(['created_at' => now()->subDay()]);
        TayarProfile::factory()->approved()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->getJson(route('api.v1.admin.tayar-applications.index'))
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $older->id)
            ->assertJsonPath('data.1.id', $newer->id)
            ->assertJsonPath('data.0.user.phone', $older->user->phone)
            ->assertJsonPath('data.0.vehicle.vehicle_type.id', $older->vehicle->vehicle_type_id);
    }

    public function test_the_list_filters_by_status_vehicle_type_and_search(): void
    {
        $car = VehicleType::factory()->create();
        $carApplication = TayarProfile::factory()->has(Vehicle::factory()->state(['vehicle_type_id' => $car->id]))->create([
            'user_id' => User::factory()->create(['name' => 'Karim Fathy', 'phone' => '+201001234567']),
        ]);
        $this->pendingApplication();
        $rejected = TayarProfile::factory()->rejected()->create();
        Sanctum::actingAs($this->createStaffUser());

        $ids = fn (array $query): array => $this->getJson(route('api.v1.admin.tayar-applications.index', $query))->assertOk()->json('data.*.id');

        $this->assertSame([$rejected->id], $ids(['status' => 'rejected']));
        $this->assertSame([$carApplication->id], $ids(['vehicle_type_id' => $car->id]));
        $this->assertSame([$carApplication->id], $ids(['search' => 'karim']));
        $this->assertSame([$carApplication->id], $ids(['search' => '01001234567']));
        $this->assertSame([$carApplication->id], $ids(['search' => '0100123']));
    }

    public function test_show_has_every_document_with_signed_urls_and_the_review_history(): void
    {
        Storage::fake('local');
        $profile = $this->pendingApplication();
        $profile->addMedia(UploadedFile::fake()->create('record.pdf', 10, 'application/pdf'))->toMediaCollection('criminal_record');
        $profile->vehicle->addMedia(UploadedFile::fake()->image('car.jpg'))->toMediaCollection('vehicle_photos');
        $reviewer = $this->createStaffUser(StaffRole::Ops);
        $profile->reviews()->create(['decision' => 'rejected', 'reason' => 'Old rejection', 'reviewed_by' => $reviewer->id]);
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $response = $this->getJson(route('api.v1.admin.tayar-applications.show', $profile))
            ->assertOk()
            ->assertJsonPath('data.national_id_number', $profile->national_id_number)
            ->assertJsonPath('data.reviews.0.reason', 'Old rejection')
            ->assertJsonPath('data.reviews.0.reviewed_by.id', $reviewer->id)
            ->assertJsonPath('data.reviewed_by', null);

        $this->assertStringContainsString('signature=', $response->json('data.documents.criminal_record.url'));
        $this->assertStringContainsString('signature=', $response->json('data.vehicle.documents.vehicle_photos.0.url'));
    }

    public function test_ops_approve_a_pending_application(): void
    {
        Event::fake([TayarApplicationApproved::class]);
        $profile = $this->pendingApplication();
        $ops = $this->createStaffUser(StaffRole::Ops);
        Sanctum::actingAs($ops);

        $this->postJson(route('api.v1.admin.tayar-applications.approve', $profile))
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reviewed_by.id', $ops->id)
            ->assertJsonPath('data.vehicle.status', 'approved')
            ->assertJsonPath('data.vehicle.is_active', true)
            ->assertJsonPath('data.reviews.0.decision', 'approved');

        Event::assertDispatched(TayarApplicationApproved::class);
    }

    public function test_reject_needs_a_reason_and_sets_when_to_reapply(): void
    {
        Event::fake([TayarApplicationRejected::class]);
        config(['tayari.tayars.reapply_after_hours' => 24]);
        $profile = $this->pendingApplication();
        Sanctum::actingAs($this->createStaffUser());

        $this->postJson(route('api.v1.admin.tayar-applications.reject', $profile), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        $this->postJson(route('api.v1.admin.tayar-applications.reject', $profile), ['reason' => 'The criminal record is blurry.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'The criminal record is blurry.')
            ->assertJsonPath('data.can_reapply_at', now()->addHours(24)->toIso8601ZuluString())
            ->assertJsonPath('data.vehicle.status', 'rejected');

        Event::assertDispatched(TayarApplicationRejected::class);
    }

    public function test_only_pending_applications_can_be_reviewed(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        Sanctum::actingAs($this->createStaffUser());

        $this->postJson(route('api.v1.admin.tayar-applications.reject', $profile), ['reason' => 'x'], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'Only pending tayar applications can be reviewed.');
    }

    public function test_staff_cannot_review_their_own_application(): void
    {
        $admin = $this->createStaffUser(StaffRole::Admin);
        $profile = $this->pendingApplication(['user_id' => $admin->id]);
        Sanctum::actingAs($admin);

        $this->postJson(route('api.v1.admin.tayar-applications.approve', $profile))->assertForbidden();
    }

    public function test_an_unknown_application_is_404(): void
    {
        Sanctum::actingAs($this->createStaffUser());

        $this->getJson(route('api.v1.admin.tayar-applications.show', 999))->assertNotFound();
    }
}
