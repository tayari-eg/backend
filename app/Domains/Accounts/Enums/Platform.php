<?php

namespace App\Domains\Accounts\Enums;

enum Platform: string
{
    case Android = 'android';
    case Ios = 'ios';
    case Web = 'web';

    public function isMobileApp(): bool
    {
        return $this !== self::Web;
    }
}
