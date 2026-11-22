<?php

namespace Tests\Feature\Domains\Tayars;

use App\Domains\Tayars\Actions\ApproveTayarApplicationAction;
use App\Domains\Tayars\Actions\RejectTayarApplicationAction;
use App\Domains\Tayars\Actions\ReplaceTayarDocumentAction;
use App\Domains\Tayars\Actions\ReplaceVehicleDocumentAction;
use App\Domains\Tayars\Actions\UpdateTayarApplicationAction;
use App\Domains\Tayars\Actions\UpdateTayarVehicleAction;
use App\Domains\Tayars\Enums\TayarDocument;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Enums\VehicleDocument;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A review that lands after the applicant's screen loaded the application, but before the edit is saved.
 */
class TayarApplicationEditActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /**
     * Each edit, run against the applicant's (stale) copy of the profile.
     *
     * @return array<string, array{Closure(TayarProfile): mixed}>
     */
    public static function edits(): array
    {
        return [
            'update the national ID number' => [fn (TayarProfile $profile) => app(UpdateTayarApplicationAction::class)
                ->handle($profile, ['national_id_number' => '29001011234599'])],
            'update the plate' => [fn (TayarProfile $profile) => app(UpdateTayarVehicleAction::class)
                ->handle($profile->vehicle, ['plate_number' => 'NEW 1234'])],
            'replace a document' => [fn (TayarProfile $profile) => app(ReplaceTayarDocumentAction::class)
                ->handle($profile, TayarDocument::CriminalRecord, UploadedFile::fake()->create('record.pdf', 10, 'application/pdf'))],
            'replace the vehicle photos' => [fn (TayarProfile $profile) => app(ReplaceVehicleDocumentAction::class)
                ->handle($profile->vehicle, VehicleDocument::VehiclePhotos, [UploadedFile::fake()->image('car.jpg')])],
        ];
    }

    /**
     * @param  Closure(TayarProfile): mixed  $edit
     */
    #[DataProvider('edits')]
    public function test_an_approval_that_lands_before_the_edit_still_sends_the_application_back_to_review(Closure $edit): void
    {
        $stale = TayarProfile::factory()->has(Vehicle::factory())->create();
        $stale->load('vehicle');

        app(ApproveTayarApplicationAction::class)->handle(TayarProfile::query()->find($stale->id), $this->createStaffUser());
        $edit($stale);

        $fresh = $stale->fresh();
        $this->assertSame(TayarStatus::Pending, $fresh->status);
        $this->assertNull($fresh->reviewed_at);
        $this->assertFalse($fresh->vehicle->is_active);
    }

    /**
     * @param  Closure(TayarProfile): mixed  $edit
     */
    #[DataProvider('edits')]
    public function test_a_rejection_that_lands_before_the_edit_enforces_the_wait(Closure $edit): void
    {
        $stale = TayarProfile::factory()->has(Vehicle::factory())->create();
        $stale->load('vehicle');

        app(RejectTayarApplicationAction::class)->handle(TayarProfile::query()->find($stale->id), $this->createStaffUser(), 'Blurry record');

        try {
            $edit($stale);
            $this->fail('An edit during the reapply wait was expected to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $fresh = $stale->fresh();
        $this->assertSame(TayarStatus::Rejected, $fresh->status);
        $this->assertSame('Blurry record', $fresh->rejection_reason);
        $this->assertSame([], Storage::disk('local')->allFiles(), 'No file is stored during the wait.');
    }

    public function test_a_plate_changed_in_the_database_after_loading_is_compared_from_the_locked_row(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $staleVehicle = $profile->vehicle;
        $originalPlate = $staleVehicle->plate_number;
        Vehicle::query()->whereKey($staleVehicle->id)->update(['plate_number' => 'MID 5555']);

        // On the stale copy the original plate looks unchanged; on the locked row it is a change back.
        app(UpdateTayarVehicleAction::class)->handle($staleVehicle, ['plate_number' => $originalPlate]);

        $this->assertSame($originalPlate, $staleVehicle->plate_number);
        $this->assertSame($originalPlate, Vehicle::query()->find($staleVehicle->id)->plate_number);
        $this->assertSame(TayarStatus::Pending, $profile->fresh()->status);
    }

    public function test_a_national_id_number_taken_after_validation_is_reported_on_the_number(): void
    {
        $profile = TayarProfile::factory()->has(Vehicle::factory())->create();
        // Saved by another application after this request's unique rule passed.
        TayarProfile::factory()->create(['national_id_number' => '29001011234599']);

        try {
            app(UpdateTayarApplicationAction::class)->handle($profile, ['national_id_number' => '29001011234599']);
            $this->fail('A national ID number taken by another application was expected to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('tayars.national_id_number_taken')], $exception->errors()['national_id_number']);
        }

        $this->assertNotSame('29001011234599', $profile->fresh()->national_id_number);
    }

    public function test_a_plate_taken_after_validation_is_reported_on_the_plate(): void
    {
        $profile = TayarProfile::factory()->has(Vehicle::factory())->create();
        $profile->load('vehicle');
        // Saved by another vehicle after this request's unique rule passed.
        Vehicle::factory()->create(['plate_number' => 'TAKEN 1']);

        try {
            app(UpdateTayarVehicleAction::class)->handle($profile->vehicle, ['plate_number' => 'TAKEN 1']);
            $this->fail('A plate taken by another vehicle was expected to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('validation.unique', ['attribute' => __('validation.attributes.plate_number')])], $exception->errors()['plate_number']);
            $this->assertArrayNotHasKey('national_id_number', $exception->errors());
        }

        $this->assertNotSame('TAKEN 1', $profile->vehicle->fresh()->plate_number);
    }
}
