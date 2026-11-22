<?php

namespace App\Domains\Tayars\Enums;

/**
 * Media Library collections on a vehicle, all on the private disk.
 */
enum VehicleDocument: string
{
    case VehicleLicense = 'vehicle_license';
    case VehiclePhotos = 'vehicle_photos';

    public const int MAX_PHOTOS = 4;

    public function isSingleFile(): bool
    {
        return $this === self::VehicleLicense;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
