<?php

namespace Tests\Unit\Domains\Accounts;

use App\Domains\Accounts\Enums\AccountType;
use App\Domains\Accounts\Enums\OnboardingStep;
use App\Domains\Accounts\Enums\RegisterAs;
use App\Domains\Accounts\Enums\StaffRole;
use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Domains\Tayars\Enums\TayarStatus;
use App\Domains\Tayars\Models\TayarProfile;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Booted app for the casts and Spatie, but no database: every relation is set in memory.
 */
class OnboardingStepTest extends TestCase
{
    private function user(?RegisterAs $registerAs, AccountType $accountType = AccountType::Individual, ?VerificationStatus $business = null, ?TayarStatus $tayar = null): User
    {
        $user = new User(['account_type' => $accountType]);
        $user->register_as = $registerAs;
        $user->setRelation('roles', collect());
        $user->setRelation('businessProfile', $business === null ? null : (new BusinessProfile)->forceFill(['verification_status' => $business]));
        $user->setRelation('tayarProfile', $tayar === null ? null : (new TayarProfile)->forceFill(['status' => $tayar]));

        return $user;
    }

    /**
     * @return array<string, array{?RegisterAs, AccountType, ?VerificationStatus, ?TayarStatus, OnboardingStep}>
     */
    public static function users(): array
    {
        return [
            'business, no profile' => [RegisterAs::Business, AccountType::Business, null, null, OnboardingStep::CompleteBusinessProfile],
            'business, pending' => [RegisterAs::Business, AccountType::Business, VerificationStatus::Pending, null, OnboardingStep::BusinessUnderReview],
            'business, rejected' => [RegisterAs::Business, AccountType::Business, VerificationStatus::Rejected, null, OnboardingStep::BusinessRejected],
            'business, approved' => [RegisterAs::Business, AccountType::Business, VerificationStatus::Approved, null, OnboardingStep::Ready],
            'tayar, no application' => [RegisterAs::Tayar, AccountType::Individual, null, null, OnboardingStep::CompleteTayarApplication],
            'tayar, pending' => [RegisterAs::Tayar, AccountType::Individual, null, TayarStatus::Pending, OnboardingStep::TayarUnderReview],
            'tayar, rejected' => [RegisterAs::Tayar, AccountType::Individual, null, TayarStatus::Rejected, OnboardingStep::TayarRejected],
            'tayar, approved' => [RegisterAs::Tayar, AccountType::Individual, null, TayarStatus::Approved, OnboardingStep::Ready],
            'approved business that also applied as a tayar' => [RegisterAs::Business, AccountType::Business, VerificationStatus::Approved, TayarStatus::Pending, OnboardingStep::Ready],
            'tayar in review that also has a business profile' => [RegisterAs::Tayar, AccountType::Business, VerificationStatus::Approved, TayarStatus::Pending, OnboardingStep::TayarUnderReview],
            'older business account without register_as' => [null, AccountType::Business, null, null, OnboardingStep::CompleteBusinessProfile],
            'older account with a tayar application' => [null, AccountType::Individual, null, TayarStatus::Rejected, OnboardingStep::TayarRejected],
            'older individual customer' => [null, AccountType::Individual, null, null, OnboardingStep::Ready],
        ];
    }

    #[DataProvider('users')]
    public function test_next_step_follows_the_registered_path(?RegisterAs $registerAs, AccountType $accountType, ?VerificationStatus $business, ?TayarStatus $tayar, OnboardingStep $expected): void
    {
        $this->assertSame($expected, $this->user($registerAs, $accountType, $business, $tayar)->onboardingStep());
    }

    public function test_staff_are_always_ready(): void
    {
        $staff = $this->user(RegisterAs::Business, AccountType::Business);
        $staff->setRelation('roles', collect([(new Role)->forceFill(['name' => StaffRole::Support->value, 'guard_name' => 'web'])]));

        $this->assertSame(OnboardingStep::Ready, $staff->onboardingStep());
    }
}
