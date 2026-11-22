<?php

namespace App\Domains\Accounts\Enums;

/**
 * Media Library collection names for personal and business documents.
 */
enum DocumentCollection: string
{
    case NationalIdFront = 'national_id_front';
    case NationalIdBack = 'national_id_back';
    case CommercialRegister = 'commercial_register';
    case TaxCard = 'tax_card';

    /**
     * Changing any business document sends an approved or rejected profile back to review.
     *
     * @return list<self>
     */
    public static function forBusinessProfile(): array
    {
        return self::cases();
    }

    /**
     * @return list<self>
     */
    public static function forNationalId(): array
    {
        return [self::NationalIdFront, self::NationalIdBack];
    }

    public function isRequiredForBusinessProfile(): bool
    {
        return in_array($this, self::forNationalId(), true);
    }
}
