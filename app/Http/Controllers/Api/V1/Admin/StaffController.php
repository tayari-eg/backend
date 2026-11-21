<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Accounts\Actions\BlockUserAction;
use App\Domains\Accounts\Actions\ChangeStaffRoleAction;
use App\Domains\Accounts\Actions\CreateStaffUserAction;
use App\Domains\Accounts\Actions\UnblockUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\BlockUserRequest;
use App\Http\Requests\Api\V1\Admin\ListStaffRequest;
use App\Http\Requests\Api\V1\Admin\StoreStaffRequest;
use App\Http\Requests\Api\V1\Admin\UpdateStaffRoleRequest;
use App\Http\Resources\Api\V1\Admin\StaffResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class StaffController extends Controller
{
    /**
     * Staff members with their role and status, newest first.
     */
    public function index(ListStaffRequest $request): AnonymousResourceCollection
    {
        $staff = User::query()
            ->staff()
            ->with('roles')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return StaffResource::collection($staff);
    }

    /**
     * Create a staff member with the admin, ops or support role.
     */
    public function store(StoreStaffRequest $request, CreateStaffUserAction $createStaffUser): JsonResponse
    {
        $staff = $createStaffUser->handle(
            $request->validated('name'),
            $request->phoneNumber(),
            $request->validated('password'),
            $request->role(),
        );

        return StaffResource::make($staff->load('roles'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Change a staff member's role.
     */
    public function updateRole(UpdateStaffRoleRequest $request, User $staff, ChangeStaffRoleAction $changeStaffRole): StaffResource
    {
        return StaffResource::make($changeStaffRole->handle($staff, $request->role(), $request->user())->load('roles'));
    }

    /**
     * Block a staff member with a reason.
     */
    public function block(BlockUserRequest $request, User $staff, BlockUserAction $blockUser): StaffResource
    {
        return StaffResource::make($blockUser->handle($staff, $request->validated('reason'), $request->user())->load('roles'));
    }

    /**
     * Unblock a staff member.
     */
    public function unblock(User $staff, UnblockUserAction $unblockUser): StaffResource
    {
        return StaffResource::make($unblockUser->handle($staff)->load('roles'));
    }
}
