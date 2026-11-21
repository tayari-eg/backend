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
        - **Permissions:** `business_profiles.view`, `business_profiles.review`, `national_ids.view`, `national_ids.review`, `business_types.view`, `business_types.manage`, `users.view`, `users.block`, `staff.manage`.
        - **Roles:** `super_admin` (everything, and the only role with `staff.manage`); `admin` (everything except `staff.manage`); `ops` (view business types and users, block users, and review business profiles and national IDs); `support` (view only: business profiles, national IDs, business types and users).
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
                2. Clicking a row opens the **Review details** page → **Get business profile**: business data, links, the owner (`user`), who reviewed it (`reviewed_by`), and the documents opened from their signed `url` in an image or PDF viewer.
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
                    'route' => 'api.v1.admin.business-profiles.show',
                    'name' => 'Get business profile',
                    'screen' => '**Review details** page, on open (and to refresh expired document links).',
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
            'name' => '4. Users',
            'description' => <<<'MD'
                Customer and business accounts. Needs `users.view` to see, `users.block` to block and unblock:

                1. **Users** page: a search box (name or phone) and filters for account type, status, phone verified, national ID status and business profile status → **List users**, paged, newest first.
                2. Clicking a row opens the **User details** page → **Get user**: account data, the national ID images and the business profile with its links and documents.
                3. **Block** opens a dialog with a required reason → **Block user**. **Unblock** (on a blocked user) → **Unblock user**.
                4. Staff accounts never show here: manage them under **Staff**.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.users.index',
                    'name' => 'List users',
                    'screen' => '**Users** page, on open, on search, on filter change and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.users.show',
                    'name' => 'Get user',
                    'screen' => '**User details** page, on open.',
                ],
                [
                    'route' => 'api.v1.admin.users.block',
                    'name' => 'Block user',
                    'screen' => '**User details** page, when the staff member confirms the **Block** dialog with a reason.',
                    'next' => 'Show the blocked banner with the reason and the **Unblock** button.',
                ],
                [
                    'route' => 'api.v1.admin.users.unblock',
                    'name' => 'Unblock user',
                    'screen' => '**User details** page, when the staff member clicks **Unblock** (confirm first).',
                ],
            ],
        ],

        [
            'name' => '5. Business types',
            'description' => <<<'MD'
                The business types customers pick from. Needs `business_types.view` to see, `business_types.manage` to change:

                1. **Business types** page with tabs **Active** and **Inactive** → **List business types** with `is_active`, an optional `search`, paged, in sort order.
                2. **Add** opens the form page or dialog (Arabic name, English name, active, sort order) → **Create business type**.
                3. **Edit** loads the type → **Get business type**, then **Save** → **Update business type**.
                4. **Activate** / **Deactivate** on a row → **Activate business type** / **Deactivate business type**; the row moves to the other tab.
                5. **Delete** opens a confirmation dialog → **Delete business type**. A type used by business profiles can't be deleted: the dialog shows the "deactivate it instead" message with a **Deactivate** button. Offer **Delete** only when `business_profiles_count` is 0.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.business-types.index',
                    'name' => 'List business types',
                    'screen' => '**Business types** page, on open, on tab change (`is_active`), on search and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.business-types.show',
                    'name' => 'Get business type',
                    'screen' => '**Edit business type** form, on open.',
                ],
                [
                    'route' => 'api.v1.admin.business-types.store',
                    'name' => 'Create business type',
                    'screen' => '**Add business type** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and reload the list.',
                ],
                [
                    'route' => 'api.v1.admin.business-types.update',
                    'name' => 'Update business type',
                    'screen' => '**Edit business type** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and update the row.',
                ],
                [
                    'route' => 'api.v1.admin.business-types.activate',
                    'name' => 'Activate business type',
                    'screen' => '**Business types** page (Inactive tab), when the staff member clicks **Activate** on a row.',
                ],
                [
                    'route' => 'api.v1.admin.business-types.deactivate',
                    'name' => 'Deactivate business type',
                    'screen' => '**Business types** page (Active tab), when the staff member clicks **Deactivate** on a row, or **Deactivate** in the delete dialog.',
                ],
                [
                    'route' => 'api.v1.admin.business-types.destroy',
                    'name' => 'Delete business type',
                    'screen' => '**Business types** page, when the staff member confirms the **Delete** dialog.',
                    'next' => 'Remove the row. On 422, keep the dialog open with the message and a **Deactivate** button.',
                ],
            ],
        ],

        [
            'name' => '6. Staff',
            'description' => <<<'MD'
                Admin panel accounts. Super admins only (`staff.manage`): hide this menu item for everyone else.

                1. **Staff** page → **List staff**, paged, with each member's role and status.
                2. **Add staff** form (name, phone, password, password again, role `admin`, `ops` or `support`) → **Create staff member**.
                3. **Change role** on a row opens a role picker → **Change staff role**.
                4. **Block** opens a dialog with a required reason → **Block staff member**. **Unblock** → **Unblock staff member**.
                5. Hide **Change role** and **Block** on the logged-in user's own row. The last active super admin can't be blocked or demoted.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.staff.index',
                    'name' => 'List staff',
                    'screen' => '**Staff** page, on open and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.staff.store',
                    'name' => 'Create staff member',
                    'screen' => '**Add staff** form, when the super admin clicks **Save**.',
                    'next' => 'Close the form and reload the list.',
                ],
                [
                    'route' => 'api.v1.admin.staff.role.update',
                    'name' => 'Change staff role',
                    'screen' => '**Staff** page, when the super admin picks a new role for a row and confirms.',
                ],
                [
                    'route' => 'api.v1.admin.staff.block',
                    'name' => 'Block staff member',
                    'screen' => '**Staff** page, when the super admin confirms the **Block** dialog with a reason.',
                ],
                [
                    'route' => 'api.v1.admin.staff.unblock',
                    'name' => 'Unblock staff member',
                    'screen' => '**Staff** page, when the super admin clicks **Unblock** on a blocked row (confirm first).',
                ],
            ],
        ],

        [
            'name' => '7. Account',
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
