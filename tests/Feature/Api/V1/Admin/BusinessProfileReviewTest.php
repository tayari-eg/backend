<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Events\BusinessProfileApproved;
use App\Domains\Accounts\Events\BusinessProfileRejected;
use App\Domains\Accounts\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessProfileReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_list_pending_profiles_oldest_first_with_signed_document_urls(): void
    {
        Storage::fake('local');
        $older = BusinessProfile::factory()->create(['created_at' => now()->subDays(2)]);
        $older->addMedia(UploadedFile::fake()->create('front.pdf', 10, 'application/pdf'))->toMediaCollection('national_id_front');
        $newer = BusinessProfile::factory()->create(['created_at' => now()->subDay()]);
        BusinessProfile::factory()->approved()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $response = $this->getJson(route('api.v1.admin.business-profiles.index'));

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $older->id)
            ->assertJsonPath('data.1.id', $newer->id)
            ->assertJsonPath('data.0.user.phone', $older->user->phone)
            ->assertJsonPath('meta.total', 2);
        $this->assertStringContainsString('signature=', $response->json('data.0.documents.national_id_front.url'));
    }

    public function test_staff_open_one_profile_with_the_reviewer_and_signed_document_urls(): void
    {
        Storage::fake('local');
        $reviewer = $this->createStaffUser(StaffRole::Ops);
        $profile = BusinessProfile::factory()->approved($reviewer)->create();
        $profile->addMedia(UploadedFile::fake()->create('front.pdf', 10, 'application/pdf'))->toMediaCollection('national_id_front');
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $response = $this->getJson(route('api.v1.admin.business-profiles.show', $profile))
            ->assertOk()
            ->assertJsonPath('data.id', $profile->id)
            ->assertJsonPath('data.user.phone', $profile->user->phone)
            ->assertJsonPath('data.business_type.id', $profile->business_type_id)
            ->assertJsonPath('data.reviewed_by', ['id' => $reviewer->id, 'name' => $reviewer->name]);

        $this->assertStringContainsString('signature=', $response->json('data.documents.national_id_front.url'));
    }

    public function test_a_profile_that_was_never_reviewed_has_no_reviewer_and_an_unknown_id_is_404(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->getJson(route('api.v1.admin.business-profiles.show', $profile))
            ->assertOk()
            ->assertJsonPath('data.reviewed_by', null);

        $this->getJson(route('api.v1.admin.business-profiles.show', 999))->assertNotFound();
    }

    public function test_the_client_profile_never_shows_the_reviewer(): void
    {
        $profile = BusinessProfile::factory()->approved($this->createStaffUser())->create();
        Sanctum::actingAs($profile->user);

        $this->getJson(route('api.v1.client.business-profile.show'))
            ->assertOk()
            ->assertJsonMissingPath('data.reviewed_by');
    }

    public function test_the_list_can_be_filtered_and_is_paginated(): void
    {
        BusinessProfile::factory()->count(3)->rejected()->create();
        Sanctum::actingAs($this->createStaffUser());

        $this->getJson(route('api.v1.admin.business-profiles.index', ['status' => 'rejected', 'per_page' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_an_admin_approves_a_pending_profile(): void
    {
        Event::fake([BusinessProfileApproved::class]);
        $profile = BusinessProfile::factory()->create();
        $admin = $this->createStaffUser(StaffRole::Admin);
        Sanctum::actingAs($admin);

        $this->postJson(route('api.v1.admin.business-profiles.approve', $profile))
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'approved');

        $profile->refresh();
        $this->assertSame(VerificationStatus::Approved, $profile->verification_status);
        $this->assertSame($admin->id, $profile->reviewed_by);
        $this->assertNotNull($profile->reviewed_at);
        Event::assertDispatched(BusinessProfileApproved::class);
    }

    public function test_an_admin_rejects_a_pending_profile_with_a_reason(): void
    {
        Event::fake([BusinessProfileRejected::class]);
        $profile = BusinessProfile::factory()->create();
        $ops = $this->createStaffUser(StaffRole::Ops);
        Sanctum::actingAs($ops);

        $this->postJson(route('api.v1.admin.business-profiles.reject', $profile), ['reason' => 'National ID photo is unclear'])
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'National ID photo is unclear');

        $profile->refresh();
        $this->assertSame($ops->id, $profile->reviewed_by);
        $this->assertNotNull($profile->reviewed_at);
        Event::assertDispatched(BusinessProfileRejected::class);
    }

    public function test_rejecting_needs_a_reason(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($this->createStaffUser());

        $this->postJson(route('api.v1.admin.business-profiles.reject', $profile))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_only_pending_profiles_can_be_reviewed(): void
    {
        $profile = BusinessProfile::factory()->approved()->create();
        Sanctum::actingAs($this->createStaffUser());

        $this->postJson(route('api.v1.admin.business-profiles.reject', $profile), ['reason' => 'Changed my mind'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_support_can_view_but_not_review(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->postJson(route('api.v1.admin.business-profiles.approve', $profile))->assertForbidden();
        $this->assertSame(VerificationStatus::Pending, $profile->refresh()->verification_status);
    }
}
