<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domains\Accounts\Enums\RegisterAs;
use App\Domains\Accounts\Enums\StaffPermission;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_logged_in_user_without_secrets(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.phone', $user->phone)
            ->assertJsonPath('data.national_id.status', null)
            ->assertJsonPath('data.business_profile', null)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token')
            ->assertJsonPath('data.roles', [])
            ->assertJsonPath('data.permissions', []);
    }

    public function test_it_includes_the_business_profile(): void
    {
        $profile = BusinessProfile::factory()->create();
        Sanctum::actingAs($profile->user);

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.business_profile.id', $profile->id)
            ->assertJsonPath('data.business_profile.verification_status', 'pending');
    }

    public function test_staff_see_their_roles_and_permissions(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Support));

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.roles', ['support'])
            ->assertJsonPath('data.permissions', ['business_profiles.view', 'national_ids.view', 'business_types.view', 'users.view', 'tayars.view', 'vehicle_types.view', 'categories.view', 'modes.view', 'areas.view', 'pricing_rules.view', 'orders.view', 'wallets.view', 'ratings.view', 'ranks.view']);
    }

    public function test_a_super_admin_gets_every_permission(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::SuperAdmin));

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.roles', ['super_admin'])
            ->assertJsonPath('data.permissions', array_column(StaffPermission::cases(), 'value'));
    }

    public function test_it_returns_the_last_mode_and_the_tayar_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.last_mode', 'client')
            ->assertJsonPath('data.tayar_profile', null);

        $profile = TayarProfile::factory()->approved()->onlineAt(30.0444, 31.2357)->create(['user_id' => $user->id]);
        $user->forceFill(['last_mode' => 'tayar'])->save();

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.last_mode', 'tayar')
            ->assertJsonPath('data.tayar_profile', ['id' => $profile->id, 'status' => 'approved', 'is_online' => true, 'rejection_reason' => null, 'can_reapply_at' => null]);
    }

    /**
     * @return array<string, array{Closure(): User, string}>
     */
    public static function onboardingUsers(): array
    {
        return [
            'business, no profile' => [fn (): User => User::factory()->registeredAs(RegisterAs::Business)->create(), 'complete_business_profile'],
            'business, pending' => [fn (): User => BusinessProfile::factory()->create(['user_id' => User::factory()->registeredAs(RegisterAs::Business)])->user, 'business_under_review'],
            'business, rejected' => [fn (): User => BusinessProfile::factory()->rejected()->create(['user_id' => User::factory()->registeredAs(RegisterAs::Business)])->user, 'business_rejected'],
            'business, approved' => [fn (): User => BusinessProfile::factory()->approved()->create(['user_id' => User::factory()->registeredAs(RegisterAs::Business)])->user, 'ready'],
            'tayar, no application' => [fn (): User => User::factory()->registeredAs(RegisterAs::Tayar)->create(), 'complete_tayar_application'],
            'tayar, pending' => [fn (): User => TayarProfile::factory()->create(['user_id' => User::factory()->registeredAs(RegisterAs::Tayar)])->user, 'tayar_under_review'],
            'tayar, rejected' => [fn (): User => TayarProfile::factory()->rejected()->create(['user_id' => User::factory()->registeredAs(RegisterAs::Tayar)])->user, 'tayar_rejected'],
            'tayar, approved' => [fn (): User => TayarProfile::factory()->approved()->create(['user_id' => User::factory()->registeredAs(RegisterAs::Tayar)])->user, 'ready'],
            'approved business that also applied as a tayar' => [fn (): User => TayarProfile::factory()->create([
                'user_id' => BusinessProfile::factory()->approved()->create(['user_id' => User::factory()->registeredAs(RegisterAs::Business)])->user_id,
            ])->user, 'ready'],
            'older business account without register_as' => [fn (): User => User::factory()->business()->create(), 'complete_business_profile'],
            'older individual customer' => [fn (): User => User::factory()->create(), 'ready'],
        ];
    }

    /**
     * @param  Closure(): User  $makeUser
     */
    #[DataProvider('onboardingUsers')]
    public function test_next_step_tells_the_app_which_screen_to_open(Closure $makeUser, string $nextStep): void
    {
        Sanctum::actingAs($makeUser());

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.next_step', $nextStep);
    }

    public function test_a_rejected_tayar_gets_the_reason_and_when_to_reapply(): void
    {
        $profile = TayarProfile::factory()->rejected()->create(['user_id' => User::factory()->registeredAs(RegisterAs::Tayar)]);
        Sanctum::actingAs($profile->user);

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.register_as', 'tayar')
            ->assertJsonPath('data.next_step', 'tayar_rejected')
            ->assertJsonPath('data.tayar_profile.rejection_reason', 'The criminal record is not readable.')
            ->assertJsonPath('data.tayar_profile.can_reapply_at', $profile->can_reapply_at->toIso8601ZuluString());
    }

    public function test_staff_are_ready_and_older_rows_have_no_register_as(): void
    {
        Sanctum::actingAs($this->createStaffUser(StaffRole::Admin));

        $this->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.register_as', null)
            ->assertJsonPath('data.next_step', 'ready');
    }

    public function test_guests_get_401(): void
    {
        $this->getJson(route('api.v1.auth.me'))->assertUnauthorized();
    }
}
