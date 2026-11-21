<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Accounts\Actions\ReplaceBusinessDocumentAction;
use App\Domains\Accounts\Enums\DocumentCollection;
use App\Domains\Accounts\Models\BusinessProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreBusinessDocumentRequest;
use App\Http\Resources\Api\V1\Client\BusinessProfileResource;

class BusinessDocumentController extends Controller
{
    /**
     * Upload or replace one business document. A reviewed profile goes back to pending.
     */
    public function __invoke(StoreBusinessDocumentRequest $request, DocumentCollection $collection, ReplaceBusinessDocumentAction $replaceBusinessDocument): BusinessProfileResource
    {
        /** @var BusinessProfile $profile */
        $profile = $request->user()->businessProfile;

        $profile = $replaceBusinessDocument->handle($profile, $collection, $request->file('file'));

        return BusinessProfileResource::make($profile->load(['businessType', 'links', 'media']));
    }
}
