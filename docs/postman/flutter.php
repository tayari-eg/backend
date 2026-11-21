<?php

/*
|--------------------------------------------------------------------------
| Postman: Flutter App
|--------------------------------------------------------------------------
|
| Feature folders in flow order. Each request names a route documented in
| docs/postman/endpoints.php, the screen that calls it, and optionally a
| "next" line added to its Success section.
|
*/

return [

    'name' => 'Flutter app',
    'auth' => 'bearer',

    'description' => <<<'MD'
        The mobile app for customers and business owners (Android and iOS).

        - **Login:** Bearer token from **Sign up and login › Log in**. Its script saves the token to `{{token}}`, and every request in this folder sends it.
        - **Headers:** this folder adds `X-Platform: {{platform}}` (`android` by default; set `ios` in the environment) and `X-App-Version: {{appVersion}}`.
        - Build the folders in order: app start, sign up and login, account, push notifications, then the business and national ID screens.
        MD,

    'folders' => [

        [
            'name' => '1. App start',
            'description' => <<<'MD'
                What the app does on the **Splash** screen, before anything else:

                1. Call **Get app config**.
                2. If the installed version is lower than `min_supported_version` for this platform, show the **force-update** screen (no way past it) with a button to the store.
                3. Else, if it is lower than `latest_version`, show an **optional update** dialog ("Update" / "Later").
                4. Keep `phone_verification_required`: it decides whether the OTP screen comes after sign up.
                5. If a token is stored, go to Home (and call **Get my account**); otherwise go to Login.

                Any other request can also answer **426** later: show the same force-update screen.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.public.app-config',
                    'name' => 'Get app config',
                    'screen' => '**Splash** screen, on every app start (and when the app comes back after a long time in the background).',
                    'next' => 'Force update, optional update dialog, then Home or Login as described in the folder.',
                ],
            ],
        ],

        [
            'name' => '2. Sign up and login',
            'description' => <<<'MD'
                From a new user to a logged-in user:

                1. **Sign up** screen: name, phone, password, password again, and a choice between **Customer** (`individual`) and **Business** (`business`). Submit → **Sign up**.
                2. If the answer has `phone_verification_required: true`, open the **OTP** screen; otherwise go to **Login**.
                3. **OTP** screen: a :otp_length-digit code input and a **Resend code** button with a countdown that starts at `resend_available_in` seconds. Submit → **Verify phone**; resend → **Resend code** (purpose `verify_phone`), then restart the countdown at :otp_cooldown_seconds seconds.
                4. After the phone is verified, go to **Login** (verifying does not log in).
                5. **Login** screen: phone and password → **Log in**. Save the token, then register the push token (**Push notifications**) and go to Home.
                6. **Forgot password** screen: phone → **Forgot password**, then always open **Reset password**.
                7. **Reset password** screen: code, new password, new password again → **Reset password**, then go to Login.

                If Login answers 403 "verify your phone", open the OTP screen and call **Resend code** first.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.auth.register',
                    'name' => 'Sign up',
                    'screen' => '**Sign up** screen, when the user taps **Create account**.',
                    'next' => 'If `phone_verification_required` is true, open the OTP screen with the countdown from `resend_available_in`; otherwise go to Login.',
                ],
                [
                    'route' => 'api.v1.auth.verify-phone',
                    'name' => 'Verify phone',
                    'screen' => '**OTP** screen, when the user enters the code (or taps **Verify**).',
                    'next' => 'Show "Phone verified" and go to Login.',
                ],
                [
                    'route' => 'api.v1.auth.otp.resend',
                    'name' => 'Resend code',
                    'screen' => '**OTP** screen (purpose `verify_phone`) and **Reset password** screen (purpose `reset_password`), when the user taps **Resend code** after the countdown.',
                ],
                [
                    'route' => 'api.v1.auth.token',
                    'name' => 'Log in',
                    'screen' => '**Login** screen, when the user taps **Log in**.',
                    'next' => 'Save `token` in secure storage, call **Save push token**, then go to Home.',
                ],
                [
                    'route' => 'api.v1.auth.forgot-password',
                    'name' => 'Forgot password',
                    'screen' => '**Forgot password** screen, when the user taps **Send code**.',
                    'next' => 'Open the Reset password screen with the phone filled in.',
                ],
                [
                    'route' => 'api.v1.auth.reset-password',
                    'name' => 'Reset password',
                    'screen' => '**Reset password** screen, when the user taps **Save**.',
                    'next' => 'Show "Password changed" and go to Login.',
                ],
            ],
        ],

        [
            'name' => '3. Account',
            'description' => <<<'MD'
                The logged-in user's screens:

                1. **Home** and **Profile**: call **Get my account** on open (and pull to refresh). It gives the name, phone, account type, the national ID status and, for business accounts, the business profile status.
                2. **Change password** screen: current password, new password, new password again → **Change password**.
                3. **Settings › Log out**: first **Remove push token** (Push notifications), then **Log out**, then clear the stored token and go to Login.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.auth.me',
                    'name' => 'Get my account',
                    'screen' => '**Home** and **Profile** screens, on open and on pull to refresh; also after the app starts with a stored token.',
                    'next' => 'For business accounts, show the business profile status from `business_profile`; show the national ID status from `national_id`.',
                ],
                [
                    'route' => 'api.v1.auth.password.update',
                    'name' => 'Change password',
                    'screen' => '**Change password** screen, when the user taps **Save**.',
                    'next' => 'Show "Password changed" and go back. Other phones of this user are logged out.',
                ],
                [
                    'route' => 'api.v1.auth.logout',
                    'name' => 'Log out',
                    'screen' => '**Settings**, when the user taps **Log out** (after **Remove push token**).',
                    'next' => 'Clear the stored token and go to Login.',
                ],
            ],
        ],

        [
            'name' => '4. Push notifications',
            'description' => <<<'MD'
                Keep the FCM push token on the server:

                1. After every successful login, get the FCM token and call **Save push token** with the platform and app version.
                2. When FCM gives a new token (`onTokenRefresh`), call **Save push token** again.
                3. On logout, call **Remove push token** **before** **Log out**, while the login is still valid.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.auth.devices.store',
                    'name' => 'Save push token',
                    'screen' => 'No screen: right after login, and whenever FCM refreshes the token.',
                ],
                [
                    'route' => 'api.v1.auth.devices.destroy',
                    'name' => 'Remove push token',
                    'screen' => 'No screen: on **Log out**, just before calling **Log out**.',
                ],
            ],
        ],

        [
            'name' => '5. Business profile',
            'description' => <<<'MD'
                Business accounts only (`account_type` = `business`):

                1. **Business profile** screen: on open → **Get business profile**. A 404 means there is no profile yet: show the form.
                2. Show the status banner: **pending** ("waiting for review"), **approved**, or **rejected** with `rejection_reason`.
                3. **Business profile form**: load the type picker with **Get business types**. On first submit → **Create business profile** (multipart, with both national ID sides and, optionally, the commercial register (السجل التجاري) and the tax card (البطاقة الضريبية)). Later edits → **Update business profile** (JSON, no files).
                4. **Documents** screen: one row per document with its current file; **Upload** or **Replace** → **Upload document**.
                5. Changing the business name, type or tax number, or any document, sends an approved or rejected profile back to pending: warn before saving.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.public.business-types.index',
                    'name' => 'Get business types',
                    'screen' => '**Business profile form**, on open, to fill the business type picker.',
                ],
                [
                    'route' => 'api.v1.client.business-profile.show',
                    'name' => 'Get business profile',
                    'screen' => '**Business profile** screen, on open and on pull to refresh.',
                ],
                [
                    'route' => 'api.v1.client.business-profile.store',
                    'name' => 'Create business profile',
                    'screen' => '**Business profile form**, the first time the user taps **Submit**.',
                    'next' => 'Open the Business profile screen with the "waiting for review" banner.',
                ],
                [
                    'route' => 'api.v1.client.business-profile.update',
                    'name' => 'Update business profile',
                    'screen' => '**Business profile form** in edit mode, when the user taps **Save**.',
                ],
                [
                    'route' => 'api.v1.client.business-profile.documents.store',
                    'name' => 'Upload document',
                    'screen' => '**Documents** screen, when the user picks a file for one document.',
                ],
            ],
        ],

        [
            'name' => '6. National ID',
            'description' => <<<'MD'
                Optional identity check for any customer, from **Profile** or **Settings**:

                1. Read the current status from **Get my account** (`national_id.status`: none, `pending`, `approved`, `rejected` with `rejection_reason`).
                2. **National ID** screen: take or pick the front and back photos → **Upload national ID**.
                3. The status becomes `pending` until staff review it. A rejected user can upload again.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.client.national-id.store',
                    'name' => 'Upload national ID',
                    'screen' => '**National ID** screen (from Profile or Settings), when the user taps **Submit** with both photos.',
                    'next' => 'Show "waiting for review" from `national_id.status`.',
                ],
            ],
        ],

    ],

];
