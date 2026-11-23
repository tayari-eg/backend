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
        - **Permissions:** `business_profiles.view`, `business_profiles.review`, `national_ids.view`, `national_ids.review`, `business_types.view`, `business_types.manage`, `users.view`, `users.block`, `staff.manage`, `tayars.view`, `tayars.review`, `vehicle_types.view`, `vehicle_types.manage`, `categories.view`, `categories.manage`, `modes.view`, `modes.manage`, `areas.view`, `areas.manage`, `pricing_rules.view`, `pricing_rules.manage`.
        - **Roles:** `super_admin` (everything, and the only role with `staff.manage`); `admin` (everything except `staff.manage`); `ops` (view business types, vehicle types, categories, modes, areas, pricing rules and users, block users, and review business profiles, national IDs and tayar applications); `support` (view only: business profiles, national IDs, business types, vehicle types, categories, modes, areas, pricing rules, users and tayars).
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
            'name' => '7. Tayar applications review',
            'description' => <<<'MD'
                People who applied to deliver orders. Needs `tayars.view` to see, `tayars.review` to decide:

                1. **Tayar applications** page with tabs **Pending**, **Approved**, **Rejected**, a vehicle type filter and a search box (name or phone) → **List tayar applications**, paged, oldest first.
                2. Clicking a row opens the **Review details** page → **Get tayar application**: the national ID number, the personal documents (national ID, criminal record, personal photo, driving licence), the vehicle with its licence and photos, the applicant, and the history of earlier reviews.
                3. **Approve** → **Approve tayar application**. The vehicle is approved with it.
                4. **Reject** opens a dialog with a required reason → **Reject tayar application**. The applicant can fix the application :tayar_reapply_hours hours later.
                5. After either, go back to the list; the application leaves the Pending tab.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.tayar-applications.index',
                    'name' => 'List tayar applications',
                    'screen' => '**Tayar applications** page, on open, on tab change (`status`), on filter or search change and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.tayar-applications.show',
                    'name' => 'Get tayar application',
                    'screen' => '**Review details** page, on open (and to refresh expired document links).',
                ],
                [
                    'route' => 'api.v1.admin.tayar-applications.approve',
                    'name' => 'Approve tayar application',
                    'screen' => '**Review details** page, when the staff member clicks **Approve** (confirm first).',
                ],
                [
                    'route' => 'api.v1.admin.tayar-applications.reject',
                    'name' => 'Reject tayar application',
                    'screen' => '**Review details** page, when the staff member confirms the **Reject** dialog with a reason.',
                ],
            ],
        ],

        [
            'name' => '8. Tayars',
            'description' => <<<'MD'
                Approved tayars. Needs `tayars.view`:

                1. **Tayars** page with filters **Online now**, vehicle type and **Blocked**, and a search box → **List tayars**, paged, newest first.
                2. Clicking a row opens the **Tayar details** page → **Get tayar**: documents, vehicle, the last position (`location`) and the review history.
                3. **Block** and **Unblock** use the users endpoints (**Users › Block user / Unblock user**, with `user.id`) and need `users.block`. A blocked tayar is out of tayar mode at once.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.tayars.index',
                    'name' => 'List tayars',
                    'screen' => '**Tayars** page, on open, on filter or search change and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.tayars.show',
                    'name' => 'Get tayar',
                    'screen' => '**Tayar details** page, on open.',
                ],
            ],
        ],

        [
            'name' => '9. Vehicle types',
            'description' => <<<'MD'
                The vehicle types tayars pick from. Needs `vehicle_types.view` to see, `vehicle_types.manage` to change:

                1. **Vehicle types** page with tabs **Active** and **Inactive** → **List vehicle types** with `is_active`, an optional `search`, paged, in sort order.
                2. **Add** opens the form (Arabic name, English name, needs a licence, can carry food, active, sort order) → **Create vehicle type**.
                3. **Edit** loads the type → **Get vehicle type**, then **Save** → **Update vehicle type**.
                4. **Activate** / **Deactivate** on a row → **Activate vehicle type** / **Deactivate vehicle type**.
                5. **Delete** opens a confirmation dialog → **Delete vehicle type**. A type used by vehicles can't be deleted: the dialog shows the "deactivate it instead" message with a **Deactivate** button. Offer **Delete** only when `vehicles_count` is 0.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.vehicle-types.index',
                    'name' => 'List vehicle types',
                    'screen' => '**Vehicle types** page, on open, on tab change (`is_active`), on search and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.vehicle-types.show',
                    'name' => 'Get vehicle type',
                    'screen' => '**Edit vehicle type** form, on open.',
                ],
                [
                    'route' => 'api.v1.admin.vehicle-types.store',
                    'name' => 'Create vehicle type',
                    'screen' => '**Add vehicle type** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and reload the list.',
                ],
                [
                    'route' => 'api.v1.admin.vehicle-types.update',
                    'name' => 'Update vehicle type',
                    'screen' => '**Edit vehicle type** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and update the row.',
                ],
                [
                    'route' => 'api.v1.admin.vehicle-types.activate',
                    'name' => 'Activate vehicle type',
                    'screen' => '**Vehicle types** page (Inactive tab), when the staff member clicks **Activate** on a row.',
                ],
                [
                    'route' => 'api.v1.admin.vehicle-types.deactivate',
                    'name' => 'Deactivate vehicle type',
                    'screen' => '**Vehicle types** page (Active tab), when the staff member clicks **Deactivate** on a row, or **Deactivate** in the delete dialog.',
                ],
                [
                    'route' => 'api.v1.admin.vehicle-types.destroy',
                    'name' => 'Delete vehicle type',
                    'screen' => '**Vehicle types** page, when the staff member confirms the **Delete** dialog.',
                    'next' => 'Remove the row. On 422, keep the dialog open with the message and a **Deactivate** button.',
                ],
            ],
        ],

        [
            'name' => '10. Categories',
            'description' => <<<'MD'
                The categories and subcategories senders pick for an order (two levels). Needs `categories.view` to see, `categories.manage` to change:

                1. **Categories** page with tabs **Active** and **Inactive** → **List categories** with `main_only` = true, `is_active`, an optional `search`, paged, in sort order.
                2. Open a main category → **List categories** with `parent_id` for its subcategories.
                3. **Add** opens the form (Arabic name, English name, parent (none for a main category), food, active, sort order) → **Create category**, then **Upload category icon** when an icon was picked.
                4. **Edit** loads the category → **Get category**, then **Save** → **Update category**. Changing the parent moves it; a category with subcategories can't move under another one.
                5. **Icon**: pick an image → **Upload category icon** (jpg, png or webp); **Remove icon** → **Remove category icon**.
                6. **Activate** / **Deactivate** on a row → **Activate category** / **Deactivate category**. A deactivated main category hides its subcategories from the apps.
                7. **Delete** opens a confirmation dialog → **Delete category**. Offer **Delete** only when `children_count` is 0.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.categories.index',
                    'name' => 'List categories',
                    'screen' => '**Categories** page, on open, on tab change (`is_active`), on search, on page change, and with `parent_id` when a main category is opened.',
                ],
                [
                    'route' => 'api.v1.admin.categories.show',
                    'name' => 'Get category',
                    'screen' => '**Edit category** form, on open.',
                ],
                [
                    'route' => 'api.v1.admin.categories.store',
                    'name' => 'Create category',
                    'screen' => '**Add category** form, when the staff member clicks **Save**.',
                    'next' => 'Upload the picked icon with **Upload category icon**, then close the form and reload the list.',
                ],
                [
                    'route' => 'api.v1.admin.categories.update',
                    'name' => 'Update category',
                    'screen' => '**Edit category** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and update the row (reload the list when the parent changed).',
                ],
                [
                    'route' => 'api.v1.admin.categories.icon.store',
                    'name' => 'Upload category icon',
                    'screen' => '**Add** / **Edit category** form, after picking an icon image.',
                ],
                [
                    'route' => 'api.v1.admin.categories.icon.destroy',
                    'name' => 'Remove category icon',
                    'screen' => '**Edit category** form, when the staff member clicks **Remove icon**.',
                ],
                [
                    'route' => 'api.v1.admin.categories.activate',
                    'name' => 'Activate category',
                    'screen' => '**Categories** page (Inactive tab), when the staff member clicks **Activate** on a row.',
                ],
                [
                    'route' => 'api.v1.admin.categories.deactivate',
                    'name' => 'Deactivate category',
                    'screen' => '**Categories** page (Active tab), when the staff member clicks **Deactivate** on a row, or **Deactivate** in the delete dialog.',
                ],
                [
                    'route' => 'api.v1.admin.categories.destroy',
                    'name' => 'Delete category',
                    'screen' => '**Categories** page, when the staff member confirms the **Delete** dialog.',
                    'next' => 'Remove the row. On 422, keep the dialog open with the message and a **Deactivate** button.',
                ],
            ],
        ],

        [
            'name' => '11. Modes',
            'description' => <<<'MD'
                The three fixed ways a tayar works: long distance, zone and area. Needs `modes.view` to see, `modes.manage` to change:

                1. **Modes** page → **List modes** (no Add or Delete: modes are fixed).
                2. **Edit** loads the mode → **Get mode** (names and description in both languages, food allowed, sort order), then **Save** → **Update mode**.
                3. **Activate** / **Deactivate** on a row → **Activate mode** / **Deactivate mode**. A deactivated mode leaves the tayar app's mode picker; tayars already online in it keep working.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.modes.index',
                    'name' => 'List modes',
                    'screen' => '**Modes** page, on open and on search.',
                ],
                [
                    'route' => 'api.v1.admin.modes.show',
                    'name' => 'Get mode',
                    'screen' => '**Edit mode** form, on open.',
                ],
                [
                    'route' => 'api.v1.admin.modes.update',
                    'name' => 'Update mode',
                    'screen' => '**Edit mode** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and update the row.',
                ],
                [
                    'route' => 'api.v1.admin.modes.activate',
                    'name' => 'Activate mode',
                    'screen' => '**Modes** page, when the staff member clicks **Activate** on a row.',
                ],
                [
                    'route' => 'api.v1.admin.modes.deactivate',
                    'name' => 'Deactivate mode',
                    'screen' => '**Modes** page, when the staff member clicks **Deactivate** on a row.',
                ],
            ],
        ],

        [
            'name' => '12. Areas',
            'description' => <<<'MD'
                The districts tayars pick in the area mode, each a centre point and a radius. Needs `areas.view` to see, `areas.manage` to change:

                1. **Areas** page with tabs **Active** and **Inactive** → **List areas** with `is_active`, an optional `search`, paged, in sort order.
                2. **Add** opens the form (Arabic and English name, Arabic and English city, the centre picked on a map, the radius in km with the circle drawn, active, sort order) → **Create area**.
                3. **Edit** loads the area → **Get area**, then **Save** → **Update area**.
                4. **Activate** / **Deactivate** on a row → **Activate area** / **Deactivate area**.
                5. **Delete** opens a confirmation dialog → **Delete area**. Offer **Delete** only when `work_sessions_count` is 0.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.areas.index',
                    'name' => 'List areas',
                    'screen' => '**Areas** page, on open, on tab change (`is_active`), on search and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.areas.show',
                    'name' => 'Get area',
                    'screen' => '**Edit area** form, on open.',
                ],
                [
                    'route' => 'api.v1.admin.areas.store',
                    'name' => 'Create area',
                    'screen' => '**Add area** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and reload the list.',
                ],
                [
                    'route' => 'api.v1.admin.areas.update',
                    'name' => 'Update area',
                    'screen' => '**Edit area** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and update the row.',
                ],
                [
                    'route' => 'api.v1.admin.areas.activate',
                    'name' => 'Activate area',
                    'screen' => '**Areas** page (Inactive tab), when the staff member clicks **Activate** on a row.',
                ],
                [
                    'route' => 'api.v1.admin.areas.deactivate',
                    'name' => 'Deactivate area',
                    'screen' => '**Areas** page (Active tab), when the staff member clicks **Deactivate** on a row, or **Deactivate** in the delete dialog.',
                ],
                [
                    'route' => 'api.v1.admin.areas.destroy',
                    'name' => 'Delete area',
                    'screen' => '**Areas** page, when the staff member confirms the **Delete** dialog.',
                    'next' => 'Remove the row. On 422, keep the dialog open with the message and a **Deactivate** button.',
                ],
            ],
        ],

        [
            'name' => '13. Pricing rules',
            'description' => <<<'MD'
                How the shipping fee will be calculated, per mode and vehicle type. Needs `pricing_rules.view` to see, `pricing_rules.manage` to change:

                1. **Pricing** page → **List pricing rules**, grouped by mode (filter with `mode_id`, `vehicle_type_id`, `is_active`).
                2. **Add** opens the form (mode, vehicle type or "All vehicles", base fare, price per km, minimum fare, food extra, tayar share %, active) → **Create pricing rule**. One rule per mode and vehicle type: the form shows the "edit that rule instead" error otherwise.
                3. **Edit** loads the rule → **Get pricing rule**, then **Save** → **Update pricing rule**.
                4. **Activate** / **Deactivate** on a row → **Activate pricing rule** / **Deactivate pricing rule**.
                5. **Delete** opens a confirmation dialog → **Delete pricing rule**.
                6. Amounts are EGP strings with two decimals: show them as they come, never round them in the panel.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.pricing-rules.index',
                    'name' => 'List pricing rules',
                    'screen' => '**Pricing** page, on open, on filter change and on page change.',
                ],
                [
                    'route' => 'api.v1.admin.pricing-rules.show',
                    'name' => 'Get pricing rule',
                    'screen' => '**Edit pricing rule** form, on open.',
                ],
                [
                    'route' => 'api.v1.admin.pricing-rules.store',
                    'name' => 'Create pricing rule',
                    'screen' => '**Add pricing rule** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and reload the list.',
                ],
                [
                    'route' => 'api.v1.admin.pricing-rules.update',
                    'name' => 'Update pricing rule',
                    'screen' => '**Edit pricing rule** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and update the row.',
                ],
                [
                    'route' => 'api.v1.admin.pricing-rules.activate',
                    'name' => 'Activate pricing rule',
                    'screen' => '**Pricing** page, when the staff member clicks **Activate** on a row.',
                ],
                [
                    'route' => 'api.v1.admin.pricing-rules.deactivate',
                    'name' => 'Deactivate pricing rule',
                    'screen' => '**Pricing** page, when the staff member clicks **Deactivate** on a row.',
                ],
                [
                    'route' => 'api.v1.admin.pricing-rules.destroy',
                    'name' => 'Delete pricing rule',
                    'screen' => '**Pricing** page, when the staff member confirms the **Delete** dialog.',
                    'next' => 'Remove the row.',
                ],
            ],
        ],

        [
            'name' => '14. Account',
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

        [
            'name' => '15. Orders',
            'description' => <<<'MD'
                Following and cancelling orders (staff with `orders.view`; cancelling needs `orders.manage`):

                1. **Orders** page: a table with status, sender, tayar, category, mode and date filters, and a search box (order code or phone) → **List orders**.
                2. **Order details** page → **Get order**: the fee breakdown (the tayar earning and platform fee are estimates until a tayar accepts), the tayar and vehicle once accepted, and the status history with who made each change (no name = the system).
                3. **Offers** tab on the order details page → **List order offers**: which tayars got the order in each round, and who accepted, rejected or let it expire.
                4. **Cancel** button (only while `can_cancel` is true: before pickup, also after a tayar accepted) → a dialog with **Get cancel reasons** and a note → **Cancel order**. Cancelling an accepted order notifies the tayar and never records a fee; a sender who cancelled after the free period shows `cancel_fee` and `cancel_fee_owed_to` (charged to the business's wallet and added to the tayar's).
                5. **Close as not delivered** button (picked up or on the way; `orders.manage`): when the receiver can't be reached → a dialog with **Get not-delivered reasons** and a required note → **Close as not delivered**. The only status staff can force; the sender and the tayar are both notified.
                6. **Resend receiver codes** button (picked up or on the way; `orders.manage`), for a receiver who lost the SMS or hit the lock-out (`receiver_codes.locked`). Staff never see the codes.
                7. The status history shows where the tayar was for each step (`lat`, `lng`), and the pickup photos. Delivery does not check payment yet; receivers get codes only once a real SMS provider is configured.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.orders.index',
                    'name' => 'List orders',
                    'screen' => '**Orders** page, on open and when a filter, the search or the page changes.',
                ],
                [
                    'route' => 'api.v1.admin.orders.show',
                    'name' => 'Get order',
                    'screen' => '**Order details** page, on open.',
                ],
                [
                    'route' => 'api.v1.admin.orders.offers.index',
                    'name' => 'List order offers',
                    'screen' => '**Order details › Offers** tab, on open and when the page changes.',
                ],
                [
                    'route' => 'api.v1.public.order-reasons.index',
                    'name' => 'Get cancel reasons',
                    'screen' => '**Cancel** dialog on the order details page, on open (`type` = `cancel`).',
                ],
                [
                    'route' => 'api.v1.public.order-reasons.index',
                    'name' => 'Get not-delivered reasons',
                    'screen' => '**Close as not delivered** dialog on the order details page, on open (`type` = `not_delivered`).',
                ],
                [
                    'route' => 'api.v1.admin.orders.not-delivered',
                    'name' => 'Close as not delivered',
                    'screen' => '**Order details** page, when the staff member confirms the **Close as not delivered** dialog.',
                    'next' => 'Show the returned order: `status` = `not_delivered` and the new history row.',
                ],
                [
                    'route' => 'api.v1.admin.orders.resend-codes',
                    'name' => 'Resend receiver codes',
                    'screen' => '**Order details** page, when the staff member taps **Resend receiver codes**.',
                ],
                [
                    'route' => 'api.v1.admin.orders.cancel',
                    'name' => 'Cancel order',
                    'screen' => '**Order details** page, when the staff member confirms the **Cancel** dialog.',
                    'next' => 'Show the returned order: `status` = `cancelled` and the new history row.',
                ],
            ],
        ],

        [
            'name' => '16. Wallets',
            'description' => <<<'MD'
                The ledger of every money movement between Tayari, businesses and tayars (`wallets.view` to see, `wallets.settle` for payouts and cash hand-ins, `wallets.adjust` for adjustments and reversals). Every order is cash for now: tayars collect from receivers and owe the cash to Tayari.

                1. **Wallets** page: a table with type and **Owing** filters, a search box (phone or name) and sort by balance → **List wallets**. Tayari's own wallets have `type` = `system` (`revenue` = commission, `bonus_expense`, `adjustments`, `treasury` = money in Tayari's bank, shown as its mirror). All balances add up to zero.
                2. **Wallet details** page → **Get wallet** (with `summary`) and **List wallet transactions** (newest first, **Load more** with `cursor`).
                3. **Record payout** (tayar or business) or **Record cash handed in** (tayar) → a form with amount, reference and note → **Record settlement** with a new `idempotency_key` per form. The amount is capped at `summary.payable_balance` or `summary.cash_to_hand_in`. A tayar owing more than EGP :max_cash_owed gets no new offers until the cash is recorded.
                4. **Adjust** (`wallets.adjust`) → a form with a signed amount, a reason from **List adjustment reasons** (`is_active` = true) and a required note → **Adjust wallet**.
                5. **Reverse** on a transaction row (only while `posting.can_reverse`) → a dialog with a reason and a note → **Reverse posting**. Entries are never edited or deleted; order, fee and bonus entries are corrected with an adjustment.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.wallets.index',
                    'name' => 'List wallets',
                    'screen' => '**Wallets** page, on open and when a filter, the search, the sort or the page changes.',
                ],
                [
                    'route' => 'api.v1.admin.wallets.show',
                    'name' => 'Get wallet',
                    'screen' => '**Wallet details** page, on open and after every settlement, adjustment or reversal.',
                ],
                [
                    'route' => 'api.v1.admin.wallets.transactions',
                    'name' => 'List wallet transactions',
                    'screen' => '**Wallet details** page, the transactions table: on open, on **Load more** and when the type filter changes.',
                ],
                [
                    'route' => 'api.v1.admin.wallets.settlements.store',
                    'name' => 'Record settlement',
                    'screen' => '**Record payout** or **Record cash handed in** form on the wallet details page, when the staff member clicks **Save**.',
                    'next' => 'Close the form and reload the wallet and its transactions.',
                ],
                [
                    'route' => 'api.v1.admin.wallets.adjustments.store',
                    'name' => 'Adjust wallet',
                    'screen' => '**Adjust** form on the wallet details page, when the staff member clicks **Save**.',
                    'next' => 'Close the form and reload the wallet and its transactions.',
                ],
                [
                    'route' => 'api.v1.admin.wallet-postings.reverse',
                    'name' => 'Reverse posting',
                    'screen' => '**Reverse** dialog on a transaction row, when the staff member confirms.',
                    'next' => 'Reload the wallet and its transactions.',
                ],
            ],
        ],

        [
            'name' => '17. Wallet adjustment reasons',
            'description' => <<<'MD'
                The reasons staff pick for adjustments and reversals. Needs `wallets.view` to see, `wallets.adjust` to change:

                1. **Adjustment reasons** page with tabs **Active** and **Inactive** → **List adjustment reasons** with `is_active`, an optional `search`, paged, in sort order.
                2. **Add** opens the form (Arabic name, English name, active, sort order) → **Create adjustment reason**.
                3. **Edit** loads the reason → **Get adjustment reason**, then **Save** → **Update adjustment reason**.
                4. **Activate** / **Deactivate** on a row; the row moves to the other tab.
                5. **Delete** (only when `postings_count` is 0) → **Delete adjustment reason**; a reason in use shows the "deactivate it instead" message.
                MD,
            'requests' => [
                [
                    'route' => 'api.v1.admin.wallet-adjustment-reasons.index',
                    'name' => 'List adjustment reasons',
                    'screen' => '**Adjustment reasons** page, on open, on tab change, on search and on page change; also the reason picker in the **Adjust** and **Reverse** dialogs (`is_active` = true).',
                ],
                [
                    'route' => 'api.v1.admin.wallet-adjustment-reasons.show',
                    'name' => 'Get adjustment reason',
                    'screen' => '**Edit adjustment reason** form, on open.',
                ],
                [
                    'route' => 'api.v1.admin.wallet-adjustment-reasons.store',
                    'name' => 'Create adjustment reason',
                    'screen' => '**Add adjustment reason** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and reload the list.',
                ],
                [
                    'route' => 'api.v1.admin.wallet-adjustment-reasons.update',
                    'name' => 'Update adjustment reason',
                    'screen' => '**Edit adjustment reason** form, when the staff member clicks **Save**.',
                    'next' => 'Close the form and update the row.',
                ],
                [
                    'route' => 'api.v1.admin.wallet-adjustment-reasons.activate',
                    'name' => 'Activate adjustment reason',
                    'screen' => '**Adjustment reasons** page (Inactive tab), when the staff member clicks **Activate** on a row.',
                ],
                [
                    'route' => 'api.v1.admin.wallet-adjustment-reasons.deactivate',
                    'name' => 'Deactivate adjustment reason',
                    'screen' => '**Adjustment reasons** page (Active tab), when the staff member clicks **Deactivate** on a row.',
                ],
                [
                    'route' => 'api.v1.admin.wallet-adjustment-reasons.destroy',
                    'name' => 'Delete adjustment reason',
                    'screen' => '**Adjustment reasons** page, when the staff member confirms the **Delete** dialog.',
                    'next' => 'Remove the row. On 422, keep the dialog open with the message and a **Deactivate** button.',
                ],
            ],
        ],

    ],

];
