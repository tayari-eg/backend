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
        - Build the folders in order: app start, sign up and login, account, push notifications, then the business and national ID screens, then the **Tayar mode** folders, then **Client mode · Categories** and **Client mode · Orders**.
        - **Reopening the app:** `last_mode` in **Get my account** says which mode to open (`tayar` only works while `tayar_profile.status` is `approved`).

        ## Onboarding

        1. **Splash** → **App start** (**Get app config**).
        2. **Choice** screen: **Tayar** or **Business owner** (individual customers come later).
        3. **Sign up** screen: name, phone, password, password again → **Sign up** with `register_as` = `tayar` or `business`.
        4. **OTP** screen when `phone_verification_required` is true → **Verify phone**, then **Log in**.
        5. **Get my account**, then open the screen for `next_step`:
           - `complete_business_profile` → the business profile form (**Get business types**, **Create business profile**);
           - `business_under_review` / `business_rejected` → **Get business profile** (show `rejection_reason` when rejected);
           - `complete_tayar_application` → the tayar application form (**Get vehicle types**, **Apply to be a tayar**);
           - `tayar_under_review` / `tayar_rejected` → **Get my tayar application** (show `rejection_reason`, and allow changes from `can_reapply_at`);
           - `ready` → the home screen.
        6. **Tayars:** approval doesn't change the mode. When `register_as` = `tayar`, `next_step` = `ready` and `last_mode` = `client`, call **Switch mode** with `tayar` once, then open the tayar home and **Go online**.

        Always decide the screen from `next_step` after **Log in** or on app start, never from local state: it still works after a reinstall or a login on another phone.
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

                1. **Sign up** screen, after the **Tayar** / **Business owner** choice: name, phone, password, password again. Submit → **Sign up** with `register_as` (`tayar` or `business`).
                2. If the answer has `phone_verification_required: true`, open the **OTP** screen; otherwise go to **Login**.
                3. **OTP** screen: a :otp_length-digit code input and a **Resend code** button with a countdown that starts at `resend_available_in` seconds. Submit → **Verify phone**; resend → **Resend code** (purpose `verify_phone`), then restart the countdown at :otp_cooldown_seconds seconds.
                4. After the phone is verified, go to **Login** (verifying does not log in).
                5. **Login** screen: phone and password → **Log in**. Save the token, register the push token (**Push notifications**), then open the screen for `next_step` (see **Onboarding** in the app folder).
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
                    'next' => 'Save `token` in secure storage, call **Save push token**, then open the screen for `data.next_step`.',
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
                4. Ask for notification permission (iOS, and Android 13 and later) before getting the token.

                **What the app receives.** Every push is sent to all of the user's saved devices, through FCM:

                - `notification.title` and `notification.body`: already in the user's language (`locale`), so show them as they are.
                - `data`: every value is a **string**. There is always a `type`; each type adds its own keys. Ignore a `type` the app does not know. The types so far:

                | `data.type` | Sent to | Other keys | Open |
                | --- | --- | --- | --- |
                | `test` | the user (server `push:test` command) | none | nothing |
                | `order_offer` | a tayar, for a new offer | `order_id`, `offer_id` | the work screen, then **Get my offers** at once (the offer lasts :offer_timeout_seconds seconds) |
                | `order_accepted` | the sender, when a tayar accepts | `order_id` | **Order details** (client mode) |
                | `order_no_tayar` | the sender, when no tayar accepted in time and the order was cancelled | `order_id` | **Order details** (client mode) |
                | `order_cancelled` | the tayar of an accepted order the sender or staff cancelled | `order_id`, `cancelled_by_type` (`sender` or `staff`) | **My order** (tayar mode) |
                | `order_picked_up` | the sender, when the tayar picked the order up | `order_id` | **Order details** (client mode) |
                | `order_on_the_way` | the sender, when the receiver confirmed and the order is on the way | `order_id` | **Order details** (client mode) |
                | `order_delivered` | the sender, when the receiver's delivery code was accepted | `order_id` | **Order details** (client mode) |
                | `order_not_delivered` | the sender; also the tayar when staff close the order (receiver unreachable) | `order_id` | **Order details** (client mode) or **My order** (tayar mode) |
                | `wallet_payout_recorded` | a tayar or business Tayari paid | `wallet_id`, `posting_id` | **Wallet** (tayar or client mode) |
                | `wallet_cash_received` | a tayar whose cash hand-in staff recorded | `wallet_id`, `posting_id` | **Wallet** (tayar mode) |
                | `wallet_adjusted` | the owner of a wallet staff adjusted, or whose payout or hand-in staff reversed | `wallet_id`, `posting_id` | **Wallet** (tayar or client mode) |
                | `wallet_signup_bonus` | a newly approved tayar | `wallet_id`, `posting_id` | **Wallet** (tayar mode) |
                - Android uses the default notification channel (no `channel_id` is sent). If the app later creates its own channel, the server can send its id.

                **Handling it:**

                - App open (`FirebaseMessaging.onMessage`): FCM shows nothing by itself, so show a local notification or an in-app banner.
                - App in the background (`onMessageOpenedApp`) or closed (`getInitialMessage()` on start): when the user taps the notification, open the screen for `data.type`. Ignore a `type` the app does not know.

                A token that FCM reports as unregistered or invalid is removed on the server, so keep calling **Save push token** after login and on every token refresh.
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
                Business accounts (`account_type` = `business`; new sign ups have `register_as` = `business`):

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
                4. Approval doesn't change the mode: when `register_as` = `tayar`, `next_step` = `ready` and `last_mode` = `client`, call **Switch mode** with `tayar` once, without asking the tayar.
                5. Switching to client mode while online sets the tayar offline and closes the work session.
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
                The tayar home screen toggle, with the mode picker:

                1. The tayar home is only shown in tayar mode (after **Switch mode** with `tayar`; a newly approved tayar with `last_mode` = `client` switches once first).
                2. **Online** toggle on → the **Choose mode** sheet → **Get modes**: one card per mode with `name` and `description`.
                3. After the tayar picks a mode, ask for what it needs, from its flags:
                   - `requires_area` → the **Area picker** → **Get areas** (circles on the map from `center` and `radius_km`);
                   - `requires_radius` → a **radius slider** from `radius_km.min` to `radius_km.max` km;
                   - `requires_destination` → a **destination picker** on the map (address, lat, lng).
                4. Read the GPS position → **Go online** with `mode_id` and the field the mode needs. Then start the background location service (**Location updates**).
                5. **Change mode** while online → the same sheet, then **Go online** again with the new settings: the server ends the old work session and starts a new one. Same settings only move the position.
                6. **Online** toggle off → **Go offline**, then stop the location service. While the tayar holds an accepted, picked-up or ongoing order, going offline (and switching to client mode) answers 422: keep working and keep the location service running.
                7. `is_online` in **Get my account** (`tayar_profile.is_online`) tells the toggle state when the app opens; `work_session.mode` in the **Go online** response tells the current mode.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.public.modes.index',
                    'name' => 'Get modes',
                    'screen' => '**Choose mode** sheet, when the tayar turns the **Online** toggle on or taps **Change mode**.',
                ],
                [
                    'route' => 'api.v1.public.areas.index',
                    'name' => 'Get areas',
                    'screen' => '**Area picker**, after the tayar picks a mode with `requires_area`.',
                ],
                [
                    'route' => 'api.v1.tayar.online',
                    'name' => 'Go online',
                    'screen' => '**Choose mode** sheet, when the tayar confirms the mode and its settings.',
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

        [
            'name' => '13. Client mode · Categories',
            'description' => <<<'MD'
                The category picker for a new order (the rest of the New order flow is in **14. Client mode · Orders**):

                1. **New order › Category** step → **Get categories**: main categories with `icon_url`, each with its `children`.
                2. The sender picks a main category, then one of its subcategories: the order is placed in the subcategory.
                3. `is_food` marks food categories; food orders only go to vehicles that can carry food.
                4. Load the list again when the user changes the language.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.public.categories.index',
                    'name' => 'Get categories',
                    'screen' => '**New order › Category** step, on open.',
                ],
            ],
        ],

        [
            'name' => '14. Client mode · Orders',
            'description' => <<<'MD'
                Sending and following orders, for **approved business accounts** (`next_step` = `ready`; individual customers come later). Other accounts get **403** with a message: show it with a link to the business profile.

                1. **New order** flow: category (**Get categories**, folder 13) → pickup and drop-off on the map → **Estimate price** (call it again whenever the category or a point changes) → receiver, goods value, who pays the shipping, details → **Create order** with the `shipping_fee` the user saw.
                2. Make one `idempotency_key` (UUID) when the New order screen opens and keep it for every retry of **Create order**. A 200 means the order already existed: open it as if it were new.
                3. A 422 on `shipping_fee` means the price changed: show the new price from the message, ask again, and send the new `shipping_fee` with the same key.
                4. **My orders** screen: two tabs, Active and Past → **Get my orders** with `status` = `active` or `past`. Infinite scroll with `cursor` = `meta.next_cursor`.
                5. **Order details** → **Get order**. Show **Cancel order** while `can_cancel` is true.
                6. **Cancel** dialog → **Get cancel reasons**, then **Cancel order** with the picked reason and an optional note.
                7. While `status` = `looking_for_tayar`, **Order details** shows "Looking for a tayar…" until `search_ends_at`, and calls **Get order** again every 10 seconds while it is open (and when an `order_accepted` or `order_no_tayar` push arrives).
                8. When a tayar accepts (`status` = `pending`), show the `tayar` card: name, a call button for `phone`, and the vehicle (`type`, `model`, `color`, `plate_number`). **Cancel order** stays until pickup: free until `free_cancel_until` (:free_cancel_seconds seconds), then the **Cancel** dialog shows the fee from `cancel_fee_now`, owed to the tayar.
                9. `cancelled_by_type` = `system`: no tayar accepted in time. Show "No tayar found" with a **Send again** button that opens New order with the same details and a new `idempotency_key`.
                10. From acceptance to delivery, **Order details** shows the tayar on a map from `tayar.location` (with how old `updated_at` is) and the steps: picked up (with the pickup photos), on the way, delivered or not delivered (with the reason). Poll **Get order** every 10 to 15 seconds while the screen is open, and on `order_picked_up`, `order_on_the_way`, `order_delivered` and `order_not_delivered` pushes. There is no ETA.
                11. After pickup nobody can cancel. The receiver gets the codes and the tracking link by SMS (only once a real SMS provider is configured); a **Share tracking link** button can build it from `tracking_token`. Delivery does not check payment yet.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.client.orders.estimate',
                    'name' => 'Estimate price',
                    'screen' => '**New order › Price** step, after both points are on the map, and again when the category or a point changes.',
                ],
                [
                    'route' => 'api.v1.client.orders.store',
                    'name' => 'Create order',
                    'screen' => '**New order › Confirm** step, when the user taps **Send order**.',
                    'next' => 'Open **Order details** for the returned order (201 or 200).',
                ],
                [
                    'route' => 'api.v1.client.orders.index',
                    'name' => 'Get my orders',
                    'screen' => '**My orders** screen, Active and Past tabs, on open, on pull to refresh and on scroll.',
                ],
                [
                    'route' => 'api.v1.client.orders.show',
                    'name' => 'Get order',
                    'screen' => '**Order details** screen, on open, every 10 seconds while the order is looking for a tayar, and on an `order_accepted` or `order_no_tayar` push.',
                ],
                [
                    'route' => 'api.v1.public.order-reasons.index',
                    'name' => 'Get cancel reasons',
                    'screen' => '**Cancel order** dialog, on open (`type` = `cancel`).',
                ],
                [
                    'route' => 'api.v1.client.orders.cancel',
                    'name' => 'Cancel order',
                    'screen' => '**Cancel order** dialog, when the user confirms.',
                    'next' => 'Show the order as cancelled and move it to the Past tab.',
                ],
            ],
        ],

        [
            'name' => '15. Tayar mode · Offers',
            'description' => <<<'MD'
                New orders near the tayar, while online (folder 10). There are no live updates: the app polls, and a push is only a nudge.

                1. **Work** screen, while online: call **Get my offers** every 5 to 10 seconds, and at once when an `order_offer` push arrives or the app comes back to the front.
                2. Show one card per offer: the earning (`tayar_earning`), the distance to the pickup, the pickup and drop-off on a map, the category (food badge when `is_food`), and a countdown from `expires_in_seconds`. Remove the card at 0.
                3. Several tayars get the same order at once (up to :offers_per_round per round). **Accept** → **Accept offer**: a 200 means the order is mine; open **My order** (folder 16). A 422 on `offer` means it expired, someone else took it, or the tayar is busy: remove the card and show the message.
                4. **Reject** → **Reject offer** (no reason). That order never comes back to this tayar.
                5. Offers belong to the current work session: going offline or changing the mode settings ends them.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.tayar.offers.index',
                    'name' => 'Get my offers',
                    'screen' => '**Work** screen, every 5 to 10 seconds while online, and on an `order_offer` push.',
                ],
                [
                    'route' => 'api.v1.tayar.offers.accept',
                    'name' => 'Accept offer',
                    'screen' => '**Offer card**, when the tayar taps **Accept**.',
                    'next' => 'Open **My order** for the returned order.',
                ],
                [
                    'route' => 'api.v1.tayar.offers.reject',
                    'name' => 'Reject offer',
                    'screen' => '**Offer card**, when the tayar taps **Reject**.',
                    'next' => 'Remove the card.',
                ],
            ],
        ],

        [
            'name' => '16. Tayar mode · My orders',
            'description' => <<<'MD'
                The orders the tayar accepted. Pickup and delivery steps come in a later version.

                1. **My orders** screen: two tabs, Active and Past → **Get my orders** with `status` = `active` or `past`. Infinite scroll with `cursor` = `meta.next_cursor`.
                2. **My order** screen → **Get my order**: the pickup (with `contact_phone`), the sender (`phone`, `business_name`), the receiver (`name`, `phone`), the drop-off and my earning. Call buttons for each phone while the order is active; once it is past, the phones are null and only the names show.
                3. In the zone and area modes a tayar holds :max_active_orders accepted order at a time (long distance: up to :max_active_orders_long_distance); while at the limit, no new offers arrive.
                4. An `order_cancelled` push means the sender or staff cancelled the order (`cancelled_by_type`): reload it, show it as cancelled and stop the trip. When the sender cancelled after the free period, `cancel_fee` shows what the sender owes the tayar (added to the tayar's wallet).
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.tayar.orders.index',
                    'name' => 'Get my orders',
                    'screen' => '**My orders** screen (tayar mode), Active and Past tabs, on open, on pull to refresh and on scroll.',
                ],
                [
                    'route' => 'api.v1.tayar.orders.show',
                    'name' => 'Get my order',
                    'screen' => '**My order** screen, on open and on an `order_cancelled` push.',
                ],
            ],
        ],

        [
            'name' => '17. Tayar mode · Delivery',
            'description' => <<<'MD'
                The steps after accepting an order (folder 16 shows it). Every step sends the current GPS `lat`/`lng`.

                1. **At the pickup:** photograph the items → **Confirm pickup** with 1 to 4 photos (multipart). The receiver then gets the delivery and refusal codes and the tracking link by SMS; `receiver_codes.sent_at` shows when. From now on nobody can cancel.
                2. **Call the receiver.** When they confirm they are there today → **Receiver confirmed**: the order is on the way. Receiver unreachable or not there today: don't confirm; contact support, who closes the order as not delivered (you get an `order_not_delivered` push).
                3. **At the drop-off:** the receiver reads out the delivery code → **Deliver** with `code`. If they refuse the order, they read out the refusal code → **Get not-delivered reasons**, then **Mark not delivered** with `code`, the reason and an optional note. Not delivered is final for now; contact support about returning the goods.
                4. **Wrong codes:** both steps share :code_max_attempts tries; `receiver_codes.attempts_left` counts down. When `receiver_codes.problem` is `codes_locked` or `codes_expired`, ask the receiver to request new codes from their tracking link, or contact support.
                5. While holding an order the tayar can't go offline or switch to client mode, and the server never sets them offline: keep the location service running, because the sender and the receiver see the last position.
                6. Delivery does not check payment yet: the payment phase comes later. The receiver gets codes only once a real SMS provider is configured on the server.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.tayar.orders.pickup',
                    'name' => 'Confirm pickup',
                    'screen' => '**My order › Pickup** step, when the tayar taps **Confirm pickup** after taking the photos.',
                    'next' => 'Show the **Call the receiver** step.',
                ],
                [
                    'route' => 'api.v1.tayar.orders.receiver-confirmed',
                    'name' => 'Receiver confirmed',
                    'screen' => '**My order › Call the receiver** step, when the tayar taps **Receiver is there today**.',
                    'next' => 'Show the drop-off on the map and the **Enter code** step.',
                ],
                [
                    'route' => 'api.v1.tayar.orders.deliver',
                    'name' => 'Deliver',
                    'screen' => '**My order › Enter code** step, when the tayar enters the delivery code.',
                    'next' => 'Show the order as delivered and move it to the Past tab.',
                ],
                [
                    'route' => 'api.v1.public.order-reasons.index',
                    'name' => 'Get not-delivered reasons',
                    'screen' => '**Not delivered** dialog, on open (`type` = `not_delivered`).',
                ],
                [
                    'route' => 'api.v1.tayar.orders.not-delivered',
                    'name' => 'Mark not delivered',
                    'screen' => '**Not delivered** dialog, when the tayar enters the refusal code and picks a reason.',
                    'next' => 'Show the order as not delivered and move it to the Past tab.',
                ],
            ],
        ],

        [
            'name' => '18. Tayar mode · Wallet',
            'description' => <<<'MD'
                The tayar's money with Tayari. Every order is cash for now: the tayar collects `amount_to_collect` from the receiver and owes it to Tayari.

                1. **Wallet** screen → **Get my wallet**: show `balance` big (green when Tayari owes the tayar, red when the tayar owes), with `payable_balance` (what Tayari can pay now) and `cash_to_hand_in` (cash to hand in).
                2. A delivered order adds `delivery_earning` and takes `cash_collected`, so the balance goes negative while the tayar holds cash. When `blocked_from_offers` is true (owing more than EGP :max_cash_owed), show a banner "Hand in your cash to get new orders": no new offers arrive and accepting one answers 422. The tayar can still finish current orders and stay online.
                3. Cash is handed in to Tayari outside the app (office or transfer); staff record it and the tayar gets a `wallet_cash_received` push. Payouts are made by staff too (`wallet_payout_recorded` push); there is no withdrawal button yet.
                4. `locked_bonus`: the EGP :signup_bonus welcome bonus counts in the balance but can't be paid out until `bonus_unlock_delivered_orders` delivered orders. Earnings and bonuses can be paid out from :available_from_hour:00 Cairo time on the day they are earned (`available_at`).
                5. **Transactions** list under the balance → **Get my wallet transactions**, newest first, infinite scroll with `cursor` = `meta.next_cursor`, with type filter chips. A row with an `order` opens **My order**. Reload the wallet and the list on any `wallet_*` push.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.tayar.wallet.show',
                    'name' => 'Get my wallet',
                    'screen' => '**Wallet** screen (tayar mode), on open, on pull to refresh and on any `wallet_*` push.',
                ],
                [
                    'route' => 'api.v1.tayar.wallet.transactions',
                    'name' => 'Get my wallet transactions',
                    'screen' => '**Wallet** screen (tayar mode), the transactions list: on open, on scroll and when a type chip changes.',
                ],
            ],
        ],

        [
            'name' => '19. Client mode · Wallet',
            'description' => <<<'MD'
                A business's money with Tayari (business accounts only; others get 403). Every order is cash for now: the tayar collects from the receiver and hands it to Tayari, and Tayari pays the business.

                1. **Wallet** screen → **Get my wallet**: `balance` (what Tayari owes the business; negative when the business owes fees) and `payable_balance`.
                2. A delivered order adds `goods_payout` (the goods value) and, when the business pays the shipping, takes `shipping_fee`. A cancellation after the free period takes `cancel_fee_charged`.
                3. Tayari pays by transfer, recorded by staff (`payout`, with the transfer `reference`); the business gets a `wallet_payout_recorded` push. There is no withdrawal button yet.
                4. **Transactions** list → **Get my wallet transactions**, newest first, infinite scroll with `cursor` = `meta.next_cursor`. A row with an `order` opens **Order details**.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.client.wallet.show',
                    'name' => 'Get my business wallet',
                    'screen' => '**Wallet** screen (client mode, business accounts), on open, on pull to refresh and on any `wallet_*` push.',
                ],
                [
                    'route' => 'api.v1.client.wallet.transactions',
                    'name' => 'Get my business wallet transactions',
                    'screen' => '**Wallet** screen (client mode), the transactions list: on open and on scroll.',
                ],
            ],
        ],

    ],

];
