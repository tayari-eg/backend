<?php

namespace Tests\Feature\Api\V1\Client;

use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Events\BusinessProfileSubmitted;
use App\Domains\Accounts\Models\BusinessLink;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Accounts\Models\BusinessType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function document(string $name = 'doc.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 100, 'application/pdf');
    }

    /**
     * @return array<string, mixed>
     */
    private function createPayload(BusinessType $businessType): array
    {
        return [
            'business_type_id' => $businessType->id,
            'business_name' => 'Koshary Corner',
            'description' => 'Koshary and desserts',
            'avg_daily_orders' => 40,
            'tax_registration_number' => '123-456-789',
            'links' => [
                ['type' => 'facebook', 'url' => 'https://facebook.com/koshary'],
                ['type' => 'website', 'url' => 'https://koshary.example'],
            ],
            'national_id_front' => $this->document('front.pdf'),
            'national_id_back' => $this->document('back.pdf'),
            'tax_card' => $this->document('tax-card.pdf'),
        ];
    }

    public function test_a_business_account_creates_a_pending_profile_with_private_documents(): void
    {
        Event::fake([BusinessProfileSubmitted::class]);
        $user = User::factory()->business()->create();
        Sanctum::actingAs($user);

        $response = $this->post(route('api.v1.client.business-profile.store'), $this->createPayload(BusinessType::factory()->create()), ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.business_name', 'Koshary Corner')
            ->assertJsonPath('data.verification_status', 'pending')
            ->assertJsonCount(2, 'data.links')
            ->assertJsonPath('data.documents.tax_register', null);
        $this->assertNotNull($response->json('data.documents.national_id_front.url'));

        $profile = BusinessProfile::query()->sole();
        $this->assertTrue($profile->user->is($user));
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        foreach (['national_id_front', 'national_id_back', 'tax_card'] as $collection) {
            $media = $profile->getFirstMedia($collection);
            $this->assertSame('local', $media->disk);
            Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
        }
        Event::assertDispatched(BusinessProfileSubmitted::class);
    }

    public function test_the_national_id_documents_are_required(): void
    {
        Sanctum::actingAs(User::factory()->business()->create());
        $payload = $this->createPayload(BusinessType::factory()->create());
        unset($payload['national_id_front'], $payload['national_id_back']);

        $this->post(route('api.v1.client.business-profile.store'), $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['national_id_front', 'national_id_back']);
    }

    public function test_an_inactive_business_type_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->business()->create());

        $this->post(route('api.v1.client.business-profile.store'), $this->createPayload(BusinessType::factory()->inactive()->create()), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['business_type_id']);
    }

    public function test_an_individual_account_cannot_create_a_business_profile(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->post(route('api.v1.client.business-profile.store'), $this->createPayload(BusinessType::factory()->create()), ['Accept' => 'application/json', 'Accept-Language' => 'en'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Only business accounts can have a business profile.');
    }

    public function test_a_second_profile_cannot_be_created(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($profile->user);

        $this->post(route('api.v1.client.business-profile.store'), $this->createPayload(BusinessType::factory()->create()), ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_the_owner_can_view_the_profile(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($profile->user);

        $this->getJson(route('api.v1.client.business-profile.show'))
            ->assertOk()
            ->assertJsonPath('data.id', $profile->id);
    }

    public function test_show_is_404_without_a_profile(): void
    {
        Sanctum::actingAs(User::factory()->business()->create());

        $this->getJson(route('api.v1.client.business-profile.show'))->assertNotFound();
    }

    /**
     * @return array<string, array{\Closure(): array<string, mixed>}>
     */
    public static function reviewedChanges(): array
    {
        return [
            'business name' => [fn (): array => ['business_name' => 'New Name']],
            'business type' => [fn (): array => ['business_type_id' => BusinessType::factory()->create()->id]],
            'tax registration number' => [fn (): array => ['tax_registration_number' => '999-999-999']],
        ];
    }

    #[DataProvider('reviewedChanges')]
    public function test_changing_a_reviewed_field_sends_an_approved_profile_back_to_pending(\Closure $change): void
    {
        Event::fake([BusinessProfileSubmitted::class]);
        $profile = BusinessProfile::factory()->approved(User::factory()->create())->create();
        Sanctum::actingAs($profile->user);

        $this->patchJson(route('api.v1.client.business-profile.update'), $change())
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'pending');

        $profile->refresh();
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertNull($profile->rejection_reason);
        $this->assertNull($profile->reviewed_by);
        $this->assertNull($profile->reviewed_at);
        Event::assertDispatched(BusinessProfileSubmitted::class);
    }

    public function test_changing_a_reviewed_field_sends_a_rejected_profile_back_to_pending_and_clears_the_rejection(): void
    {
        $profile = BusinessProfile::factory()->rejected(User::factory()->create())->create();
        Sanctum::actingAs($profile->user);

        $this->patchJson(route('api.v1.client.business-profile.update'), ['business_name' => 'Fixed Name'])
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'pending')
            ->assertJsonPath('data.rejection_reason', null);

        $profile->refresh();
        $this->assertSame(VerificationStatus::Pending, $profile->verification_status);
        $this->assertNull($profile->rejection_reason);
        $this->assertNull($profile->reviewed_by);
    }

    public function test_description_daily_orders_and_links_change_without_a_new_review(): void
    {
        Event::fake([BusinessProfileSubmitted::class]);
        $reviewer = User::factory()->create();
        $profile = BusinessProfile::factory()->approved($reviewer)->create();
        BusinessLink::factory()->for($profile)->create(['type' => 'website', 'url' => 'https://old.example']);
        Sanctum::actingAs($profile->user);

        $this->patchJson(route('api.v1.client.business-profile.update'), [
            'description' => 'Now also breakfast',
            'avg_daily_orders' => 80,
            'business_name' => $profile->business_name,
            'links' => [['type' => 'instagram', 'url' => 'https://instagram.com/koshary']],
        ])->assertOk()
            ->assertJsonPath('data.verification_status', 'approved')
            ->assertJsonPath('data.description', 'Now also breakfast')
            ->assertJsonPath('data.links.0.type', 'instagram')
            ->assertJsonCount(1, 'data.links');

        $profile->refresh();
        $this->assertSame(VerificationStatus::Approved, $profile->verification_status);
        $this->assertSame($reviewer->id, $profile->reviewed_by);
        $this->assertSame(80, $profile->avg_daily_orders);
        $this->assertDatabaseMissing('business_links', ['url' => 'https://old.example']);
        Event::assertNotDispatched(BusinessProfileSubmitted::class);
    }

    public function test_replacing_a_document_sends_an_approved_profile_back_to_pending(): void
    {
        $profile = BusinessProfile::factory()->approved()->create();
        $profile->addMedia($this->document('old-front.pdf'))->toMediaCollection('national_id_front');
        Sanctum::actingAs($profile->user);

        $this->post(route('api.v1.client.business-profile.documents.store', ['collection' => 'national_id_front']), ['file' => $this->document('new-front.pdf')], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'pending')
            ->assertJsonPath('data.documents.national_id_front.file_name', 'new-front.pdf');

        $this->assertSame(1, $profile->refresh()->getMedia('national_id_front')->count());
    }

    public function test_an_unknown_document_collection_is_404(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($profile->user);

        $this->post(route('api.v1.client.business-profile.documents.store', ['collection' => 'selfie']), ['file' => $this->document()], ['Accept' => 'application/json'])
            ->assertNotFound();
    }

    public function test_documents_must_be_images_or_pdfs(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($profile->user);

        $this->post(route('api.v1.client.business-profile.documents.store', ['collection' => 'tax_card']), ['file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }
}
