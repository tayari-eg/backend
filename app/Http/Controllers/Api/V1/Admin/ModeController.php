<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Catalog\Actions\ActivateModeAction;
use App\Domains\Catalog\Actions\DeactivateModeAction;
use App\Domains\Catalog\Actions\UpdateModeAction;
use App\Domains\Catalog\Models\Mode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListModesRequest;
use App\Http\Requests\Api\V1\Admin\UpdateModeRequest;
use App\Http\Resources\Api\V1\Admin\ModeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModeController extends Controller
{
    /**
     * The three modes, active and inactive, in sort order.
     */
    public function index(ListModesRequest $request): AnonymousResourceCollection
    {
        $modes = Mode::query()
            ->withCount('workSessions')
            ->when($request->isActive() !== null, fn ($query) => $query->where('is_active', $request->isActive()))
            ->when($request->search() !== null, fn ($query) => $query->whereNameLike($request->search()))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($request->perPage());

        return ModeResource::collection($modes);
    }

    /**
     * Show one mode with its name and description in every language.
     */
    public function show(Mode $mode): ModeResource
    {
        return ModeResource::make($mode->loadCount('workSessions'));
    }

    /**
     * Update a mode's name, description, food flag or sort order; the code never changes.
     */
    public function update(UpdateModeRequest $request, Mode $mode, UpdateModeAction $updateMode): ModeResource
    {
        return ModeResource::make($updateMode->handle($mode, $request->validated())->loadCount('workSessions'));
    }

    /**
     * Let tayars go online in this mode again.
     */
    public function activate(Mode $mode, ActivateModeAction $activateMode): ModeResource
    {
        return ModeResource::make($activateMode->handle($mode)->loadCount('workSessions'));
    }

    /**
     * Stop new go-online requests in this mode; running sessions keep it.
     */
    public function deactivate(Mode $mode, DeactivateModeAction $deactivateMode): ModeResource
    {
        return ModeResource::make($deactivateMode->handle($mode)->loadCount('workSessions'));
    }
}
