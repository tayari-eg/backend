<?php

namespace App\Domains\Accounts\Enums;

enum StaffPermission: string
{
    case BusinessProfilesView = 'business_profiles.view';
    case BusinessProfilesReview = 'business_profiles.review';
    case NationalIdsView = 'national_ids.view';
    case NationalIdsReview = 'national_ids.review';
    case BusinessTypesView = 'business_types.view';
    case BusinessTypesManage = 'business_types.manage';
    case UsersView = 'users.view';
    case UsersBlock = 'users.block';
    case StaffManage = 'staff.manage';
    case TayarsView = 'tayars.view';
    case TayarsReview = 'tayars.review';
    case VehicleTypesView = 'vehicle_types.view';
    case VehicleTypesManage = 'vehicle_types.manage';
    case CategoriesView = 'categories.view';
    case CategoriesManage = 'categories.manage';
    case ModesView = 'modes.view';
    case ModesManage = 'modes.manage';
    case AreasView = 'areas.view';
    case AreasManage = 'areas.manage';
    case PricingRulesView = 'pricing_rules.view';
    case PricingRulesManage = 'pricing_rules.manage';
    case OrdersView = 'orders.view';
    case OrdersManage = 'orders.manage';
    case WalletsView = 'wallets.view';
    case WalletsSettle = 'wallets.settle';
    case WalletsAdjust = 'wallets.adjust';
}
