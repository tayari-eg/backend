<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Auth\UserResource;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * The logged-in user, with the national ID status, the business profile and, for staff, roles and permissions.
     */
    public function __invoke(Request $request): UserResource
    {
        return UserResource::make($request->user()->load(UserResource::RELATIONS));
    }
}
