<?php

namespace Tests\Feature\Api\V1\Client;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Tayars\Actions\GoOnlineAction;
use App\Domains\Tayars\Events\TayarApplicationSubmitted;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\Vehicle;
use App\Domains\Tayars\Models\VehicleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TayarApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function application(VehicleType $vehicleType, array $overrides = []): array
    {
        return [
            'national_id_number' => '29001011234567',
            'vehicle_type_id' => $vehicleType->id,
            'plate_number' => 'ABC 1234',
            'model' => 'Example Model',
            'color' => 'Red',
            'year' => 2020,
            'national_id_front' => UploadedFile::fake()->image('front.jpg'),
            'national_id_back' => UploadedFile::fake()->image('back.jpg'),
            'criminal_record' => UploadedFile::fake()->create('record.pdf', 10, 'application/pdf'),
            'personal_photo' => UploadedFile::fake()->image('me.jpg'),
            'driving_license' => UploadedFile::fake()->image('license.jpg'),
            'vehicle_license' => UploadedFile::fake()->image('vehicle-license.jpg'),
            'vehicle_photos' => [UploadedFile::fake()->image('car-1.jpg'), UploadedFile::fake()->image('car-2.jpg')],
            ...$overrides,
        ];
    }

    private function actingAsTayar(TayarProfile $profile): void
    {
        Sanctum::actingAs($profile->user);
    }

    public function test_a_client_applies_with_documents_and_the_first_vehicle(): void
    {
        Event::fake([TayarApplicationSubmitted::class]);
        Sanctum::actingAs(User::factory()->business()->create());

        $response = $this->postJson(route('api.v1.client.tayar-profile.store'), $this->application(VehicleType::factory()->create(), [
            'national_id_number' => '٢٩٠٠١٠١١٢٣٤٥٦٧',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.national_id_number', '29001011234567')
            ->assertJsonPath('data.is_online', false)
            ->assertJsonPath('data.vehicle.plate_number', 'ABC 1234')
            ->assertJsonPath('data.vehicle.year', 2020)
            ->assertJsonPath('data.vehicle.status', 'pending')
            ->assertJsonCount(2, 'data.vehicle.documents.vehicle_photos');
        $this->assertStringContainsString('signature=', $response->json('data.documents.criminal_record.url'));
        $this->assertStringContainsString('signature=', $response->json('data.vehicle.documents.vehicle_license.url'));
        // 4 personal documents, 2 licences and 2 vehicle photos.
        $this->assertCount(8, Storage::disk('local')->allFiles(), 'Every document is on the private disk.');
        Event::assertDispatched(TayarApplicationSubmitted::class);
    }

    public function test_a_bicycle_needs_no_plate_and_no_licences(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $application = $this->application(VehicleType::factory()->withoutLicense()->create(), ['plate_number' => null]);
        unset($application['driving_license'], $application['vehicle_license']);

        $this->postJson(route('api.v1.client.tayar-profile.store'), $application)
            ->assertCreated()
            ->assertJsonPath('data.documents.driving_license', null)
            ->assertJsonPath('data.vehicle.documents.vehicle_license', null);
    }

    public function test_the_application_is_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $application = $this->application(VehicleType::factory()->create(), [
            'national_id_number' => '12345',
            'plate_number' => null,
            'year' => 1970,
            'vehicle_photos' => [],
        ]);
        unset($application['criminal_record'], $application['driving_license'], $application['vehicle_license']);

        $this->postJson(route('api.v1.client.tayar-profile.store'), $application)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['national_id_number', 'plate_number', 'year', 'vehicle_photos', 'criminal_record', 'driving_license', 'vehicle_license']);

        $this->postJson(route('api.v1.client.tayar-profile.store'), $this->application(VehicleType::factory()->inactive()->create()))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vehicle_type_id']);

        $this->postJson(route('api.v1.client.tayar-profile.store'), $this->application(VehicleType::factory()->create(), [
            'vehicle_photos' => array_fill(0, 5, UploadedFile::fake()->image('car.jpg')),
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vehicle_photos']);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function nationalIdNumbers(): array
    {
        return [
            'valid (born 1990-01-01)' => ['29001011234567', true],
            'valid (born 2005-12-31)' => ['30512311234567', true],
            'impossible month' => ['29013011234567', false],
            'impossible day' => ['29002301234567', false],
            'future date' => ['39912311234567', false],
        ];
    }

    #[DataProvider('nationalIdNumbers')]
    public function test_the_birth_date_in_the_national_id_number_must_be_real_and_not_in_the_future(string $number, bool $isValid): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('api.v1.client.tayar-profile.store'), $this->application(VehicleType::factory()->create(), ['national_id_number' => $number]));

        $isValid ? $response->assertCreated() : $response->assertUnprocessable()->assertJsonValidationErrors(['national_id_number']);
    }

    public function test_photos_must_be_images(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.client.tayar-profile.store'), $this->application(VehicleType::factory()->create(), [
            'personal_photo' => UploadedFile::fake()->create('me.pdf', 10, 'application/pdf'),
            'vehicle_photos' => [UploadedFile::fake()->create('car.pdf', 10, 'application/pdf')],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['personal_photo', 'vehicle_photos.0']);
    }

    public function test_a_national_id_number_or_a_plate_used_by_another_application_is_rejected(): void
    {
        TayarProfile::factory()->has(Vehicle::factory()->state(['plate_number' => 'ABC 1234']))->create(['national_id_number' => '29001011234567']);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.client.tayar-profile.store'), $this->application(VehicleType::factory()->create()), ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.national_id_number.0', 'This national ID number is already used in another tayar application.')
            ->assertJsonValidationErrors(['plate_number']);
    }

    public function test_a_second_application_is_forbidden(): void
    {
        $profile = TayarProfile::factory()->create();
        $this->actingAsTayar($profile);

        $this->postJson(route('api.v1.client.tayar-profile.store'), $this->application(VehicleType::factory()->create(), ['national_id_number' => '29001011234599']), ['Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'You have already applied to be a tayar.');
    }

    public function test_staff_cannot_apply(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->postJson(route('api.v1.client.tayar-profile.store'), $this->application(VehicleType::factory()->create()))->assertForbidden();
    }

    public function test_the_status_shows_the_rejection_reason_and_when_to_reapply(): void
    {
        $profile = TayarProfile::factory()->rejected()->create();
        $this->actingAsTayar($profile);

        $this->getJson(route('api.v1.client.tayar-profile.show'))
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'The criminal record is not readable.')
            ->assertJsonPath('data.can_reapply_at', $profile->can_reapply_at->toIso8601ZuluString())
            ->assertJsonMissingPath('data.reviewed_by');
    }

    public function test_the_status_is_404_before_applying(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.client.tayar-profile.show'))->assertNotFound();
    }

    public function test_a_rejected_applicant_waits_before_changing_the_application(): void
    {
        $profile = TayarProfile::factory()->rejected()->create();
        $this->actingAsTayar($profile);

        $this->patchJson(route('api.v1.client.tayar-profile.update'), ['national_id_number' => '29001011234599'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->travelTo($profile->can_reapply_at->addMinute());

        $this->patchJson(route('api.v1.client.tayar-profile.update'), ['national_id_number' => '29001011234599'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.rejection_reason', null);
    }

    public function test_changing_the_national_id_number_sends_an_approved_tayar_back_to_review_and_offline(): void
    {
        $profile = app(GoOnlineAction::class)->handle(TayarProfile::factory()->approved()->create(), 30.0444, 31.2357, $this->zoneWorkSessionSettings());
        $this->actingAsTayar($profile);

        $this->patchJson(route('api.v1.client.tayar-profile.update'), ['national_id_number' => '29001011234599'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.is_online', false)
            ->assertJsonPath('data.vehicle.status', 'pending')
            ->assertJsonPath('data.vehicle.is_active', false);
    }

    public function test_saving_the_same_national_id_number_keeps_the_approval(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $this->actingAsTayar($profile);

        $this->patchJson(route('api.v1.client.tayar-profile.update'), ['national_id_number' => $profile->national_id_number])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_replacing_a_document_sends_the_application_back_to_review(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $this->actingAsTayar($profile);

        $response = $this->postJson(route('api.v1.client.tayar-profile.documents.store', 'criminal_record'), ['file' => UploadedFile::fake()->create('record.pdf', 10, 'application/pdf')])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->assertStringContainsString('signature=', $response->json('data.documents.criminal_record.url'));
        $this->postJson(route('api.v1.client.tayar-profile.documents.store', 'passport'), ['file' => UploadedFile::fake()->image('x.jpg')])->assertNotFound();
    }

    public function test_vehicle_photos_are_replaced_all_at_once(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $profile->vehicle->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('vehicle_photos');
        $this->actingAsTayar($profile);

        $this->postJson(route('api.v1.client.tayar-profile.vehicle.documents.store', 'vehicle_photos'), [
            'files' => [UploadedFile::fake()->image('new-1.jpg'), UploadedFile::fake()->image('new-2.jpg'), UploadedFile::fake()->image('new-3.jpg')],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonCount(3, 'data.vehicle.documents.vehicle_photos')
            ->assertJsonPath('data.vehicle.documents.vehicle_photos.0.file_name', 'new-1.jpg');

        $this->postJson(route('api.v1.client.tayar-profile.vehicle.documents.store', 'vehicle_photos'), ['files' => array_fill(0, 5, UploadedFile::fake()->image('car.jpg'))])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['files']);
    }

    public function test_changing_the_plate_sends_the_application_back_to_review_but_the_colour_does_not(): void
    {
        $profile = TayarProfile::factory()->approved()->create();
        $this->actingAsTayar($profile);

        $this->patchJson(route('api.v1.client.tayar-profile.vehicle.update'), ['color' => 'Blue'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.vehicle.color', 'Blue');

        $this->patchJson(route('api.v1.client.tayar-profile.vehicle.update'), ['plate_number' => 'XYZ 9876'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.vehicle.plate_number', 'XYZ 9876');

        $this->patchJson(route('api.v1.client.tayar-profile.vehicle.update'), ['plate_number' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plate_number']);
    }

    public function test_switching_to_a_vehicle_type_that_needs_a_licence_needs_the_licences_uploaded_first(): void
    {
        $profile = TayarProfile::factory()->create();
        $profile->vehicles()->create(['vehicle_type_id' => VehicleType::factory()->withoutLicense()->create()->id]);
        $this->actingAsTayar($profile);

        $this->patchJson(route('api.v1.client.tayar-profile.vehicle.update'), ['vehicle_type_id' => VehicleType::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plate_number', 'driving_license', 'vehicle_license']);
    }
}
