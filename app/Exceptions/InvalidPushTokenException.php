<?php

namespace App\Exceptions;

use Exception;

/**
 * FCM reported the device's push token as unregistered or invalid, so the device should be removed.
 */
class InvalidPushTokenException extends Exception
{
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct("FCM reported the push token as dead [{$errorCode}].");
    }
}
