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

                1. When the visitor picks **Business** on the sign-up page, load **Get business types** for the onboarding wizard that comes after login (the type is sent with the business profile, not at sign up).
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.public.business-types.index',
                    'name' => 'Get business types',
                    'screen' => '**Sign up** page when **Business** is chosen, and the first step of the **Business onboarding** wizard.',
                ],
            ],
        ],

        [
            'name' => '2. Sign up and login',
            'description' => <<<'MD'
                From a visitor to a logged-in user:

                1. On app load (and before **Log in**), call **Get CSRF cookie**.
                2. **Sign up** page: name, phone, password, password again, and **Customer** or **Business** → **Sign up**.
                3. If `phone_verification_required` is true, open the **OTP** page; otherwise go to **Login**.
                4. **OTP** page: code input and a **Resend code** link with a countdown from `resend_available_in` → **Verify phone** / **Resend code**.
                5. **Login** page: phone and password → **Log in**. Then load **Get my account** and go to the account (business accounts without a profile go to the onboarding wizard).
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
                    'next' => 'Call **Get my account**, then go to the account page (or the onboarding wizard for a business without a profile).',
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
                Business accounts only (`account_type` = `business`):

                1. After login, if **Get my account** has `business_profile: null`, open the **Onboarding wizard**.
                2. Wizard step 1, **Business details**: business type (from **Get business types**), name, description, average daily orders, tax registration number, and links.
                3. Wizard step 2, **Documents**: national ID front and back (required), commercial register (السجل التجاري, `tax_register`) and tax card (البطاقة الضريبية, `tax_card`) (optional).
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

    ],

];
