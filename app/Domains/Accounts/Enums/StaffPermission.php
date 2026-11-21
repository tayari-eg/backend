<?php

namespace App\Domains\Accounts\Enums;

enum StaffPermission: string
{
    case BusinessProfilesView = 'business_profiles.view';
    case BusinessProfilesReview = 'business_profiles.review';
    case NationalIdsView = 'national_ids.view';
    case NationalIdsReview = 'national_ids.review';
}
