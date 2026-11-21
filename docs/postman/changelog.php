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
