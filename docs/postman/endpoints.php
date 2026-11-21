<?php

/*
|--------------------------------------------------------------------------
| Endpoint Documentation
|--------------------------------------------------------------------------
|
| One entry per route name, shared by every app that uses the endpoint.
| Sections: what, who, request, success, errors, notes (Markdown). The
| "Screen" section and an optional "next" line come from the app files.
|
*/

$phoneField = '`phone` (string, required): any Egyptian mobile format (`01000000000`, `+201000000000`, `00201000000000`, spaces, dashes and Arabic digits are fine). The server saves it as `+201XXXXXXXXX`.';
$fileRule = 'jpg, jpeg, png, webp or pdf, up to :max_upload_mb MB';
$throttled = '- **429:** too many attempts from this phone number or this IP address (users on the same network share the IP limit). Wait for `Retry-After` seconds, then let the user retry.';
$upgrade = '- **426 (mobile):** the app is too old. Show the force-update screen.';
$serverError = '- **500:** show a generic error and a retry button.';
$loggedOut = '- **401:** not logged in, or the login expired. Clear the stored login and go to Login.';
$blocked = '- **403:** the account is blocked (`message` says so). Show the message and log the user out.';
$clientOnly = '- **403:** `message` explains why: the account is blocked, the phone is not verified yet (when phone verification is on), or a staff account tried a customer screen. Show the message; for "verify your phone", send the user to the OTP screen.';
$staffOnly = '- **403:** not a staff account, blocked, or missing the permission (`message` says which). Hide this action for users without the permission.';
$userFields = '`id`, `name`, `phone`, `email`, `account_type` (`individual` | `business`), `locale` (`ar` | `en`), `status` (`active` | `blocked`), `is_phone_verified`, `phone_verified_at`, `national_id` (`status`: null | `pending` | `approved` | `rejected`, `rejection_reason`, `verified_at`, `front` and `back` files with a signed `url`), `business_profile` (or null), `roles` (staff role names, for display only; an empty list for customers) and `permissions` (what the user may do; an empty list for customers, every permission for a super admin)';
$profileFields = '`id`, `business_type` (`id`, `name`), `business_name`, `description`, `avg_daily_orders`, `tax_registration_number`, `verification_status` (`pending` | `approved` | `rejected`), `rejection_reason`, `reviewed_at`, `links` (`id`, `type`, `url`), `documents` (`national_id_front`, `national_id_back`, `tax_register` (the commercial register, السجل التجاري), `tax_card` (the tax card, البطاقة الضريبية): each a file with `file_name`, `mime_type`, `size`, `uploaded_at` and a signed `url`, or null), `created_at`, `updated_at`';
$otpCodeError = 'The same error for a wrong code, an expired code, a used code, a code with too many wrong tries (:otp_max_attempts per code) and an unknown phone: `errors.code` = "The code is wrong or has expired…". Show it under the code input and offer **Resend code**.';

