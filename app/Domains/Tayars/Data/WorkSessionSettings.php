<?php

namespace App\Domains\Tayars\Data;

use App\Domains\Catalog\Models\Area;
use App\Domains\Catalog\Models\Mode;
use App\Domains\Tayars\Models\TayarWorkSession;
use Brick\Math\BigDecimal;

/**
 * What a tayar picks when going online: the mode, plus the area, radius or destination that mode needs.
 * Numbers are decimal strings, as validated.
 */
final readonly class WorkSessionSettings
{
    public function __construct(
        public Mode $mode,
        public ?Area $area = null,
        public ?string $radiusKm = null,
        public ?string $destinationAddress = null,
        public ?string $destinationLat = null,
        public ?string $destinationLng = null,
    ) {}

    /**
     * @return array{mode_id: int, area_id: ?int, radius_km: ?string, destination_address: ?string, destination_lat: ?string, destination_lng: ?string}
     */
    public function toAttributes(): array
    {
        return [
            'mode_id' => $this->mode->id,
            'area_id' => $this->area?->id,
            'radius_km' => $this->radiusKm,
            'destination_address' => $this->destinationAddress,
            'destination_lat' => $this->destinationLat,
            'destination_lng' => $this->destinationLng,
        ];
    }

    /**
     * Whether the session was started with these same settings.
     */
    public function matches(TayarWorkSession $session): bool
    {
        return $session->mode_id === $this->mode->id
            && $session->area_id === $this->area?->id
            && $session->destination_address === $this->destinationAddress
            && self::sameNumber($session->radius_km, $this->radiusKm)
            && self::sameNumber($session->destination_lat, $this->destinationLat)
            && self::sameNumber($session->destination_lng, $this->destinationLng);
    }

    private static function sameNumber(?string $stored, ?string $given): bool
    {
        if ($stored === null || $given === null) {
            return $stored === $given;
        }

        return BigDecimal::of($stored)->isEqualTo($given);
    }
}
