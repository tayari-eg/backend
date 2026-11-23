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
    | Orders
    |--------------------------------------------------------------------------
    |
    | Distances are straight lines times road_distance_multiplier, to
    | approximate the road until a maps API is added. A trip up to
    | zone_max_distance_km is priced in the zone mode (unless both points are
    | in one area); longer trips in the long distance mode, up to
    | max_distance_km of road. Pickup and drop-off must be at least
    | min_distance_km apart in a straight line.
    |
    */

    'orders' => [
        'road_distance_multiplier' => (string) env('ORDER_ROAD_DISTANCE_MULTIPLIER', '1.30'),
        'zone_max_distance_km' => (int) env('ORDER_ZONE_MAX_DISTANCE_KM', 15),
        'max_distance_km' => (int) env('ORDER_MAX_DISTANCE_KM', 1200),
        'min_distance_km' => (string) env('ORDER_MIN_DISTANCE_KM', '0.1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Matching
    |--------------------------------------------------------------------------
    |
    | A new order is offered to the offers_per_round nearest eligible tayars at
    | once; the first to accept gets it. An offer lasts offer_timeout_seconds.
    | Each round searches first_radius_km around the pickup, radius_step_km
    | more per round, up to max_radius_km. An order nobody accepted within
    | search_timeout_minutes of its price being accepted is cancelled by the
    | system. A long-distance tayar only gets orders whose drop-off is within
    | destination_radius_km of the destination. A tayar takes no new order
    | while holding max_active_orders (zone and area modes) or
    | max_active_orders_long_distance accepted orders. A sender cancels an
    | accepted order for free within free_cancel_seconds of the accept; after
    | that the sender owes the tayar cancel_fee (at most the shipping fee),
    | recorded on the order and settled later by the wallet.
    |
    */

    'matching' => [
        'offer_timeout_seconds' => (int) env('MATCHING_OFFER_TIMEOUT_SECONDS', 60),
        'offers_per_round' => (int) env('MATCHING_OFFERS_PER_ROUND', 5),
        'first_radius_km' => (int) env('MATCHING_FIRST_RADIUS_KM', 3),
        'radius_step_km' => (int) env('MATCHING_RADIUS_STEP_KM', 3),
        'max_radius_km' => (int) env('MATCHING_MAX_RADIUS_KM', 10),
        'search_timeout_minutes' => (int) env('MATCHING_SEARCH_TIMEOUT_MINUTES', 15),
        'destination_radius_km' => (int) env('MATCHING_DESTINATION_RADIUS_KM', 20),
        'max_active_orders' => (int) env('MATCHING_MAX_ACTIVE_ORDERS', 1),
        'max_active_orders_long_distance' => (int) env('MATCHING_MAX_ACTIVE_ORDERS_LONG_DISTANCE', 5),
        'free_cancel_seconds' => (int) env('MATCHING_FREE_CANCEL_SECONDS', 120),
        'cancel_fee' => (string) env('MATCHING_CANCEL_FEE', '20.00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking
    |--------------------------------------------------------------------------
    |
    | At pickup the receiver gets one SMS with the tracking link (url, where
    | {token} is replaced) and two codes of code_length digits: the delivery
    | code and the refusal code. Only their hashes are stored. The codes last
    | codes_valid_hours; code_max_attempts wrong codes lock them until new
    | ones are sent (the receiver can ask resend_codes_per_hour times an hour
    | per order). The public tracking endpoint allows requests_per_minute
    | requests per IP; a tayar may send code_requests_per_minute delivery or
    | refusal codes a minute.
    |
    */

    'tracking' => [
        'url' => (string) env('TRACKING_URL', 'http://localhost:3000/track/{token}'),
        'code_length' => (int) env('TRACKING_CODE_LENGTH', 6),
        'code_max_attempts' => (int) env('TRACKING_CODE_MAX_ATTEMPTS', 5),
        'codes_valid_hours' => (int) env('TRACKING_CODES_VALID_HOURS', 24),
        'resend_codes_per_hour' => (int) env('TRACKING_RESEND_CODES_PER_HOUR', 3),
        'requests_per_minute' => (int) env('TRACKING_REQUESTS_PER_MINUTE', 30),
        'code_requests_per_minute' => (int) env('TRACKING_CODE_REQUESTS_PER_MINUTE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet
    |--------------------------------------------------------------------------
    |
    | Every order is cash for now: the tayar collects amount_to_collect and
    | owes it to Tayari. A tayar owing more than max_cash_owed (net) gets no
    | new offers until staff record the cash handed in. A newly approved
    | tayar gets signup_bonus once; it can't be paid out until the tayar has
    | bonus_unlock_delivered_orders delivered orders. Tayar earnings and
    | bonuses can be paid out from available_from_hour (business time) on
    | the day they are earned.
    |
    */

    'wallet' => [
        'max_cash_owed' => (string) env('WALLET_MAX_CASH_OWED', '3000.00'),
        'signup_bonus' => (string) env('WALLET_SIGNUP_BONUS', '100.00'),
        'bonus_unlock_delivered_orders' => (int) env('WALLET_BONUS_UNLOCK_DELIVERED_ORDERS', 5),
        'available_from_hour' => (int) env('WALLET_AVAILABLE_FROM_HOUR', 23),
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
