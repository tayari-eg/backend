<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\UserStatus;
use App\Domains\Accounts\Models\BusinessLink;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createStaffUser(StaffRole::Admin);
        Sanctum::actingAs($this->admin);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function list(array $query = []): TestResponse
    {
        return $this->getJson(route('api.v1.admin.users.index', $query))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<int>
     */
    private function listedIds(array $query = []): array
    {
        return $this->list($query)->json('data.*.id');
    }

    private function uploadNationalId(User $user): void
    {
        $user->addMedia(UploadedFile::fake()->image('front.jpg'))->toMediaCollection('national_id_front');
        $user->addMedia(UploadedFile::fake()->image('back.jpg'))->toMediaCollection('national_id_back');
    }

    public function test_the_list_shows_customers_newest_first_and_never_staff(): void
    {
        $older = User::factory()->create();
        $newer = User::factory()->business()->create();
        $this->createStaffUser(StaffRole::Support);

        $this->list()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('data.0.account_type', 'business')
            ->assertJsonPath('data.0.national_id.status', null)
            ->assertJsonPath('data.0.business_profile', null);
    }

    public function test_search_by_a_full_phone_number_in_any_format_matches_exactly(): void
    {
        $user = User::factory()->create(['phone' => '+201001234567']);
        User::factory()->create(['phone' => '+201001234568']);

        foreach (['01001234567', '+201001234567', '0100 123 4567', '٠١٠٠١٢٣٤٥٦٧'] as $search) {
            $this->assertSame([$user->id], $this->listedIds(['search' => $search]), "Search [{$search}] failed.");
        }
    }

    public function test_search_by_part_of_a_phone_number_drops_a_leading_zero(): void
    {
        $first = User::factory()->create(['phone' => '+201001234567']);
        $second = User::factory()->create(['phone' => '+201001239999']);
        User::factory()->create(['phone' => '+201559876543']);

        $this->assertSame([$second->id, $first->id], $this->listedIds(['search' => '0100123']));
        $this->assertSame([$first->id], $this->listedIds(['search' => '٤٥٦٧']));
    }

    public function test_search_by_name_when_the_search_is_not_a_phone(): void
    {
        $user = User::factory()->create(['name' => 'Mona Hassan']);
        User::factory()->create(['name' => 'Omar Ali']);
        User::factory()->create(['name' => 'Number 123', 'phone' => '+201001230000']);

        $this->assertSame([$user->id], $this->listedIds(['search' => 'hassan']));
        // Fewer than four digits is a name search, not a phone search.
        $this->assertCount(1, $this->listedIds(['search' => '123']));
    }

    public function test_the_list_filters_by_account_type_status_and_phone_verification(): void
    {
        $business = User::factory()->business()->create();
        $blocked = User::factory()->blocked()->create();
        $unverified = User::factory()->unverifiedPhone()->create();

        $this->assertSame([$business->id], $this->listedIds(['account_type' => 'business']));
        $this->assertSame([$blocked->id], $this->listedIds(['status' => 'blocked']));
        $this->assertSame([$unverified->id], $this->listedIds(['phone_verified' => 'false']));
        $this->assertSame([$blocked->id, $business->id], $this->listedIds(['phone_verified' => 'true']));
    }

    public function test_the_list_filters_by_national_id_status(): void
    {
        Storage::fake('local');
        $none = User::factory()->create();
        $pending = User::factory()->create();
        $this->uploadNationalId($pending);
        $approved = User::factory()->create(['id_verified_at' => now(), 'id_reviewed_at' => now()]);
        $rejected = User::factory()->create(['id_reviewed_at' => now(), 'id_rejection_reason' => 'Blurry']);

        $this->assertSame([$none->id], $this->listedIds(['national_id_status' => 'none']));
        $this->assertSame([$pending->id], $this->listedIds(['national_id_status' => 'pending']));
        $this->assertSame([$approved->id], $this->listedIds(['national_id_status' => 'approved']));
        $this->assertSame([$rejected->id], $this->listedIds(['national_id_status' => 'rejected']));
    }

    public function test_the_list_filters_by_business_profile_status(): void
    {
        $pending = BusinessProfile::factory()->create();
        $approved = BusinessProfile::factory()->approved()->create();

        $this->assertSame([$pending->user_id], $this->listedIds(['business_profile_status' => 'pending']));
        $this->assertSame([$approved->user_id], $this->listedIds(['business_profile_status' => 'approved']));
    }

    public function test_invalid_filters_are_422(): void
    {
        $this->getJson(route('api.v1.admin.users.index', ['account_type' => 'tayar', 'national_id_status' => 'maybe', 'phone_verified' => 'yes']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['account_type', 'national_id_status', 'phone_verified']);
    }

    public function test_show_returns_the_national_id_and_the_business_profile_with_signed_urls(): void
    {
        Storage::fake('local');
        $profile = BusinessProfile::factory()->create();
        BusinessLink::factory()->create(['business_profile_id' => $profile->id]);
        $profile->addMedia(UploadedFile::fake()->create('tax.pdf', 10, 'application/pdf'))->toMediaCollection('tax_card');
        $this->uploadNationalId($profile->user);
        $profile->user->forceFill(['status' => UserStatus::Blocked, 'blocked_reason' => 'Fraud'])->save();

        $response = $this->getJson(route('api.v1.admin.users.show', $profile->user))
            ->assertOk()
            ->assertJsonPath('data.id', $profile->user_id)
            ->assertJsonPath('data.phone', $profile->user->phone)
            ->assertJsonPath('data.status', 'blocked')
            ->assertJsonPath('data.blocked_reason', 'Fraud')
            ->assertJsonPath('data.national_id.status', 'pending')
            ->assertJsonPath('data.business_profile.id', $profile->id)
            ->assertJsonCount(1, 'data.business_profile.links');

        $this->assertStringContainsString('signature=', $response->json('data.national_id.front.url'));
        $this->assertStringContainsString('signature=', $response->json('data.business_profile.documents.tax_card.url'));
    }

    public function test_staff_accounts_are_404_on_the_customer_routes(): void
    {
        $staff = $this->createStaffUser(StaffRole::Support);

        $this->getJson(route('api.v1.admin.users.show', $staff))->assertNotFound();
        $this->postJson(route('api.v1.admin.users.block', $staff), ['reason' => 'x'])->assertNotFound();
        $this->postJson(route('api.v1.admin.users.unblock', $staff))->assertNotFound();
    }

    public function test_block_needs_a_reason_and_signs_the_user_out_of_the_mobile_apps(): void
    {
        $user = User::factory()->create();
        $user->createToken('phone');

        $this->postJson(route('api.v1.admin.users.block', $user), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);

        $this->postJson(route('api.v1.admin.users.block', $user), ['reason' => 'Fraud reports'])
            ->assertOk()
            ->assertJsonPath('data.status', 'blocked')
            ->assertJsonPath('data.blocked_reason', 'Fraud reports');

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_blocking_again_updates_the_reason_and_unblock_always_succeeds(): void
    {
        $user = User::factory()->blocked()->create();

        $this->postJson(route('api.v1.admin.users.block', $user), ['reason' => 'Second reason'])
            ->assertOk()
            ->assertJsonPath('data.blocked_reason', 'Second reason');

        $this->postJson(route('api.v1.admin.users.unblock', $user))
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.blocked_reason', null);

        $this->postJson(route('api.v1.admin.users.unblock', $user))
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    }
}
