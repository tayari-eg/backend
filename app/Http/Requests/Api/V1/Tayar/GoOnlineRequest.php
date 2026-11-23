<?php

namespace App\Http\Requests\Api\V1\Tayar;

use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Tayars\Data\WorkSessionSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoOnlineRequest extends FormRequest
{
    private ?Mode $selectedMode = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The mode decides the other fields: area mode needs area_id, zone needs radius_km, long distance needs the
     * destination. Fields another mode needs are refused.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $code = $this->selectedMode()?->code;
        $needsArea = $code?->requiresArea() === true;
        $needsRadius = $code?->requiresRadius() === true;
        $needsDestination = $code?->requiresDestination() === true;

        return [
            'lat' => ['required', 'numeric', 'decimal:0,7', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'decimal:0,7', 'between:-180,180'],
            'mode_id' => ['required', 'integer', Rule::exists('modes', 'id')->where('is_active', true)],
            'area_id' => [Rule::requiredIf($needsArea), Rule::prohibitedIf($code !== null && ! $needsArea), 'integer', Rule::exists('areas', 'id')->where('is_active', true)],
            'radius_km' => [
                Rule::requiredIf($needsRadius), Rule::prohibitedIf($code !== null && ! $needsRadius), 'numeric', 'decimal:0,2',
                'min:'.config('tayari.tayars.zone_min_radius_km'), 'max:'.config('tayari.tayars.zone_max_radius_km'),
            ],
            'destination_address' => [Rule::requiredIf($needsDestination), Rule::prohibitedIf($code !== null && ! $needsDestination), 'string', 'max:255'],
            'destination_lat' => [Rule::requiredIf($needsDestination), Rule::prohibitedIf($code !== null && ! $needsDestination), 'numeric', 'decimal:0,7', 'between:-90,90'],
            'destination_lng' => [Rule::requiredIf($needsDestination), Rule::prohibitedIf($code !== null && ! $needsDestination), 'numeric', 'decimal:0,7', 'between:-180,180'],
        ];
    }

    public function lat(): float
    {
        return (float) $this->validated('lat');
    }

    public function lng(): float
    {
        return (float) $this->validated('lng');
    }

    public function settings(): WorkSessionSettings
    {
        $validated = $this->validated();

        return new WorkSessionSettings(
            mode: $this->selectedMode() ?? Mode::query()->findOrFail($validated['mode_id']),
            area: isset($validated['area_id']) ? Area::query()->findOrFail($validated['area_id']) : null,
            radiusKm: isset($validated['radius_km']) ? (string) $validated['radius_km'] : null,
            destinationAddress: $validated['destination_address'] ?? null,
            destinationLat: isset($validated['destination_lat']) ? (string) $validated['destination_lat'] : null,
            destinationLng: isset($validated['destination_lng']) ? (string) $validated['destination_lng'] : null,
        );
    }

    private function selectedMode(): ?Mode
    {
        $modeId = $this->input('mode_id');

        if ($this->selectedMode === null && is_numeric($modeId)) {
            $this->selectedMode = Mode::query()->find((int) $modeId);
        }

        return $this->selectedMode;
    }
}
