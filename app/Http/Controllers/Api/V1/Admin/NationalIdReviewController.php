<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Accounts\Actions\RejectNationalIdAction;
use App\Domains\Accounts\Actions\VerifyNationalIdAction;
use App\Domains\Accounts\Enums\DocumentCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListNationalIdsRequest;
use App\Http\Requests\Api\V1\Admin\RejectNationalIdRequest;
use App\Http\Requests\Api\V1\Admin\VerifyNationalIdRequest;
use App\Http\Resources\Api\V1\Admin\NationalIdReviewResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NationalIdReviewController extends Controller
{
    /**
     * Client national IDs waiting for review, oldest account first.
     */
    public function index(ListNationalIdsRequest $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->with('media')
            ->whereNull('id_reviewed_at')
            ->whereHas('media', fn (Builder $query) => $query->where('collection_name', DocumentCollection::NationalIdFront->value))
            ->orderBy('id')
            ->paginate($request->perPage());

        return NationalIdReviewResource::collection($users);
    }

    public function verify(VerifyNationalIdRequest $request, User $user, VerifyNationalIdAction $verifyNationalId): NationalIdReviewResource
    {
        return NationalIdReviewResource::make($verifyNationalId->handle($user, $request->user())->load('media'));
    }

    public function reject(RejectNationalIdRequest $request, User $user, RejectNationalIdAction $rejectNationalId): NationalIdReviewResource
    {
        return NationalIdReviewResource::make($rejectNationalId->handle($user, $request->user(), $request->validated('reason'))->load('media'));
    }
}
