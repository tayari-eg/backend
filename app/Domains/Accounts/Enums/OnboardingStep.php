<?php

namespace App\Domains\Accounts\Enums;

/**
 * The screen the app opens after login, so a user who left in the middle of onboarding continues where they were.
 */
enum OnboardingStep: string
{
    case CompleteBusinessProfile = 'complete_business_profile';
    case BusinessUnderReview = 'business_under_review';
    case BusinessRejected = 'business_rejected';
    case CompleteTayarApplication = 'complete_tayar_application';
    case TayarUnderReview = 'tayar_under_review';
    case TayarRejected = 'tayar_rejected';
    case Ready = 'ready';
}
