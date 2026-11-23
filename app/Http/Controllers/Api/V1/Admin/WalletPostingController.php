<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Wallet\Actions\ReversePostingAction;
use App\Domains\Wallet\Models\WalletPosting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ReversePostingRequest;
use App\Http\Resources\Api\V1\Admin\WalletPostingResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class WalletPostingController extends Controller
{
    /**
     * Reverse a payout, cash handed in or adjustment recorded by mistake; the original stays. Asking again returns the
     * same reversal (200).
     */
    public function reverse(ReversePostingRequest $request, WalletPosting $walletPosting, ReversePostingAction $reversePosting): JsonResponse
    {
        $reversal = $reversePosting->handle($walletPosting, $request->adjustmentReason(), $request->note(), $request->user());

        return WalletPostingResource::make($reversal->load(WalletController::POSTING_RELATIONS))
            ->response()
            ->setStatusCode($reversal->wasRecentlyCreated ? HttpStatus::HTTP_CREATED : HttpStatus::HTTP_OK);
    }
}
