<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Events\NationalIdRejected;
use App\Domains\Accounts\Events\NationalIdVerified;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NationalIdReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function clientWithNationalId(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->addMedia(UploadedFile::fake()->create('front.pdf', 10, 'application/pdf'))->toMediaCollection('national_id_front');
        $user->addMedia(UploadedFile::fake()->create('back.pdf', 10, 'application/pdf'))->toMediaCollection('national_id_back');

        return $user;
    }

    public function test_staff_list_only_pending_national_ids(): void
    {
        $pending = $this->clientWithNationalId();
        $this->clientWithNationalId(['id_verified_at' => now(), 'id_reviewed_at' => now()]);
        User::factory()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $response = $this->getJson(route('api.v1.admin.national-ids.index'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $pending->id)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('meta.total', 1);
        $this->assertStringContainsString('signature=', $response->json('data.0.front.url'));
    }

    public function test_an_admin_verifies_a_national_id(): void
    {
        Event::fake([NationalIdVerified::class]);
        $client = $this->clientWithNationalId();
        $admin = $this->createStaffUser();
        Sanctum::actingAs($admin);

        $this->postJson(route('api.v1.admin.national-ids.verify', $client))
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $client->refresh();
        $this->assertNotNull($client->id_verified_at);
        $this->assertSame($admin->id, $client->id_reviewed_by);
        $this->assertNotNull($client->id_reviewed_at);
        Event::assertDispatched(NationalIdVerified::class);
    }

    public function test_an_admin_rejects_a_national_id_with_a_reason(): void
    {
        Event::fake([NationalIdRejected::class]);
        $client = $this->clientWithNationalId();
        $admin = $this->createStaffUser();
        Sanctum::actingAs($admin);

        $this->postJson(route('api.v1.admin.national-ids.reject', $client), ['reason' => 'Expired card'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Expired card');

        $client->refresh();
        $this->assertNull($client->id_verified_at);
        $this->assertSame($admin->id, $client->id_reviewed_by);
        Event::assertDispatched(NationalIdRejected::class);
    }

    public function test_a_user_without_an_upload_cannot_be_verified(): void
    {
        $client = User::factory()->create();
        Sanctum::actingAs($this->createStaffUser());

        $this->postJson(route('api.v1.admin.national-ids.verify', $client))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_support_can_view_but_not_review(): void
    {
        $client = $this->clientWithNationalId();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->postJson(route('api.v1.admin.national-ids.verify', $client))->assertForbidden();
        $this->assertNull($client->refresh()->id_verified_at);
    }
}
