<?php

/*
|--------------------------------------------------------------------------
| Postman: React Admin Panel
|--------------------------------------------------------------------------
*/

return [

    'name' => 'React admin panel',
    'auth' => 'cookie',

    'description' => <<<'MD'
        The admin panel for Tayari staff.

        - **Login:** Sanctum session cookie, like the website: **Get CSRF cookie**, then **Log in**.
        - **Postman setup:** open **Cookies › Domains Allowlist** and add the host of `{{appUrl}}` (for example `localhost`). Otherwise this folder's script can't read the `XSRF-TOKEN` cookie and requests answer **419**.
        - **Roles:** `super_admin` (everything), `admin` and `ops` (view and review), `support` (view only). Each action needs a permission: `business_profiles.view`, `business_profiles.review`, `national_ids.view`, `national_ids.review`.
        - Build the menu and buttons from `permissions` in **Get my account** only: hide what the user can't do. A super admin gets every permission in the list. `roles` is for display (for example the role badge in the top bar), never for access checks.
        MD,

    'folders' => [

        [
            'name' => '1. Login',
            'description' => <<<'MD'
                1. On panel load, call **Get CSRF cookie**.
                2. **Admin login** page: phone and password → **Log in**.
                3. Call **Get my account**. A user with an empty `permissions` list can't use the panel: show "not allowed" and log them out.
                4. Build the side menu and buttons from `permissions` only (for example, show **Business profiles** only with `business_profiles.view`, and the Approve and Reject buttons only with `business_profiles.review`). A super admin gets every permission. Show `roles` only as a label.
                MD,
            'requests' => [
                [
                    'route' => 'sanctum.csrf-cookie',
                    'name' => 'Get CSRF cookie',
                    'screen' => 'When the admin panel loads, before **Log in**, and again after a 419.',
                ],
                [
                    'route' => 'api.v1.auth.login',
                    'name' => 'Log in',
                    'screen' => '**Admin login** page, when the staff member clicks **Log in**.',
                    'next' => 'Call **Get my account** and build the menu from `permissions`.',
                ],
            ],
        ],

        [
            'name' => '2. Business profiles review',
            'description' => <<<'MD'
                Needs `business_profiles.view` to see, `business_profiles.review` to decide:

                1. **Review queue** page with tabs **Pending**, **Approved**, **Rejected** → **List business profiles** with `status`, paged.
                2. Clicking a row opens the **Review details** page with the item from the list: business data, links, the owner (`user`), and the documents opened from their signed `url` in an image or PDF viewer.
                3. **Approve** → **Approve business profile**.
                4. **Reject** opens a dialog with a required reason → **Reject business profile**.
                5. After either, go back to the queue; the profile leaves the Pending tab.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.business-profiles.index',
                    'name' => 'List business profiles',
                    'screen' => '**Review queue** page, on open, on tab change (`status`) and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.business-profiles.approve',
                    'name' => 'Approve business profile',
                    'screen' => '**Review details** page, when the staff member clicks **Approve** (confirm first).',
                ],
                [
                    'route' => 'api.v1.admin.business-profiles.reject',
                    'name' => 'Reject business profile',
                    'screen' => '**Review details** page, when the staff member confirms the **Reject** dialog with a reason.',
                ],
            ],
        ],

        [
            'name' => '3. National IDs review',
            'description' => <<<'MD'
                Needs `national_ids.view` to see, `national_ids.review` to decide:

                1. **Pending national IDs** page → **List pending national IDs**, paged.
                2. Clicking a row opens **Review details** with the item from the list: the client's name and phone, and the front and back images from their signed `url`.
                3. **Verify** → **Verify national ID**.
                4. **Reject** opens a dialog with a required reason → **Reject national ID**.
                5. After either, the ID leaves the list.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.national-ids.index',
                    'name' => 'List pending national IDs',
                    'screen' => '**Pending national IDs** page, on open and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.national-ids.verify',
                    'name' => 'Verify national ID',
                    'screen' => '**Review details** page, when the staff member clicks **Verify** (confirm first).',
                ],
                [
                    'route' => 'api.v1.admin.national-ids.reject',
                    'name' => 'Reject national ID',
                    'screen' => '**Review details** page, when the staff member confirms the **Reject** dialog with a reason.',
                ],
            ],
        ],

        [
            'name' => '4. Account',
            'description' => <<<'MD'
                1. **Get my account** on every panel load: who is logged in, `permissions` for the menu and buttons, and `roles` for display (a 401 means show the login page).
                2. **Change password** page → **Change password**.
                3. **Log out** in the top bar → **Log out**, then show the login page.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.auth.me',
                    'name' => 'Get my account',
                    'screen' => 'Every panel load, to show the user in the top bar and build the menu.',
                    'next' => 'Show or hide menu items and buttons from `permissions`.',
                ],
                [
                    'route' => 'api.v1.auth.password.update',
                    'name' => 'Change password',
                    'screen' => '**Change password** page, when the staff member clicks **Save**.',
                ],
                [
                    'route' => 'api.v1.auth.logout',
                    'name' => 'Log out',
                    'screen' => '**Top bar › Log out**.',
                    'next' => 'Show the login page.',
                ],
            ],
        ],

    ],

];
