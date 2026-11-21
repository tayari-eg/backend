<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Accounts\Actions\BlockUserAction;
use App\Domains\Accounts\Actions\UnblockUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\BlockUserRequest;
use App\Http\Requests\Api\V1\Admin\ListUsersRequest;
use App\Http\Resources\Api\V1\Admin\CustomerResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    /**
     * @var list<string>
     */
    private const array LIST_RELATIONS = ['media', 'businessProfile'];

    /**
     * @var list<string>
     */
    private const array DETAIL_RELATIONS = ['media', 'businessProfile.businessType', 'businessProfile.links', 'businessProfile.media'];

    /**
     * Customer and business accounts (never staff), newest first.
     */
    public function index(ListUsersRequest $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->customers()
            ->with(self::LIST_RELATIONS)
            ->when($request->searchPhone(), fn ($query, $phone) => $query->where('phone', $phone->value))
            ->when($request->searchPhoneDigits(), fn ($query, string $digits) => $query->whereLike('phone', "%{$digits}%"))
            ->when($request->searchName(), fn ($query, string $name) => $query->whereLike('name', "%{$name}%"))
            ->when($request->validated('account_type'), fn ($query, string $accountType) => $query->where('account_type', $accountType))
            ->when($request->validated('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->phoneVerified() !== null, fn ($query) => $request->phoneVerified()
                ? $query->whereNotNull('phone_verified_at')
                : $query->whereNull('phone_verified_at'))
            ->when($request->filtersNationalIdStatus(), fn ($query) => $query->whereNationalIdStatus($request->nationalIdStatus()))
            ->when($request->validated('business_profile_status'), fn ($query, string $status) => $query
                ->whereHas('businessProfile', fn ($profiles) => $profiles->where('verification_status', $status)))
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return CustomerResource::collection($users);
    }

    /**
     * Show a customer with the national ID and the business profile.
     */
    public function show(User $customer): CustomerResource
    {
        return CustomerResource::make($customer->load(self::DETAIL_RELATIONS));
    }

    /**
     * Block a customer with a reason; their mobile logins are signed out.
     */
    public function block(BlockUserRequest $request, User $customer, BlockUserAction $blockUser): CustomerResource
    {
        $blockUser->handle($customer, $request->validated('reason'), $request->user());

        return CustomerResource::make($customer->load(self::DETAIL_RELATIONS));
    }

    /**
     * Unblock a customer.
     */
    public function unblock(User $customer, UnblockUserAction $unblockUser): CustomerResource
    {
        return CustomerResource::make($unblockUser->handle($customer)->load(self::DETAIL_RELATIONS));
    }
}
