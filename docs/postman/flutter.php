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
        The mobile app for customers, business owners and tayars (Android and iOS). One app, two modes: client mode and tayar mode (see the **Tayar mode** folders).

        - **Login:** Bearer token from **Sign up and login › Log in**. Its script saves the token to `{{token}}`, and every request in this folder sends it.
        - **Headers:** this folder adds `X-Platform: {{platform}}` (`android` by default; set `ios` in the environment) and `X-App-Version: {{appVersion}}`.
        - Build the folders in order: app start, sign up and login, account, push notifications, then the business and national ID screens, then the **Tayar mode** folders.
        - **Reopening the app:** `last_mode` in **Get my account** says which mode to open (`tayar` only works while `tayar_profile.status` is `approved`).
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

        [
            'name' => '7. Tayar mode · Become a tayar',
            'description' => <<<'MD'
                Any customer (individual or business) can apply to deliver orders:

                1. **Become a tayar** entry (account menu, when `tayar_profile` in **Get my account** is null) → open the application form.
                2. **Application form**, step 1: national ID number (14 digits).
                3. Step 2, **Vehicle**: the type picker from **Get vehicle types**, then plate number, model, colour and year. Hide the plate and the licences when the type has `requires_license` = false (a bicycle).
                4. Step 3, **Documents**: national ID front and back, criminal record (الفيش), a personal photo, the driving licence, the vehicle licence, and 1 to 4 vehicle photos.
                5. On **Send**, everything goes in one multipart request → **Apply to be a tayar**. Then show **Application status**.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.public.vehicle-types.index',
                    'name' => 'Get vehicle types',
                    'screen' => '**Application form**, vehicle step, to fill the vehicle type picker.',
                ],
                [
                    'route' => 'api.v1.client.tayar-profile.store',
                    'name' => 'Apply to be a tayar',
                    'screen' => '**Application form**, when the user taps **Send** on the last step.',
                    'next' => 'Open **Application status** with the "waiting for review" state.',
                ],
            ],
        ],

        [
            'name' => '8. Tayar mode · Application status',
            'description' => <<<'MD'
                After applying:

                1. **Application status** screen → **Get my tayar application**: `pending` (waiting for review), `approved` (show **Switch to tayar mode**), or `rejected` with `rejection_reason`.
                2. A rejected applicant can fix the application from `can_reapply_at`; before that, show the date and disable the edit buttons.
                3. **Edit** → **Update my tayar application** (national ID number) or **Replace tayar document** (one document).
                4. Changing the national ID number, the vehicle type or plate, or any document sends an approved or rejected application back to pending: warn before saving.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.client.tayar-profile.show',
                    'name' => 'Get my tayar application',
                    'screen' => '**Application status** screen, on open and on pull to refresh.',
                ],
                [
                    'route' => 'api.v1.client.tayar-profile.update',
                    'name' => 'Update my tayar application',
                    'screen' => '**Edit application** screen, when the user saves a new national ID number.',
                ],
                [
                    'route' => 'api.v1.client.tayar-profile.documents.store',
                    'name' => 'Replace tayar document',
                    'screen' => '**Edit application** screen, when the user picks a new file for one document.',
                ],
            ],
        ],

        [
            'name' => '9. Tayar mode · Switch mode',
            'description' => <<<'MD'
                Moving between client mode and tayar mode:

                1. **Mode switch** in the account menu (only when `tayar_profile.status` is `approved`) → **Switch mode** with `tayar` or `client`.
                2. The app reopens in `last_mode` next time.
                3. Switch to tayar mode **before** going online: **Go online** answers 403 while the app is in client mode.
                4. Switching to client mode while online sets the tayar offline and closes the work session.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.auth.mode.update',
                    'name' => 'Switch mode',
                    'screen' => '**Account menu › Switch to tayar mode** / **Switch to client mode**.',
                    'next' => 'Open the tayar home (or the client home) and keep `last_mode` for the next start.',
                ],
            ],
        ],

        [
            'name' => '10. Tayar mode · Go online and offline',
            'description' => <<<'MD'
                The tayar home screen toggle:

                1. The tayar home is only shown in tayar mode (after **Switch mode** with `tayar`). **Online** toggle on → read the GPS position → **Go online**. Then start the background location service (**Location updates**).
                2. **Online** toggle off → **Go offline**, then stop the location service.
                3. `is_online` in **Get my account** (`tayar_profile.is_online`) tells the toggle state when the app opens.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.tayar.online',
                    'name' => 'Go online',
                    'screen' => '**Tayar home**, when the tayar turns the **Online** toggle on.',
                    'next' => 'Start sending **Update location**.',
                ],
                [
                    'route' => 'api.v1.tayar.offline',
                    'name' => 'Go offline',
                    'screen' => '**Tayar home**, when the tayar turns the **Online** toggle off.',
                    'next' => 'Stop the background location service.',
                ],
            ],
        ],

        [
            'name' => '11. Tayar mode · Location updates',
            'description' => <<<'MD'
                A background service while online:

                1. Send **Update location** every 5 to 10 seconds, also when the app is in the background. At most :tayar_location_per_minute a minute.
                2. On **429**, wait for `Retry-After` seconds and send only the latest position (drop the ones in between).
                3. On **422** `errors.status` (offline), stop the service and show the offline state: the server sets a tayar offline after :tayar_offline_minutes minutes without a location.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.tayar.location',
                    'name' => 'Update location',
                    'screen' => '**Background location service**, every 5 to 10 seconds while online.',
                ],
            ],
        ],

        [
            'name' => '12. Tayar mode · Vehicle',
            'description' => <<<'MD'
                The tayar's vehicle (one vehicle for now, sent with the application):

                1. **My vehicle** screen shows `vehicle` from **Get my tayar application**: type, plate, model, colour, year, status, licence and photos.
                2. **Edit** → **Update vehicle** (no files). Changing the type or the plate sends the application back to pending.
                3. **Replace licence** or **Replace photos** → **Replace vehicle document** (the photos are replaced all at once, 1 to 4).
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.client.tayar-profile.vehicle.update',
                    'name' => 'Update vehicle',
                    'screen' => '**Edit vehicle** screen, when the tayar taps **Save**.',
                ],
                [
                    'route' => 'api.v1.client.tayar-profile.vehicle.documents.store',
                    'name' => 'Replace vehicle document',
                    'screen' => '**My vehicle** screen, when the tayar picks a new licence or new photos.',
                ],
            ],
        ],

    ],

];
