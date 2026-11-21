<?php

namespace App\Domains\Accounts\Enums;

enum BusinessLinkType: string
{
    case Website = 'website';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Tiktok = 'tiktok';
    case Whatsapp = 'whatsapp';
    case Other = 'other';
}
