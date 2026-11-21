<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Accounts\Actions\SubmitNationalIdAction;
use App\Domains\Accounts\Enums\DocumentCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreNationalIdRequest;
use App\Http\Resources\Api\V1\Auth\UserResource;

class NationalIdController extends Controller
{
    /**
     * Upload the optional national ID (front and back). A new upload clears the previous review.
     */
    public function __invoke(StoreNationalIdRequest $request, SubmitNationalIdAction $submitNationalId): UserResource
    {
        $user = $submitNationalId->handle(
            $request->user(),
            $request->file(DocumentCollection::NationalIdFront->value),
            $request->file(DocumentCollection::NationalIdBack->value),
        );

        return UserResource::make($user->load(UserResource::RELATIONS));
    }
}
