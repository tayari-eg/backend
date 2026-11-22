<?php

namespace Tests\Feature\Domains\Tayars;

use App\Domains\Tayars\Actions\SubmitTayarApplicationAction;
use App\Domains\Tayars\Events\TayarApplicationSubmitted;
use App\Domains\Tayars\Models\Vehicle;
use App\Domains\Tayars\Models\VehicleType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Tests\TestCase;

class SubmitTayarApplicationActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, UploadedFile>
     */
    private function documents(): array
    {
        return [
            'national_id_front' => UploadedFile::fake()->image('front.jpg'),
            'national_id_back' => UploadedFile::fake()->image('back.jpg'),
            'criminal_record' => UploadedFile::fake()->create('record.pdf', 10, 'application/pdf'),
            'personal_photo' => UploadedFile::fake()->image('me.jpg'),
        ];
    }

    public function test_a_failed_vehicle_photo_removes_the_files_already_stored_the_vehicle_and_the_profile(): void
    {
        Storage::fake('local');
        Event::fake([TayarApplicationSubmitted::class]);
        $brokenPhoto = UploadedFile::fake()->image('photo.jpg');
        unlink($brokenPhoto->getPathname());

        try {
            app(SubmitTayarApplicationAction::class)->handle(
                User::factory()->create(),
                '29001011234567',
                ['vehicle_type_id' => VehicleType::factory()->withoutLicense()->create()->id],
                $this->documents(),
                ['vehicle_license' => null, 'vehicle_photos' => [$brokenPhoto]],
            );
            $this->fail('Storing the vehicle photo was expected to fail.');
        } catch (FileDoesNotExist) {
            // Expected: the photo file is missing.
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('tayar_profiles', 0);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('media', 0);
        Event::assertNotDispatched(TayarApplicationSubmitted::class);
    }

    public function test_a_plate_taken_at_the_same_moment_is_reported_on_the_plate(): void
    {
        Storage::fake('local');
        Vehicle::factory()->create(['plate_number' => 'ABC 1234']);
        $user = User::factory()->create();

        // The request rule passed before the other vehicle was saved, so only the unique index stops it.
        try {
            app(SubmitTayarApplicationAction::class)->handle(
                $user,
                '29001011234567',
                ['vehicle_type_id' => VehicleType::factory()->create()->id, 'plate_number' => 'ABC 1234'],
                [],
                ['vehicle_photos' => []],
            );
            $this->fail('A duplicate plate was expected to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame([__('validation.unique', ['attribute' => __('validation.attributes.plate_number')])], $exception->errors()['plate_number']);
            $this->assertArrayNotHasKey('national_id_number', $exception->errors());
        }

        $this->assertFalse($user->tayarProfile()->exists(), 'The new application is rolled back.');
    }

    public function test_a_double_submit_answers_like_a_second_application(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $vehicleTypeId = VehicleType::factory()->withoutLicense()->create()->id;
        app(SubmitTayarApplicationAction::class)->handle($user, '29001011234567', ['vehicle_type_id' => $vehicleTypeId], [], ['vehicle_photos' => []]);

        try {
            app(SubmitTayarApplicationAction::class)->handle($user, '29001011234568', ['vehicle_type_id' => $vehicleTypeId], [], ['vehicle_photos' => []]);
            $this->fail('A second application for the same user was expected to fail.');
        } catch (AuthorizationException $exception) {
            $this->assertSame(__('tayars.application_exists'), $exception->getMessage());
        }

        $this->assertDatabaseCount('tayar_profiles', 1);
    }
}
