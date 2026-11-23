<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Pricing\Actions\ActivatePricingRuleAction;
use App\Domains\Pricing\Actions\CreatePricingRuleAction;
use App\Domains\Pricing\Actions\DeactivatePricingRuleAction;
use App\Domains\Pricing\Actions\DeletePricingRuleAction;
use App\Domains\Pricing\Actions\UpdatePricingRuleAction;
use App\Domains\Pricing\Models\PricingRule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListPricingRulesRequest;
use App\Http\Requests\Api\V1\Admin\StorePricingRuleRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePricingRuleRequest;
use App\Http\Resources\Api\V1\Admin\PricingRuleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class PricingRuleController extends Controller
{
    /**
     * Pricing rules, active and inactive, by mode.
     */
    public function index(ListPricingRulesRequest $request): AnonymousResourceCollection
    {
        $pricingRules = PricingRule::query()
            ->with(['mode', 'vehicleType'])
            ->when($request->modeId() !== null, fn ($query) => $query->where('mode_id', $request->modeId()))
            ->when($request->vehicleTypeId() !== null, fn ($query) => $query->where('vehicle_type_id', $request->vehicleTypeId()))
            ->when($request->isActive() !== null, fn ($query) => $query->where('is_active', $request->isActive()))
            ->orderBy('mode_id')
            ->orderBy('id')
            ->paginate($request->perPage());

        return PricingRuleResource::collection($pricingRules);
    }

    /**
     * Show one pricing rule.
     */
    public function show(PricingRule $pricingRule): PricingRuleResource
    {
        return PricingRuleResource::make($pricingRule->load(['mode', 'vehicleType']));
    }

    /**
     * Create a pricing rule for a mode and one vehicle type, or every vehicle.
     */
    public function store(StorePricingRuleRequest $request, CreatePricingRuleAction $createPricingRule): JsonResponse
    {
        return PricingRuleResource::make($createPricingRule->handle($request->pricingRuleAttributes())->load(['mode', 'vehicleType']))
            ->response()
            ->setStatusCode(HttpStatus::HTTP_CREATED);
    }

    /**
     * Update a pricing rule; send only the fields that change.
     */
    public function update(UpdatePricingRuleRequest $request, PricingRule $pricingRule, UpdatePricingRuleAction $updatePricingRule): PricingRuleResource
    {
        return PricingRuleResource::make($updatePricingRule->handle($pricingRule, $request->pricingRuleAttributes())->load(['mode', 'vehicleType']));
    }

    /**
     * Turn a pricing rule on.
     */
    public function activate(PricingRule $pricingRule, ActivatePricingRuleAction $activatePricingRule): PricingRuleResource
    {
        return PricingRuleResource::make($activatePricingRule->handle($pricingRule)->load(['mode', 'vehicleType']));
    }

    /**
     * Turn a pricing rule off.
     */
    public function deactivate(PricingRule $pricingRule, DeactivatePricingRuleAction $deactivatePricingRule): PricingRuleResource
    {
        return PricingRuleResource::make($deactivatePricingRule->handle($pricingRule)->load(['mode', 'vehicleType']));
    }

    /**
     * Delete a pricing rule.
     */
    public function destroy(PricingRule $pricingRule, DeletePricingRuleAction $deletePricingRule): Response
    {
        $deletePricingRule->handle($pricingRule);

        return response()->noContent();
    }
}
