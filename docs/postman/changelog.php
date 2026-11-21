<?php

/*
|--------------------------------------------------------------------------
| Postman Docs Changelog
|--------------------------------------------------------------------------
|
| Every change to the API or to docs/postman adds an entry at the top with
| a raised version (semver), the date (YYYY-MM-DD) and plain-English
| change lines for the frontend and Flutter teams. Newest first.
|
| - major: a new API version (/api/v2) or a breaking change;
| - minor: new endpoints, new fields, new folders or new screens in the docs;
| - patch: wording fixes, clearer docs, corrected limits or examples.
|
*/

return [

    [
        'version' => '1.1.0',
        'date' => '2026-11-21',
        'changes' => [
            'Admin panel, Business types: list active and inactive types with search, show, create, update, activate, deactivate, and delete (only when no business profile uses the type; otherwise a 422 asks to deactivate it instead). Names must be unique in each language, ignoring letter case.',
            'Admin panel, Users: list customer and business accounts with search by name, full phone or part of a phone, and filters (account type, status, phone verified, national ID status, business profile status); user details with the national ID and the business profile; block with a reason and unblock.',
            'Admin panel, Staff (super admins only): list staff, create a staff member (admin, ops or support), change a role, block and unblock. Nobody can change their own role or block themselves, and the last active super admin can\'t be blocked or demoted.',
            'Admin panel, Business profiles review: the Review details page now uses the new **Get business profile** endpoint, which also returns `reviewed_by`.',
            'New permissions: `business_types.view`, `business_types.manage`, `users.view`, `users.block` and `staff.manage`. Admin has every permission except `staff.manage`; ops gets the business types and users view permissions and `users.block`; support gets the business types and users view permissions.',
            'Login rate limits, clearer wording: tries are counted per phone number from the same IP address, plus an hourly limit per phone number from any IP address.',
            'Example bodies: the `reason` example in the reject and block requests is now a neutral placeholder.',
        ],
    ],

    [
        'version' => '1.0.0',
        'date' => '2026-11-21',
        'changes' => [
            'App config and force update: minimum and latest app versions per platform, the phone verification switch and the server time; old mobile apps get 426 and must update.',
            'Sign up, with the phone verification switch: when it is on, an SMS code (OTP) is sent, then Verify phone and Resend code.',
            'Login: a Bearer token for the Flutter app, a session cookie for the React website and admin panel.',
            'Forgot password and Reset password with an SMS code.',
            'Account: Get my account, Change password and Log out.',
            'Push tokens: save and remove the FCM token of a device (Flutter app).',
            'Business types for the business type picker.',
            'Business profile and documents: create, show and update the profile, and upload the national ID sides, the commercial register and the tax card.',
            'Optional national ID upload for any customer.',
            'Admin review of business profiles and national IDs: list, approve or verify, and reject with a reason.',
            'Roles and permissions for the admin menu: `roles` and `permissions` are always returned (empty for customers), a super admin gets every permission, and the menu and buttons are built from `permissions` only.',
            'Postman setup for the cookie flow: allowlist the host of `{{appUrl}}` under Cookies › Domains Allowlist, then call Get CSRF cookie before Log in.',
            'Rate limits per phone number and per IP address (users on the same network share the IP limit).',
        ],
    ],

];
