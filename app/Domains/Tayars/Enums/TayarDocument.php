<?php

namespace App\Domains\Tayars\Enums;

/**
 * Media Library collections on the tayar profile, all on the private disk.
 */
enum TayarDocument: string
{
    case NationalIdFront = 'national_id_front';
    case NationalIdBack = 'national_id_back';
    case CriminalRecord = 'criminal_record';
    case PersonalPhoto = 'personal_photo';
    case DrivingLicense = 'driving_license';

    /**
     * The driving licence is only required when the vehicle type requires a licence (not for a bicycle).
     */
    public function isAlwaysRequired(): bool
    {
        return $this !== self::DrivingLicense;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
