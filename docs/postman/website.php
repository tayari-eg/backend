<?php

/*
|--------------------------------------------------------------------------
| Postman: React Website
|--------------------------------------------------------------------------
*/

return [

    'name' => 'React website',
    'auth' => 'cookie',

    'description' => <<<'MD'
        The website for customers and businesses.

        - **Login:** Sanctum session cookie. Call **Get CSRF cookie**, then **Log in**; after that the browser sends the session cookie by itself.
        - **Every request:** with cookies (`credentials: 'include'` / `withCredentials: true`) and the `X-XSRF-TOKEN` header from the `XSRF-TOKEN` cookie (axios adds it by itself). This folder's script does the same in Postman and sets `X-Platform: web`, `Origin` and `Referer` to `{{frontendUrl}}`.
        - **Postman setup:** open **Cookies › Domains Allowlist** and add the host of `{{appUrl}}` (for example `localhost`). Otherwise this folder's script can't read the `XSRF-TOKEN` cookie and requests answer **419**.
        - The website has no push notifications: it never calls the devices endpoints.
        MD,

    'folders' => [

        [
            'name' => '1. Before login',
            'description' => <<<'MD'
                Data the public pages need:

                1. Load **Get business types** for the onboarding wizard that comes after login (the type is sent with the business profile, not at sign up).
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.public.business-types.index',
                    'name' => 'Get business types',
                    'screen' => 'The first step of the **Business onboarding** wizard.',
                ],
            ],
        ],

        [
            'name' => '2. Sign up and login',
            'description' => <<<'MD'
                From a visitor to a logged-in user:

                1. On app load (and before **Log in**), call **Get CSRF cookie**.
                2. **Sign up** page: name, phone, password, password again → **Sign up** with `register_as` = `business` (the website signs up business owners only for now).
                3. If `phone_verification_required` is true, open the **OTP** page; otherwise go to **Login**.
                4. **OTP** page: code input and a **Resend code** link with a countdown from `resend_available_in` → **Verify phone** / **Resend code**.
                5. **Login** page: phone and password → **Log in**. Then load **Get my account** and open the page for `next_step` (`complete_business_profile` → the onboarding wizard; `business_under_review` / `business_rejected` → the business profile page; `complete_tayar_application` / `tayar_under_review` / `tayar_rejected` → a "continue in the Tayari app" page, since the website is for business accounts only; `ready` → the account).
                6. **Forgot password** page → **Forgot password**, then always the **Reset password** page → **Reset password**, then Login.

                A 419 answer means the CSRF cookie is old: call **Get CSRF cookie** again and retry once.
                MD,
            'requests' => [
                [
                    'route' => 'sanctum.csrf-cookie',
                    'name' => 'Get CSRF cookie',
                    'screen' => 'When the website loads, before **Log in**, and again after a 419.',
                ],
                [
                    'route' => 'api.v1.auth.register',
                    'name' => 'Sign up',
                    'screen' => '**Sign up** page, when the visitor clicks **Create account**.',
                    'next' => 'If `phone_verification_required` is true, open the OTP page with the countdown from `resend_available_in`; otherwise go to Login.',
                ],
                [
                    'route' => 'api.v1.auth.verify-phone',
                    'name' => 'Verify phone',
                    'screen' => '**OTP** page, when the visitor submits the code.',
                    'next' => 'Show "Phone verified" and go to Login.',
                ],
                [
                    'route' => 'api.v1.auth.otp.resend',
                    'name' => 'Resend code',
                    'screen' => '**OTP** page (purpose `verify_phone`) and **Reset password** page (purpose `reset_password`), when the countdown is over and the visitor clicks **Resend code**.',
                ],
                [
                    'route' => 'api.v1.auth.login',
                    'name' => 'Log in',
                    'screen' => '**Login** page, when the visitor clicks **Log in** (after **Get CSRF cookie**).',
                    'next' => 'Call **Get my account**, then open the page for `next_step`.',
                ],
                [
                    'route' => 'api.v1.auth.forgot-password',
                    'name' => 'Forgot password',
                    'screen' => '**Forgot password** page, when the visitor clicks **Send code**.',
                    'next' => 'Open the Reset password page with the phone filled in.',
                ],
                [
                    'route' => 'api.v1.auth.reset-password',
                    'name' => 'Reset password',
                    'screen' => '**Reset password** page, when the visitor clicks **Save**.',
                    'next' => 'Show "Password changed" and go to Login.',
                ],
            ],
        ],

        [
            'name' => '3. Account',
            'description' => <<<'MD'
                The logged-in user's pages:

                1. On every page load, call **Get my account** to know who is logged in (a 401 means not logged in: show Login). Use it for the **account menu** and the **Account** page.
                2. **Change password** page → **Change password**.
                3. **Log out** in the account menu → **Log out**, then go to the home page.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.auth.me',
                    'name' => 'Get my account',
                    'screen' => '**Account menu** on every page load, and the **Account** page.',
                ],
                [
                    'route' => 'api.v1.auth.password.update',
                    'name' => 'Change password',
                    'screen' => '**Change password** page, when the user clicks **Save**.',
                ],
                [
                    'route' => 'api.v1.auth.logout',
                    'name' => 'Log out',
                    'screen' => '**Account menu › Log out**.',
                    'next' => 'Go to the home page. The session cookie is no longer valid.',
                ],
            ],
        ],

        [
            'name' => '4. Business onboarding',
            'description' => <<<'MD'
                Business accounts (`account_type` = `business`; new sign ups have `register_as` = `business`):

                1. After login, if **Get my account** has `business_profile: null`, open the **Onboarding wizard**.
                2. Wizard step 1, **Business details**: business type (from **Get business types**), name, description, average daily orders, tax registration number, and links.
                3. Wizard step 2, **Documents**: national ID front and back (required), commercial register (السجل التجاري, `commercial_register`) and tax card (البطاقة الضريبية, `tax_card`) (optional).
                4. On **Finish**, send everything in one request → **Create business profile**.
                5. **Business profile** page → **Get business profile**, with a status banner: pending, approved, or rejected with the reason.
                6. **Edit business profile** page → **Update business profile** (no files). **Documents** page → **Upload document** per document.
                7. Changing the name, type or tax number, or any document, sends an approved or rejected profile back to pending: warn before saving.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.client.business-profile.store',
                    'name' => 'Create business profile',
                    'screen' => '**Onboarding wizard**, when the user clicks **Finish** on the last step.',
                    'next' => 'Open the Business profile page with the "waiting for review" banner.',
                ],
                [
                    'route' => 'api.v1.client.business-profile.show',
                    'name' => 'Get business profile',
                    'screen' => '**Business profile** page, on open.',
                ],
                [
                    'route' => 'api.v1.client.business-profile.update',
                    'name' => 'Update business profile',
                    'screen' => '**Edit business profile** page, when the user clicks **Save**.',
                ],
                [
                    'route' => 'api.v1.client.business-profile.documents.store',
                    'name' => 'Upload document',
                    'screen' => '**Documents** page, when the user picks a file for one document.',
                ],
            ],
        ],

        [
            'name' => '5. National ID',
            'description' => <<<'MD'
                Optional identity check for any customer:

                1. Show the status from **Get my account** (`national_id.status`: none, `pending`, `approved`, `rejected` with `rejection_reason`).
                2. **National ID** page: front and back files → **Upload national ID**.
                3. The status becomes `pending` until staff review it. A rejected user can upload again.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.client.national-id.store',
                    'name' => 'Upload national ID',
                    'screen' => '**National ID** page, when the user clicks **Submit** with both files.',
                ],
            ],
        ],

        [
            'name' => '6. Categories',
            'description' => <<<'MD'
                The category picker for a new order (the rest of the New order page is in **7. Orders**):

                1. **New order › Category** step → **Get categories**: main categories with `icon_url`, each with its `children`.
                2. The user picks a main category, then one of its subcategories: the order is placed in the subcategory.
                3. Load the list again when the user changes the language.
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
            'name' => '7. Orders',
            'description' => <<<'MD'
                Sending and following orders, for **approved business accounts** (`next_step` = `ready`; individual customers come later). Other accounts get **403** with a message: show it with a link to the business profile.

                1. **New order** page: category (**Get categories**, folder 6) → pickup and drop-off on the map → **Estimate price** (call it again whenever the category or a point changes) → receiver, goods value, who pays the shipping, details → **Create order** with the `shipping_fee` the user saw.
                2. Make one `idempotency_key` (UUID) when the New order page opens and keep it for every retry of **Create order**. A 200 means the order already existed: open it as if it were new.
                3. A 422 on `shipping_fee` means the price changed: show the new price from the message, ask again, and send the new `shipping_fee` with the same key.
                4. **My orders** page: two tabs, Active and Past → **Get my orders** with `status` = `active` or `past`. Load more with `cursor` = `meta.next_cursor`.
                5. **Order details** → **Get order**. Show **Cancel order** while `can_cancel` is true.
                6. **Cancel** dialog → **Get cancel reasons**, then **Cancel order** with the picked reason and an optional note.
                7. While `status` = `looking_for_tayar`, **Order details** shows "Looking for a tayar…" until `search_ends_at`, and calls **Get order** again every 10 seconds while the page is open.
                8. When a tayar accepts (`status` = `pending`), show the `tayar` card: name, phone and the vehicle (`type`, `model`, `color`, `plate_number`). **Cancel order** stays until pickup: free until `free_cancel_until` (:free_cancel_seconds seconds), then the **Cancel** dialog shows the fee from `cancel_fee_now`, owed to the tayar.
                9. `cancelled_by_type` = `system`: no tayar accepted in time. Show "No tayar found" with a **Send again** button that opens New order with the same details and a new `idempotency_key`.
                10. From acceptance to delivery, **Order details** shows the tayar on a map from `tayar.location` (with how old `updated_at` is) and the steps: picked up (with the pickup photos), on the way, delivered or not delivered (with the reason). Reload every 10 to 15 seconds while the page is open. There is no ETA.
                11. After pickup nobody can cancel. The receiver gets the codes and the tracking link by SMS (only once a real SMS provider is configured); a **Copy tracking link** button can build it from `tracking_token` (folder 8). Delivery does not check payment yet.
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
                    'screen' => '**New order › Confirm** step, when the user clicks **Send order**.',
                    'next' => 'Open **Order details** for the returned order (201 or 200).',
                ],
                [
                    'route' => 'api.v1.client.orders.index',
                    'name' => 'Get my orders',
                    'screen' => '**My orders** page, Active and Past tabs, on open, and on **Load more**.',
                ],
                [
                    'route' => 'api.v1.client.orders.show',
                    'name' => 'Get order',
                    'screen' => '**Order details** page, on open, every 10 seconds while the order is looking for a tayar, and every 10 to 15 seconds while it is accepted, picked up or on the way.',
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
            'name' => '8. Tracking page',
            'description' => <<<'MD'
                The receiver's public tracking page at `/track/{token}` (the link in the receiver's SMS; set the same URL in `TRACKING_URL` on the server). No login.

                1. On open → **Get tracking**: the status, the business name, the tayar's first name and vehicle, and a map with the tayar's last position while the goods are on the way. Reload every 15 seconds while `is_finished` is false, and show how old `location.updated_at` is.
                2. The codes are never shown on the page: the receiver has them in the SMS. While `can_resend_codes` is true, a **Send me new codes** button → **Resend codes** (at most :resend_codes_per_hour an hour for an order); the old codes stop working.
                3. After the order ends, the page shows the final status without a position.
                4. 404 means a wrong or broken link.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.public.tracking.show',
                    'name' => 'Get tracking',
                    'screen' => '**Tracking** page, on open and every 15 seconds until `is_finished`.',
                ],
                [
                    'route' => 'api.v1.public.tracking.resend-codes',
                    'name' => 'Resend codes',
                    'screen' => '**Tracking** page, when the receiver taps **Send me new codes**.',
                    'next' => 'Show "A new SMS is on its way".',
                ],
            ],
        ],

        [
            'name' => '9. Wallet',
            'description' => <<<'MD'
                The business's money with Tayari (business accounts only; others get 403). Every order is cash for now: the tayar collects from the receiver and hands it to Tayari, and Tayari pays the business.

                1. **Wallet** page → **Get my wallet**: `balance` (what Tayari owes the business; negative when it owes fees) and `payable_balance`.
                2. A delivered order adds `goods_payout` (the goods value) and, when the business pays the shipping, takes `shipping_fee`. A cancellation after the free period takes `cancel_fee_charged`.
                3. Tayari pays by transfer, recorded by staff (`payout` rows show the transfer `reference`). There is no withdrawal button yet.
                4. **Transactions** table → **Get my wallet transactions**, newest first, with a **Load more** button using `cursor` = `meta.next_cursor` and a type filter. A row with an `order` links to the order page.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.client.wallet.show',
                    'name' => 'Get my wallet',
                    'screen' => '**Wallet** page, on open.',
                ],
                [
                    'route' => 'api.v1.client.wallet.transactions',
                    'name' => 'Get my wallet transactions',
                    'screen' => '**Wallet** page, the transactions table: on open, on **Load more** and when the type filter changes.',
                ],
            ],
        ],

    ],

];
