<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Minimum Supported App Versions
    |--------------------------------------------------------------------------
    |
    | Mobile app versions below these get a 426 Upgrade Required response.
    | Empty means no minimum. Web is never blocked.
    |
    */

    'min_supported_versions' => [
        'android' => env('APP_MIN_SUPPORTED_VERSION_ANDROID') ?: null,
        'ios' => env('APP_MIN_SUPPORTED_VERSION_IOS') ?: null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Latest App Versions
    |--------------------------------------------------------------------------
    |
    | Returned by app-config so the apps can suggest an optional update.
    |
    */

    'latest_versions' => [
        'android' => env('APP_LATEST_VERSION_ANDROID') ?: null,
        'ios' => env('APP_LATEST_VERSION_IOS') ?: null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | On the shared host the scheduler drains the queue every minute.
    | Turn this off once Horizon runs the queue workers.
    |
    */

    'queue' => [
        'work_from_scheduler' => (bool) env('QUEUE_WORK_FROM_SCHEDULER', true),
    ],

];
