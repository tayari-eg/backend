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

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | When phone verification is required, register sends an OTP, and login
    | and client routes need a verified phone.
    |
    */

    'auth' => [
        'require_phone_verification' => (bool) env('REQUIRE_PHONE_VERIFICATION', true),
        'login_attempts_per_minute' => (int) env('LOGIN_ATTEMPTS_PER_MINUTE', 5),
        'login_attempts_per_phone_per_hour' => (int) env('LOGIN_ATTEMPTS_PER_PHONE_PER_HOUR', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | One-Time Passwords
    |--------------------------------------------------------------------------
    */

    'otp' => [
        'length' => (int) env('OTP_LENGTH', 6),
        'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 5),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
        'rate_limits' => [
            'decay_minutes' => (int) env('OTP_RATE_LIMIT_DECAY_MINUTES', 60),
            'per_phone' => (int) env('OTP_RATE_LIMIT_PER_PHONE', 5),
            'per_ip' => (int) env('OTP_RATE_LIMIT_PER_IP', 20),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "log". The log driver writes the full message only
    | in local and testing; elsewhere it logs the number and [redacted].
    |
    */

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | Personal and business documents live on the private disk and are only
    | served through short-lived signed URLs.
    |
    */

    'media' => [
        'private_disk' => env('MEDIA_PRIVATE_DISK', 'local'),
        'public_disk' => env('MEDIA_PUBLIC_DISK', 'public'),
        'signed_url_minutes' => (int) env('MEDIA_SIGNED_URL_MINUTES', 5),
        'max_upload_kb' => (int) env('MEDIA_MAX_UPLOAD_KB', 5120),
        'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tayars
    |--------------------------------------------------------------------------
    |
    | A tayar online with no location for offline_after_minutes is set offline
    | by the scheduler. A rejected applicant may change the application again
    | reapply_after_hours after the rejection.
    |
    */

    'tayars' => [
        'location_updates_per_minute' => (int) env('TAYAR_LOCATION_UPDATES_PER_MINUTE', 20),
        'offline_after_minutes' => (int) env('TAYAR_OFFLINE_AFTER_MINUTES', 5),
        'nearest_radius_km' => (float) env('TAYAR_NEAREST_RADIUS_KM', 5),
        'reapply_after_hours' => (int) env('TAYAR_REAPPLY_AFTER_HOURS', 24),
        'zone_min_radius_km' => (int) env('TAYAR_ZONE_MIN_RADIUS_KM', 1),
        'zone_max_radius_km' => (int) env('TAYAR_ZONE_MAX_RADIUS_KM', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | First Super Admin
    |--------------------------------------------------------------------------
    |
    | Created by SuperAdminSeeder. The phone is normalized to +20… on seeding.
    |
    */

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME'),
        'phone' => env('SUPER_ADMIN_PHONE'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo Data
    |--------------------------------------------------------------------------
    |
    | The password of every demo account made by DemoDataSeeder (php artisan
    | db:seed --class=DemoDataSeeder). Empty: the seeder refuses to run.
    |
    */

    'demo' => [
        'users_password' => env('DEMO_USERS_PASSWORD'),
    ],

];
