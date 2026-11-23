<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Wallet\Actions\ActivateWalletAdjustmentReasonAction;
use App\Domains\Wallet\Actions\CreateWalletAdjustmentReasonAction;
use App\Domains\Wallet\Actions\DeactivateWalletAdjustmentReasonAction;
use App\Domains\Wallet\Actions\DeleteWalletAdjustmentReasonAction;
use App\Domains\Wallet\Actions\UpdateWalletAdjustmentReasonAction;
use App\Domains\Wallet\Models\WalletAdjustmentReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListWalletAdjustmentReasonsRequest;
use App\Http\Requests\Api\V1\Admin\StoreWalletAdjustmentReasonRequest;
use App\Http\Requests\Api\V1\Admin\UpdateWalletAdjustmentReasonRequest;
use App\Http\Resources\Api\V1\Admin\WalletAdjustmentReasonResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class WalletAdjustmentReasonController extends Controller
{
    /**
     * Active and inactive adjustment reasons, in sort order.
     */
    public function index(ListWalletAdjustmentReasonsRequest $request): AnonymousResourceCollection
    {
        $reasons = WalletAdjustmentReason::query()
            ->withCount('postings')
            ->when($request->isActive() !== null, fn ($query) => $query->where('is_active', $request->isActive()))
            ->when($request->search() !== null, fn ($query) => $query->whereNameLike($request->search()))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return WalletAdjustmentReasonResource::collection($reasons);
    }

    /**
     * Show one adjustment reason with its names in every language.
     */
    public function show(WalletAdjustmentReason $walletAdjustmentReason): WalletAdjustmentReasonResource
    {
        return WalletAdjustmentReasonResource::make($walletAdjustmentReason->loadCount('postings'));
    }

    /**
     * Create an adjustment reason.
     */
    public function store(StoreWalletAdjustmentReasonRequest $request, CreateWalletAdjustmentReasonAction $createReason): JsonResponse
    {
        $reason = $createReason->handle(
            $request->validated('name'),
            $request->boolean('is_active', true),
            $request->has('sort_order') ? (int) $request->validated('sort_order') : null,
        );

        return WalletAdjustmentReasonResource::make($reason->loadCount('postings'))
            ->response()
            ->setStatusCode(HttpStatus::HTTP_CREATED);
    }

    /**
     * Update an adjustment reason; send only the fields that change.
     */
    public function update(UpdateWalletAdjustmentReasonRequest $request, WalletAdjustmentReason $walletAdjustmentReason, UpdateWalletAdjustmentReasonAction $updateReason): WalletAdjustmentReasonResource
    {
        return WalletAdjustmentReasonResource::make($updateReason->handle($walletAdjustmentReason, $request->validated())->loadCount('postings'));
    }

    /**
     * Let staff pick the reason again.
     */
    public function activate(WalletAdjustmentReason $walletAdjustmentReason, ActivateWalletAdjustmentReasonAction $activateReason): WalletAdjustmentReasonResource
    {
        return WalletAdjustmentReasonResource::make($activateReason->handle($walletAdjustmentReason)->loadCount('postings'));
    }

    /**
     * Stop staff picking the reason; past adjustments keep it.
     */
    public function deactivate(WalletAdjustmentReason $walletAdjustmentReason, DeactivateWalletAdjustmentReasonAction $deactivateReason): WalletAdjustmentReasonResource
    {
        return WalletAdjustmentReasonResource::make($deactivateReason->handle($walletAdjustmentReason)->loadCount('postings'));
    }

    /**
     * Delete an adjustment reason no posting uses.
     */
    public function destroy(WalletAdjustmentReason $walletAdjustmentReason, DeleteWalletAdjustmentReasonAction $deleteReason): Response
    {
        $deleteReason->handle($walletAdjustmentReason);

        return response()->noContent();
    }
}