return [

    'sanctum.csrf-cookie' => [
        'what' => 'Starts a web session: the server sets the `XSRF-TOKEN` and session cookies in the browser. Not under `/api/v1`.',
        'who' => 'Anyone (web only).',
        'request' => "- `GET {{appUrl}}/sanctum/csrf-cookie`, with cookies (`credentials: 'include'` / `withCredentials: true`).\n- No body.",
        'success' => '**204** with no body. The browser now has the `XSRF-TOKEN` cookie. Send it back as the `X-XSRF-TOKEN` header on the next requests (axios does this by itself).',
        'errors' => $serverError,
        'notes' => "- Call it again when a request answers **419**.\n- In Postman, the folder script copies the cookie into `X-XSRF-TOKEN` for you.",
    ],

    'api.v1.public.app-config' => [
        'what' => 'Returns the app settings the apps need at start: the minimum and latest app versions per platform, whether phone verification is on, and the server time.',
        'who' => 'Anyone, no login. It is never blocked by the app version check, so an old app can always read it.',
        'request' => '- No body or parameters. Send the usual headers.',
        'success' => "**200** with `data`:\n- `min_supported_version`: `android` and `ios` (string or null). Below it, the app must update.\n- `latest_version`: `android` and `ios` (string or null). Below it, an update is available but optional.\n- `phone_verification_required` (boolean): whether sign up needs the OTP step.\n- `server_time`: the server time in UTC.",
        'errors' => $serverError,
        'notes' => "- Compare versions number by number (`1.10.0` is newer than `1.9.0`). Ignore a `+build` suffix.\n- A null version means \"no rule\": don't block or suggest anything.",
    ],

    'api.v1.public.business-types.index' => [
        'what' => 'Lists the active business types (restaurant, pharmacy, …) for the business type picker.',
        'who' => 'Anyone, no login.',
        'request' => "- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page` (optional, starts at 1).\n- `Accept-Language` decides the language of the names.",
        'success' => '**200** with `data`: a list of `{ id, name }` in display order, plus `meta` and `links` for pages.',
        'errors' => "- **422:** `per_page` is out of range.\n{$upgrade}\n{$serverError}",
        'notes' => '- Send the chosen `id` as `business_type_id` in the business profile form, not at sign up. Load the list again when the user changes the language.',
    ],

    'api.v1.auth.register' => [
        'what' => 'Creates a new account. It does not log the user in.',
        'who' => 'Guest (not logged in).',
        'request' => "- `name` (string, required): up to 255 characters.\n- {$phoneField}\n- `password` (string, required): 8 to 64 characters.\n- `password_confirmation` (string, required): the same password again.\n- `account_type` (required): `individual` (customer) or `business`.\n- `Accept-Language` is saved as the account language (used for SMS).",
        'success' => "**201** with:\n- `data`: the new user: {$userFields}.\n- `phone_verification_required` (boolean): when true, an SMS code was sent.\n- `resend_available_in` (number of seconds, or null): how long until **Resend code** may send another code. Null when no code was sent.\n- `message`: \"Your account has been created.\"",
        'errors' => "- **422:** field errors. A phone that already has an account gets `errors.phone` = \"This phone number already has an account. Please log in, or use forgot password.\": show it with links to Login and Forgot password.\n{$throttled}\n{$upgrade}\n{$serverError}",
        'notes' => "- Register never changes an existing account. An unverified user who comes back finishes with **Resend code** and **Verify phone**, not with register again.\n- Limits: :otp_limit_per_phone requests per phone number and :otp_limit_per_ip per IP address every :otp_limit_minutes minutes (users on the same network share the IP limit). **Sign up**, **Resend code** and **Forgot password** share one limit per phone.",
    ],

    'api.v1.auth.verify-phone' => [
        'what' => 'Confirms the phone number with the SMS code. It does not log the user in.',
        'who' => 'Guest.',
        'request' => "- {$phoneField}\n- `code` (string, required): the :otp_length-digit code from the SMS.",
        'success' => '**200** with `message` ("Your phone number has been verified.").',
        'errors' => "- **422:** {$otpCodeError}\n{$throttled}\n{$upgrade}\n{$serverError}",
        'notes' => "- A code is valid for :otp_expires_minutes minutes and allows :otp_max_attempts wrong tries.\n- Limits: :otp_limit_per_phone tries per phone number and :otp_limit_per_ip per IP address every :otp_limit_minutes minutes (users on the same network share the IP limit). **Verify phone** and **Reset password** share one limit per phone.",
    ],

    'api.v1.auth.otp.resend' => [
        'what' => 'Sends a new SMS code for phone verification or for a password reset.',
        'who' => 'Guest.',
        'request' => "- {$phoneField}\n- `purpose` (required): `verify_phone` or `reset_password`.",
        'success' => '**200** with `message` ("If this phone number can receive a code, we have sent one."). Restart the resend countdown at :otp_cooldown_seconds seconds.',
        'errors' => "- **422:** field errors.\n{$throttled}\n{$upgrade}\n{$serverError}",
        'notes' => "- **Same answer for registered and unknown phones**, and for an already verified phone: never tell the user whether the number has an account.\n- Within :otp_cooldown_seconds seconds of the last code, nothing new is sent and the earlier code stays valid. Keep the **Resend** button disabled during the countdown.\n- A new code replaces the old one.\n- Limits: :otp_limit_per_phone requests per phone number and :otp_limit_per_ip per IP address every :otp_limit_minutes minutes (users on the same network share the IP limit). **Sign up**, **Resend code** and **Forgot password** share one limit per phone.",
    ],

    'api.v1.auth.token' => [
        'what' => 'Mobile login. Returns a Bearer token for this device.',
        'who' => 'Guest.',
        'request' => "- {$phoneField}\n- `password` (string, required): up to 64 characters.\n- `device_name` (string, required): up to 100 characters, for example the phone model. It names the token.",
        'success' => "**200** with:\n- `token`: send it as `Authorization: Bearer <token>` on every request. Store it in secure storage.\n- `token_type`: `Bearer`.\n- `data`: the user: {$userFields}.",
        'errors' => "- **422:** wrong phone or password: `errors.phone` = \"These credentials do not match our records.\" (the same for an unknown phone).\n- **403:** the account is blocked, or the phone is not verified while phone verification is on. Show `message`; for \"verify your phone\", open the OTP screen (call **Resend code** first).\n{$throttled}\n{$upgrade}\n{$serverError}",
        'notes' => '- Limits: :login_per_minute tries per minute per phone number and IP address (users on the same network share the IP limit), and :login_per_phone_per_hour per hour per phone.',
    ],

    'api.v1.auth.login' => [
        'what' => 'Web login with a session cookie. No token is returned.',
        'who' => 'Guest, from the React website or admin panel.',
        'request' => "- First call `GET {{appUrl}}/sanctum/csrf-cookie`.\n- Send with cookies and the `X-XSRF-TOKEN` header.\n- {$phoneField}\n- `password` (string, required): up to 64 characters.",
        'success' => "**200** with `data`, the logged-in user: {$userFields}. The session cookie is now set; later requests just send cookies.",
        'errors' => "- **400:** the request did not come from a trusted website domain (`SANCTUM_STATEFUL_DOMAINS`), so no session could start.\n- **419:** the CSRF cookie is missing or old: call `sanctum/csrf-cookie` again and retry.\n- **422:** wrong phone or password: `errors.phone` = \"These credentials do not match our records.\".\n- **403:** the account is blocked, or the phone is not verified while phone verification is on. Show `message`.\n{$throttled}\n{$serverError}",
        'notes' => '- Limits: :login_per_minute tries per minute per phone number and IP address (users on the same network share the IP limit), and :login_per_phone_per_hour per hour per phone.',
    ],

    'api.v1.auth.forgot-password' => [
        'what' => 'Sends a password reset code by SMS.',
        'who' => 'Guest.',
        'request' => "- {$phoneField}",
        'success' => '**200** with `message` ("If an account exists for this phone number, we have sent it a reset code."). Always go to the Reset password screen.',
        'errors' => "- **422:** the phone is missing or not a valid Egyptian mobile number.\n{$throttled}\n{$upgrade}\n{$serverError}",
        'notes' => "- **Same answer for registered and unknown phones**: never tell the user whether the number has an account.\n- Always uses the SMS code, even when phone verification is off.\n- Within :otp_cooldown_seconds seconds of the last code, nothing new is sent and the earlier code stays valid.\n- Limits: :otp_limit_per_phone requests per phone number and :otp_limit_per_ip per IP address every :otp_limit_minutes minutes (users on the same network share the IP limit). **Sign up**, **Resend code** and **Forgot password** share one limit per phone.",
    ],

    'api.v1.auth.reset-password' => [
        'what' => 'Sets a new password with the reset code from the SMS.',
        'who' => 'Guest.',
        'request' => "- {$phoneField}\n- `code` (string, required): the code from the reset SMS.\n- `password` (string, required): 8 to 64 characters.\n- `password_confirmation` (string, required): the same password again.",
        'success' => '**200** with `message` ("Your password has been reset."). Go to Login. The user is signed out of every device, and the phone counts as verified.',
        'errors' => "- **422:** {$otpCodeError} Password errors come under `errors.password`.\n{$throttled}\n{$upgrade}\n{$serverError}",
        'notes' => "- Use the code from **Forgot password** (purpose `reset_password`); a sign-up code doesn't work here.\n- Limits: :otp_limit_per_phone tries per phone number and :otp_limit_per_ip per IP address every :otp_limit_minutes minutes (users on the same network share the IP limit). **Verify phone** and **Reset password** share one limit per phone.",
    ],

    'api.v1.auth.me' => [
        'what' => 'Returns the logged-in user with everything the account screens need.',
        'who' => 'Any logged-in user who is not blocked (customer, business or staff).',
        'request' => '- No body. Send the Bearer token (mobile) or the session cookie (web).',
        'success' => "**200** with `data`, the user: {$userFields}.",
        'errors' => "{$loggedOut}\n{$blocked}\n{$upgrade}\n{$serverError}",
        'notes' => '- The document `url`s expire after :signed_url_minutes minutes: call **me** again to refresh them.',
    ],

    'api.v1.auth.password.update' => [
        'what' => 'Changes the password of the logged-in user.',
        'who' => 'Any logged-in user who is not blocked.',
        'request' => "- `current_password` (string, required): the current password, up to 64 characters.\n- `password` (string, required): the new password, 8 to 64 characters, different from the current one.\n- `password_confirmation` (string, required): the new password again.",
        'success' => '**200** with `message` ("Your password has been changed."). The user stays logged in here; their other mobile logins are signed out.',
        'errors' => "- **422:** `errors.current_password` (wrong current password) or `errors.password` (too short, too long, not confirmed, or the same as the current one).\n{$loggedOut}\n{$blocked}\n{$upgrade}\n{$serverError}",
        'notes' => '- From the web, every mobile login of this user is signed out (the web session stays).',
    ],

    'api.v1.auth.logout' => [
        'what' => 'Logs out: on mobile it deletes the current token; on the web it ends the session.',
        'who' => 'Any logged-in user, blocked users too.',
        'request' => '- No body.',
        'success' => '**200** with `message` ("You have been logged out."). Clear the stored login and go to Login.',
        'errors' => "{$loggedOut} (treat it as already logged out)\n{$serverError}",
        'notes' => '- Even if the call fails, clear the stored login on the device.',
    ],

    'api.v1.auth.devices.store' => [
        'what' => 'Saves the FCM push token of this device, so the user gets push notifications.',
        'who' => 'Any logged-in user who is not blocked.',
        'request' => "- `platform` (required): `android`, `ios` or `web`.\n- `push_token` (string, required): the FCM token, up to 255 characters.\n- `app_version` (string, optional): up to 20 characters, for example `1.4.0`.",
        'success' => '**201** when the token is new, **200** when it was already saved. `data`: `id`, `platform`, `app_version`, `last_seen_at`. The push token itself is never returned.',
        'errors' => "- **422:** field errors.\n{$loggedOut}\n{$blocked}\n{$upgrade}\n{$serverError}",
        'notes' => '- One token belongs to one device: if it was saved for another user (someone else logged in on this phone before), it moves to the current user.',
    ],

    'api.v1.auth.devices.destroy' => [
        'what' => 'Removes the FCM push token of this device, so it stops getting this user\'s notifications.',
        'who' => 'Any logged-in user who is not blocked.',
        'request' => '- `push_token` (string, required): the same FCM token. Send it as a query parameter (or in the body).',
        'success' => '**204** with no body. It also answers 204 when the token was not saved for this user.',
        'errors' => "- **422:** `push_token` is missing.\n{$loggedOut}\n{$blocked}\n{$upgrade}\n{$serverError}",
        'notes' => '- Call it **before** logout: after logout the token is no longer valid.',
    ],

    'api.v1.client.business-profile.show' => [
        'what' => 'Returns the business profile of the logged-in business account, with its review status.',
        'who' => 'A logged-in customer account (not staff) with a business profile.',
        'request' => '- No body.',
        'success' => "**200** with `data`, the business profile: {$profileFields}.",
        'errors' => "- **404:** no business profile yet: show the create form or the onboarding.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- Show the status: `pending` = waiting for review; `approved` = ready; `rejected` = show `rejection_reason` and let the user fix the data or the documents.\n- Document `url`s expire after :signed_url_minutes minutes.",
    ],

    'api.v1.client.business-profile.store' => [
        'what' => 'Creates the business profile, with the documents, in one multipart request. It starts as `pending`.',
        'who' => 'A logged-in **business** account without a business profile yet.',
        'request' => "Send `multipart/form-data`:\n- `business_type_id` (number, required): an `id` from the business types list.\n- `business_name` (string, required): up to 255 characters.\n- `description` (string, optional): up to 2000 characters.\n- `avg_daily_orders` (number, optional): 0 to 100000.\n- `tax_registration_number` (string, optional): up to 50 characters.\n- `links` (optional, up to 10): `links[0][type]` (`website`, `facebook`, `instagram`, `tiktok`, `whatsapp` or `other`) and `links[0][url]` (a full URL, up to 2048 characters).\n- `national_id_front` and `national_id_back` (files, required): {$fileRule}.\n- `tax_register` (the commercial register, السجل التجاري) and `tax_card` (the tax card, البطاقة الضريبية) (files, optional): {$fileRule}.",
        'success' => "**201** with `data`, the new business profile: {$profileFields}. Show the \"waiting for review\" state.",
        'errors' => "- **403:** the account is not a business account (\"Only business accounts can have a business profile.\") or already has a profile (\"You already have a business profile.\"), or one of the account checks below.\n- **422:** field errors, for example a missing ID side, a file that is too big or of the wrong type, or an inactive business type.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => '- The documents are private: they are only reachable through the signed URLs in the responses.',
    ],

    'api.v1.client.business-profile.update' => [
        'what' => 'Updates the business details. Send only the fields that changed (JSON, no files).',
        'who' => 'The logged-in business account that owns the profile.',
        'request' => "- Any of: `business_type_id`, `business_name`, `description`, `avg_daily_orders`, `tax_registration_number` (same rules as create).\n- `links` (optional): when sent, it **replaces all** links (send the full list; an empty list removes them all).",
        'success' => "**200** with `data`, the updated business profile: {$profileFields}.",
        'errors' => "- **403:** no business profile yet, or one of the account checks below.\n- **422:** field errors.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- Changing `business_name`, `business_type_id` or `tax_registration_number` sends an approved or rejected profile back to `pending` (and clears the rejection reason). Warn the user before saving.\n- `description`, `avg_daily_orders` and `links` can change without a new review.",
    ],

    'api.v1.client.business-profile.documents.store' => [
        'what' => 'Uploads or replaces one business document.',
        'who' => 'The logged-in business account that owns the profile.',
        'request' => "- Path `collection`: `national_id_front`, `national_id_back`, `tax_register` (the commercial register, السجل التجاري) or `tax_card` (the tax card, البطاقة الضريبية).\n- `file` (file, required, `multipart/form-data`): {$fileRule}.",
        'success' => "**200** with `data`, the updated business profile: {$profileFields}. The new file replaces the old one.",
        'errors' => "- **404:** unknown document name in the path.\n- **403:** no business profile yet, or one of the account checks below.\n- **422:** the file is missing, too big or of the wrong type.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => '- Any document change sends an approved or rejected profile back to `pending`. Warn the user before uploading.',
    ],

    'api.v1.client.national-id.store' => [
        'what' => 'Uploads the national ID (front and back) for an optional identity check by Tayari staff.',
        'who' => 'Any logged-in customer account (individual or business, not staff).',
        'request' => "Send `multipart/form-data`:\n- `national_id_front` (file, required): {$fileRule}.\n- `national_id_back` (file, required): {$fileRule}.",
        'success' => "**200** with `data`, the user: {$userFields}. `national_id.status` is now `pending`.",
        'errors' => "- **422:** a side is missing, too big or of the wrong type.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- A new upload replaces both sides and clears the previous review (even a rejection).\n- The status (`pending`, `approved`, `rejected` with `rejection_reason`) and the images come from **me**.",
    ],

    'api.v1.admin.business-profiles.index' => [
        'what' => 'Lists business profiles for review, filtered by status, oldest first.',
        'who' => 'Staff with `business_profiles.view` (support, ops, admin, super admin).',
        'request' => "- Query `status` (optional): `pending` (default), `approved` or `rejected`.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of business profiles, each with {$profileFields}, and the owner as `user` (`id`, `name`, `phone`). Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an unknown `status` or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- There is no separate "one profile" endpoint: open the details page with the item from this list. The document `url`s expire after :signed_url_minutes minutes, so reload the list for fresh links.',
    ],

    'api.v1.admin.business-profiles.approve' => [
        'what' => 'Approves a pending business profile.',
        'who' => 'Staff with `business_profiles.review` (ops, admin, super admin).',
        'request' => '- Path `businessProfile`: the profile `id`. No body.',
        'success' => '**200** with the updated profile (`verification_status` = `approved`, `reviewed_at` set). Remove it from the pending tab.',
        'errors' => "- **404:** no profile with this id.\n- **422:** the profile is not pending any more (`errors.status`): reload the list.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Hide the button for staff without `business_profiles.review`.',
    ],

    'api.v1.admin.business-profiles.reject' => [
        'what' => 'Rejects a pending business profile with a reason the business owner will see.',
        'who' => 'Staff with `business_profiles.review` (ops, admin, super admin).',
        'request' => "- Path `businessProfile`: the profile `id`.\n- `reason` (string, required): up to 255 characters. Write it for the business owner: it is shown in their app.",
        'success' => '**200** with the updated profile (`verification_status` = `rejected`, `rejection_reason`, `reviewed_at`). Close the dialog and remove it from the pending tab.',
        'errors' => "- **404:** no profile with this id.\n- **422:** `errors.reason` (missing or too long) or `errors.status` (not pending any more: reload the list).\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- The business can fix the data or documents; the profile then goes back to pending and shows up in the queue again.',
    ],

    'api.v1.admin.national-ids.index' => [
        'what' => 'Lists client national IDs waiting for review (uploaded and not reviewed yet), oldest account first.',
        'who' => 'Staff with `national_ids.view` (support, ops, admin, super admin).',
        'request' => '- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.',
        'success' => '**200** with `data`: a list of `{ user: { id, name, phone, account_type }, status, rejection_reason, verified_at, reviewed_at, submitted_at, front, back }`, where `front` and `back` are files with a signed `url`. Plus `meta` and `links`.',
        'errors' => "- **422:** `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- There is no separate "one ID" endpoint: open the details page with the item from this list. The image `url`s expire after :signed_url_minutes minutes.',
    ],

    'api.v1.admin.national-ids.verify' => [
        'what' => 'Marks a client\'s pending national ID as verified.',
        'who' => 'Staff with `national_ids.review` (ops, admin, super admin).',
        'request' => '- Path `user`: the user `id` (from `user.id` in the list). No body.',
        'success' => '**200** with the review item (`status` = `approved`, `verified_at` set). Remove it from the list.',
        'errors' => "- **404:** no user with this id.\n- **422:** the ID is not waiting for review (`errors.status`): reload the list.\n{$loggedOut}\n{$staffOnly} Staff (except super admins) can't review their own ID.\n{$serverError}",
        'notes' => '- Hide the buttons for staff without `national_ids.review`.',
    ],

    'api.v1.admin.national-ids.reject' => [
        'what' => 'Rejects a client\'s pending national ID with a reason the client will see.',
        'who' => 'Staff with `national_ids.review` (ops, admin, super admin).',
        'request' => "- Path `user`: the user `id`.\n- `reason` (string, required): up to 255 characters, written for the client.",
        'success' => '**200** with the review item (`status` = `rejected`, `rejection_reason`). Close the dialog and remove it from the list.',
        'errors' => "- **404:** no user with this id.\n- **422:** `errors.reason` (missing or too long) or `errors.status` (not waiting for review: reload the list).\n{$loggedOut}\n{$staffOnly} Staff (except super admins) can't review their own ID.\n{$serverError}",
        'notes' => '- The client can upload a new ID; it then shows up in the list again.',
    ],

];
