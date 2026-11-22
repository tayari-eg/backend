<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Tayars\Actions\ReplaceTayarDocumentAction;
use App\Domains\Tayars\Enums\TayarDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreTayarDocumentRequest;
use App\Http\Resources\Api\V1\TayarProfileResource;

class TayarDocumentController extends Controller
{
    /**
     * Upload or replace one tayar document. A reviewed application goes back to pending.
     */
    public function __invoke(StoreTayarDocumentRequest $request, TayarDocument $collection, ReplaceTayarDocumentAction $replaceTayarDocument): TayarProfileResource
    {
        $profile = $replaceTayarDocument->handle($request->user()->tayarProfile, $collection, $request->file('file'));

        return TayarProfileResource::make($profile->load(TayarProfileController::RELATIONS));
    }
}
