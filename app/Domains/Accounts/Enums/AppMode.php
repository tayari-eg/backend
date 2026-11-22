<?php

namespace App\Domains\Accounts\Enums;

/**
 * The side of the app a user last used; the Flutter app reopens in it.
 */
enum AppMode: string
{
    case Client = 'client';
    case Tayar = 'tayar';
}
