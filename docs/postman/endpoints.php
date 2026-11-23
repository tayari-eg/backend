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
$imageRule = 'an image: jpg, jpeg, png or webp (no pdf), up to :max_upload_mb MB';
$throttled = '- **429:** too many attempts from this phone number or this IP address (users on the same network share the IP limit). Wait for `Retry-After` seconds, then let the user retry.';
$loginThrottled = '- **429:** too many login tries: :login_per_minute per minute for each phone number from the same IP address, or :login_per_phone_per_hour per hour for one phone number from any IP address. Wait for `Retry-After` seconds, then let the user retry.';
$upgrade = '- **426 (mobile):** the app is too old. Show the force-update screen.';
$serverError = '- **500:** show a generic error and a retry button.';
$loggedOut = '- **401:** not logged in, or the login expired. Clear the stored login and go to Login.';
$blocked = '- **403:** the account is blocked (`message` says so). Show the message and log the user out.';
$clientOnly = '- **403:** `message` explains why: the account is blocked, the phone is not verified yet (when phone verification is on), or a staff account tried a customer screen. Show the message; for "verify your phone", send the user to the OTP screen.';
$staffOnly = '- **403:** not a staff account, blocked, or missing the permission (`message` says which). Hide this action for users without the permission.';
$userFields = '`id`, `name`, `phone`, `email`, `account_type` (`individual` | `business`), `register_as` (`business` | `tayar`, or null for accounts from before sign up asked for it), `next_step` (the screen to open: see **Get my account**), `locale` (`ar` | `en`), `status` (`active` | `blocked`), `is_phone_verified`, `phone_verified_at`, `national_id` (`status`: null | `pending` | `approved` | `rejected`, `rejection_reason`, `verified_at`, `front` and `back` files with a signed `url`), `business_profile` (or null), `last_mode` (`client` | `tayar`: the mode the app reopens in), `tayar_profile` (`id`, `status`, `is_online`, `rejection_reason`, `can_reapply_at`, or null when the user never applied), `roles` (staff role names, for display only; an empty list for customers) and `permissions` (what the user may do; an empty list for customers, every permission for a super admin)';
$profileFields = '`id`, `business_type` (`id`, `name`), `business_name`, `description`, `avg_daily_orders`, `tax_registration_number`, `verification_status` (`pending` | `approved` | `rejected`), `rejection_reason`, `reviewed_at`, `links` (`id`, `type`, `url`), `documents` (`national_id_front`, `national_id_back`, `commercial_register` (the commercial register, السجل التجاري), `tax_card` (the tax card, البطاقة الضريبية): each a file with `file_name`, `mime_type`, `size`, `uploaded_at` and a signed `url`, or null), `created_at`, `updated_at`';
$businessTypeFields = '`id`, `name` (`ar` and `en`), `is_active`, `sort_order`, `business_profiles_count` (how many business profiles use it), `created_at`, `updated_at`';
$customerFields = '`id`, `name`, `phone`, `email`, `account_type` (`individual` | `business`), `locale`, `status` (`active` | `blocked`), `blocked_reason`, `is_phone_verified`, `phone_verified_at`, `national_id` (`status`: null | `pending` | `approved` | `rejected`, `rejection_reason`, `verified_at`, `reviewed_at`, `front` and `back` files with a signed `url`), `business_profile` (or null), `created_at`';
$staffFields = '`id`, `name`, `phone`, `role` (`super_admin` | `admin` | `ops` | `support`), `status` (`active` | `blocked`), `blocked_reason`, `created_at`';
$typeNotFound = '- **404:** no business type with this id.';
$customerNotFound = '- **404:** no customer with this id (staff accounts are 404 here too: manage them under **Staff**).';
$staffNotFound = '- **404:** no staff member with this id (customer accounts are 404 here too).';
$vehicleTypeFields = '`id`, `name` (`ar` and `en`), `requires_license`, `can_carry_food`, `is_active`, `sort_order`, `vehicles_count` (how many vehicles use it), `created_at`, `updated_at`';
$vehicleTypeNotFound = '- **404:** no vehicle type with this id.';
$categoryFields = '`id`, `parent_id` (null for a main category), `parent` (`id`, `name` with `ar` and `en`, or null), `name` (`ar` and `en`), `is_food`, `is_active`, `sort_order`, `icon_url` (public image URL, or null), `children_count` (how many subcategories it has), `created_at`, `updated_at`';
$categoryNotFound = '- **404:** no category with this id.';
$modeFields = '`id`, `code` (`long_distance` | `zone` | `area`, never changes), `name` (`ar` and `en`), `description` (`ar` and `en`, or null), `allows_food`, `is_active`, `sort_order`, `work_sessions_count` (how many work sessions used it), `created_at`, `updated_at`';
$modeNotFound = '- **404:** no mode with this id.';
$areaFields = '`id`, `name` (`ar` and `en`), `city` (`ar` and `en`), `center` (`lat`, `lng`), `radius_km`, `is_active`, `sort_order`, `work_sessions_count` (how many work sessions used it), `created_at`, `updated_at`';
$areaNotFound = '- **404:** no area with this id.';
$pricingRuleFields = '`id`, `mode` (`id`, `code`, `name` with `ar` and `en`), `vehicle_type` (`id`, `name` with `ar` and `en`, or null for every vehicle), `base_fare`, `price_per_km`, `minimum_fare`, `food_extra` (or null), `tayar_share_percent`, `is_active`, `created_at`, `updated_at`. Amounts are strings with two decimals, in EGP (for example `"20.00"`)';
$pricingRuleNotFound = '- **404:** no pricing rule with this id.';
$bilingualName = '`name.ar` and `name.en` (strings, both required): up to 100 characters each, as `{ "name": { "ar": "…", "en": "…" } }`';
$amountRule = 'a number with up to 2 decimals, 0 to 99999999.99';
$vehicleFields = '`id`, `vehicle_type` (`id`, `name`, `requires_license`, `can_carry_food`), `plate_number`, `model`, `color`, `year`, `status` (`pending` | `approved` | `rejected`), `is_active`, `documents` (`vehicle_license`: a file or null; `vehicle_photos`: a list of files; each file with a signed `url`)';
$tayarProfileFields = "`id`, `status` (`pending` | `approved` | `rejected`), `rejection_reason`, `can_reapply_at` (when a rejected applicant may change the application again, or null), `reviewed_at`, `national_id_number`, `is_online`, `location` (`lat`, `lng`, `updated_at`, or null), `documents` (`national_id_front`, `national_id_back`, `criminal_record` (the فيش), `personal_photo`, `driving_license`: each a file with a signed `url`, or null), `vehicle` ({$vehicleFields}), `created_at`, `updated_at`";
$tayarAdminFields = "{$tayarProfileFields}, the applicant as `user` (`id`, `name`, `phone`, `status`)";
$tayarAdminDetailFields = "{$tayarAdminFields}, `reviewed_by` (`id`, `name`, or null) and `reviews` (every approval and rejection, oldest first: `decision`, `reason`, `reviewed_by`, `created_at`)";
$workStatusFields = '`is_online`, `work_session` (`id`, `vehicle_id`, `mode` (`id`, `code`, `name`), `area` (`id`, `name`, or null), `radius_km` (or null), `destination` (`address`, `lat`, `lng`, or null), `started_at`; or null when offline), `location` (`lat`, `lng`, `updated_at`, or null)';
$notTayar = '- **403:** not an approved tayar (`message`: "Tayar mode is only available after your tayar application is approved."), the account is blocked, or the phone is not verified. Leave tayar mode and show the application status.';
$reapplyLater = '- **422:** `errors.status` = "You can change your application again from …" while a rejected application still has to wait until `can_reapply_at`.';
$backToReview = 'An approved or rejected application goes back to `pending` (and an online tayar goes offline): warn the user before saving.';
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
        'request' => "- `name` (string, required): up to 255 characters.\n- {$phoneField}\n- `password` (string, required): 8 to 64 characters.\n- `password_confirmation` (string, required): the same password again.\n- `register_as` (required): `business` (business owner) or `tayar`, from the choice on the first screen. Individual customers come later.\n- `Accept-Language` is saved as the account language (used for SMS).",
        'success' => "**201** with:\n- `data`: the new user: {$userFields}. `next_step` is `complete_business_profile` or `complete_tayar_application`: after the code screen and Login, open that form.\n- `phone_verification_required` (boolean): when true, an SMS code was sent.\n- `resend_available_in` (number of seconds, or null): how long until **Resend code** may send another code. Null when no code was sent.\n- `message`: \"Your account has been created.\"",
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
        'errors' => "- **422:** wrong phone or password: `errors.phone` = \"These credentials do not match our records.\" (the same for an unknown phone).\n- **403:** the account is blocked, or the phone is not verified while phone verification is on. Show `message`; for \"verify your phone\", open the OTP screen (call **Resend code** first).\n{$loginThrottled}\n{$upgrade}\n{$serverError}",
        'notes' => '- Limits: :login_per_minute tries per minute for each phone number from the same IP address, plus :login_per_phone_per_hour per hour for each phone number from any IP address. Mobile and web login share these limits.',
    ],

    'api.v1.auth.login' => [
        'what' => 'Web login with a session cookie. No token is returned.',
        'who' => 'Guest, from the React website or admin panel.',
        'request' => "- First call `GET {{appUrl}}/sanctum/csrf-cookie`.\n- Send with cookies and the `X-XSRF-TOKEN` header.\n- {$phoneField}\n- `password` (string, required): up to 64 characters.",
        'success' => "**200** with `data`, the logged-in user: {$userFields}. The session cookie is now set; later requests just send cookies.",
        'errors' => "- **400:** the request did not come from a trusted website domain (`SANCTUM_STATEFUL_DOMAINS`), so no session could start.\n- **419:** the CSRF cookie is missing or old: call `sanctum/csrf-cookie` again and retry.\n- **422:** wrong phone or password: `errors.phone` = \"These credentials do not match our records.\".\n- **403:** the account is blocked, or the phone is not verified while phone verification is on. Show `message`.\n{$loginThrottled}\n{$serverError}",
        'notes' => '- Limits: :login_per_minute tries per minute for each phone number from the same IP address, plus :login_per_phone_per_hour per hour for each phone number from any IP address. Mobile and web login share these limits.',
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
        'success' => "**200** with `data`, the user: {$userFields}.\n\n`next_step` says which screen to open, also after a reinstall or a login on another phone:\n- `complete_business_profile`: the business profile form → **Create business profile**.\n- `business_under_review`: the \"waiting for review\" screen (**Get business profile**).\n- `business_rejected`: show `business_profile.rejection_reason` and let the user fix the data or documents (**Update business profile**, **Upload document**).\n- `complete_tayar_application`: the tayar application form → **Get vehicle types**, then **Apply to be a tayar**.\n- `tayar_under_review`: the \"waiting for review\" screen (**Get my tayar application**).\n- `tayar_rejected`: show `tayar_profile.rejection_reason`; changes are allowed from `tayar_profile.can_reapply_at`.\n- `ready`: the home screen. Staff are always `ready`; older accounts without `register_as` are `ready` unless they are a business account or have a tayar application.",
        'errors' => "{$loggedOut}\n{$blocked}\n{$upgrade}\n{$serverError}",
        'notes' => "- `next_step` follows only the side the user registered as (`register_as`); an application on the other side shows in its own status (`business_profile`, `tayar_profile`).\n- A tayar reaching `ready` is still in client mode (`last_mode` = `client`): call **Switch mode** with `tayar` once before **Go online**.\n- The document `url`s expire after :signed_url_minutes minutes: call **me** again to refresh them.",
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
        'request' => "Send `multipart/form-data`:\n- `business_type_id` (number, required): an `id` from the business types list.\n- `business_name` (string, required): up to 255 characters.\n- `description` (string, optional): up to 2000 characters.\n- `avg_daily_orders` (number, optional): 0 to 100000.\n- `tax_registration_number` (string, optional): up to 50 characters.\n- `links` (optional, up to 10): `links[0][type]` (`website`, `facebook`, `instagram`, `tiktok`, `whatsapp` or `other`) and `links[0][url]` (a full URL, up to 2048 characters).\n- `national_id_front` and `national_id_back` (files, required): {$fileRule}.\n- `commercial_register` (the commercial register, السجل التجاري) and `tax_card` (the tax card, البطاقة الضريبية) (files, optional): {$fileRule}.",
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
        'request' => "- Path `collection`: `national_id_front`, `national_id_back`, `commercial_register` (the commercial register, السجل التجاري) or `tax_card` (the tax card, البطاقة الضريبية).\n- `file` (file, required, `multipart/form-data`): {$fileRule}.",
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
        'notes' => '- Open the Review details page with **Get business profile**. The document `url`s expire after :signed_url_minutes minutes.',
    ],

    'api.v1.admin.business-profiles.show' => [
        'what' => 'Returns one business profile with everything the Review details page needs: the business data, links, documents, the owner and who reviewed it.',
        'who' => 'Staff with `business_profiles.view` (support, ops, admin, super admin).',
        'request' => '- Path `businessProfile`: the profile `id` (from the list). No body.',
        'success' => "**200** with `data`, the business profile: {$profileFields}, the owner as `user` (`id`, `name`, `phone`) and `reviewed_by` (`id`, `name` of the staff member who approved or rejected it, or null).",
        'errors' => "- **404:** no profile with this id.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Call it again to refresh the document `url`s; they expire after :signed_url_minutes minutes.',
    ],

    'api.v1.admin.business-profiles.approve' => [
        'what' => 'Approves a pending business profile.',
        'who' => 'Staff with `business_profiles.review` (ops, admin, super admin).',
        'request' => '- Path `businessProfile`: the profile `id`. No body.',
        'success' => '**200** with the updated profile (`verification_status` = `approved`, `reviewed_at` set). Remove it from the pending tab.',
        'errors' => "- **404:** no profile with this id.\n- **422:** the profile is not pending any more (`errors.status`): reload the list.\n{$loggedOut}\n{$staffOnly} Staff (except super admins) can't review their own business profile.\n{$serverError}",
        'notes' => '- Hide the button for staff without `business_profiles.review`.',
    ],

    'api.v1.admin.business-profiles.reject' => [
        'what' => 'Rejects a pending business profile with a reason the business owner will see.',
        'who' => 'Staff with `business_profiles.review` (ops, admin, super admin).',
        'request' => "- Path `businessProfile`: the profile `id`.\n- `reason` (string, required): up to 255 characters. Write it for the business owner: it is shown in their app.",
        'success' => '**200** with the updated profile (`verification_status` = `rejected`, `rejection_reason`, `reviewed_at`). Close the dialog and remove it from the pending tab.',
        'errors' => "- **404:** no profile with this id.\n- **422:** `errors.reason` (missing or too long) or `errors.status` (not pending any more: reload the list).\n{$loggedOut}\n{$staffOnly} Staff (except super admins) can't review their own business profile.\n{$serverError}",
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

    'api.v1.admin.business-types.index' => [
        'what' => 'Lists every business type, active and inactive, in sort order.',
        'who' => 'Staff with `business_types.view` (support, ops, admin, super admin).',
        'request' => "- Query `is_active` (optional): `true` for the Active tab, `false` for the Inactive tab; leave it out for all.\n- Query `search` (optional, up to 100 characters): part of the Arabic or English name, any letter case.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of business types, each with {$businessTypeFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid `is_active`, `search` too long or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Show **Delete** only when `business_profiles_count` is 0; otherwise offer **Deactivate**.',
    ],

    'api.v1.admin.business-types.show' => [
        'what' => 'Returns one business type with its name in both languages, to fill the edit form.',
        'who' => 'Staff with `business_types.view`.',
        'request' => '- Path `businessType`: the type `id`. No body.',
        'success' => "**200** with `data`: {$businessTypeFields}.",
        'errors' => "{$typeNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.business-types.store' => [
        'what' => 'Creates a business type.',
        'who' => 'Staff with `business_types.manage` (admin, super admin).',
        'request' => "- `name.ar` and `name.en` (strings, both required): up to 100 characters each, as `{ \"name\": { \"ar\": \"مطعم\", \"en\": \"Restaurant\" } }`.\n- `is_active` (boolean, optional): default `true`.\n- `sort_order` (number, optional): 0 to 65535. Left out, the type goes to the end of the list.",
        'success' => "**201** with `data`, the new type: {$businessTypeFields}.",
        'errors' => "- **422:** field errors. A name another type already uses (ignoring letter case) gets `errors[\"name.ar\"]` or `errors[\"name.en\"]` = \"Another business type already has this name.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- An active type shows up in the public **Get business types** list at once.',
    ],

    'api.v1.admin.business-types.update' => [
        'what' => 'Updates a business type. Send only the fields that change.',
        'who' => 'Staff with `business_types.manage` (admin, super admin).',
        'request' => "- Path `businessType`: the type `id`.\n- Any of `name` (both `ar` and `en` when sent), `is_active`, `sort_order` (same rules as create).",
        'success' => "**200** with `data`, the updated type: {$businessTypeFields}.",
        'errors' => "{$typeNotFound}\n- **422:** field errors, for example a name with one language only, or a name another type already uses (ignoring letter case).\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Renaming a type changes the name every business profile with this type shows.',
    ],

    'api.v1.admin.business-types.activate' => [
        'what' => 'Activates a business type, so it shows in the public list again.',
        'who' => 'Staff with `business_types.manage` (admin, super admin).',
        'request' => '- Path `businessType`: the type `id`. No body.',
        'success' => "**200** with `data`, the type (`is_active` = true): {$businessTypeFields}. Move it to the Active tab.",
        'errors' => "{$typeNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Activating an active type also answers 200.',
    ],

    'api.v1.admin.business-types.deactivate' => [
        'what' => 'Deactivates a business type: it leaves the public list, and new business profiles can\'t pick it.',
        'who' => 'Staff with `business_types.manage` (admin, super admin).',
        'request' => '- Path `businessType`: the type `id`. No body.',
        'success' => "**200** with `data`, the type (`is_active` = false): {$businessTypeFields}. Move it to the Inactive tab.",
        'errors' => "{$typeNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Existing business profiles keep the type. Use this instead of delete for a type that is in use.',
    ],

    'api.v1.admin.business-types.destroy' => [
        'what' => 'Deletes a business type that no business profile uses.',
        'who' => 'Staff with `business_types.manage` (admin, super admin).',
        'request' => '- Path `businessType`: the type `id`. No body.',
        'success' => '**204** with no body. Remove the row.',
        'errors' => "{$typeNotFound}\n- **422:** a business profile uses the type: `errors.business_type` = \"This business type is used by business profiles, so it cannot be deleted. Deactivate it instead.\" Show it in the dialog with a **Deactivate** button.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Ask for confirmation first; a delete can\'t be undone.',
    ],

    'api.v1.admin.users.index' => [
        'what' => 'Lists customer and business accounts (never staff), newest first, with search and filters.',
        'who' => 'Staff with `users.view` (support, ops, admin, super admin).',
        'request' => "- Query `search` (optional, up to 100 characters), Arabic digits are fine:\n  - a full Egyptian mobile number in any format (`01000000000`, `+201000000000`) finds that exact phone;\n  - 4 or more digits find phones that contain them (a leading 0 is ignored, so `0100000` finds `+20100000…`);\n  - anything else searches the name.\n- Query `account_type` (optional): `individual` or `business`.\n- Query `status` (optional): `active` or `blocked`.\n- Query `phone_verified` (optional): `true` or `false`.\n- Query `national_id_status` (optional): `none` (never uploaded), `pending`, `approved` or `rejected`.\n- Query `business_profile_status` (optional): `pending`, `approved` or `rejected` (only business accounts with a profile).\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of users, each with {$customerFields}. In the list, `business_profile` has the profile data without `business_type`, `links` and `documents`; open the user for those. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an unknown filter value or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Keep the search and filters in the page URL, so the list survives a reload.',
    ],

    'api.v1.admin.users.show' => [
        'what' => 'Returns one customer or business account for the User details page: the national ID and the business profile with links and documents.',
        'who' => 'Staff with `users.view`.',
        'request' => '- Path `customer`: the user `id` (from the list). No body.',
        'success' => "**200** with `data`: {$customerFields}, with `business_profile` in full: {$profileFields}.",
        'errors' => "{$customerNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => "- A file comes without `url` when the staff member can't view that kind of document (`national_ids.view` for the national ID, `business_profiles.view` for business documents).\n- The `url`s expire after :signed_url_minutes minutes: call it again to refresh them.",
    ],

    'api.v1.admin.users.block' => [
        'what' => 'Blocks a customer or business account with a reason. Their mobile logins are signed out at once, and every request from them answers 403.',
        'who' => 'Staff with `users.block` (ops, admin, super admin).',
        'request' => "- Path `customer`: the user `id`.\n- `reason` (string, required): up to 255 characters, for the staff (the user only sees \"Your account has been blocked\").",
        'success' => "**200** with `data`, the user (`status` = `blocked`, `blocked_reason`): {$customerFields}.",
        'errors' => "{$customerNotFound}\n- **422:** `errors.reason` (missing or too long).\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Blocking a blocked user answers 200 and only replaces the reason.',
    ],

    'api.v1.admin.users.unblock' => [
        'what' => 'Unblocks a customer or business account and clears the reason.',
        'who' => 'Staff with `users.block` (ops, admin, super admin).',
        'request' => '- Path `customer`: the user `id`. No body.',
        'success' => "**200** with `data`, the user (`status` = `active`, `blocked_reason` = null): {$customerFields}.",
        'errors' => "{$customerNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Unblocking an active user also answers 200. The user logs in again; old mobile tokens stay deleted.',
    ],

    'api.v1.admin.staff.index' => [
        'what' => 'Lists staff members with their role and status, newest first.',
        'who' => 'Staff with `staff.manage` (super admin only).',
        'request' => '- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.',
        'success' => "**200** with `data`: a list of staff members, each with {$staffFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Hide the **Staff** menu item for users without `staff.manage`.',
    ],

    'api.v1.admin.staff.store' => [
        'what' => 'Creates a staff account with a role. The staff member can log in to the admin panel at once with this phone and password.',
        'who' => 'Staff with `staff.manage` (super admin only).',
        'request' => "- `name` (string, required): up to 255 characters.\n- {$phoneField} It must not belong to any account, customer or staff.\n- `password` (string, required): 8 to 64 characters.\n- `password_confirmation` (string, required): the same password again.\n- `role` (required): `admin`, `ops` or `support`. `super_admin` can't be given through the API.",
        'success' => "**201** with `data`, the new staff member: {$staffFields}.",
        'errors' => "- **422:** field errors. A phone that already has an account gets `errors.phone` = \"This phone number already belongs to an account.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- The phone counts as verified. Share the password with the staff member privately and ask them to change it after the first login.',
    ],

    'api.v1.admin.staff.role.update' => [
        'what' => 'Changes a staff member\'s role.',
        'who' => 'Staff with `staff.manage` (super admin only).',
        'request' => "- Path `staff`: the staff member `id`.\n- `role` (required): `admin`, `ops` or `support`.",
        'success' => "**200** with `data`, the staff member with the new role: {$staffFields}.",
        'errors' => "{$staffNotFound}\n- **422:** `errors.role` (unknown or `super_admin`), or `errors.staff`: \"You cannot change your own role.\" or \"This is the last active super admin, so it cannot be blocked or demoted.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Hide the change-role action on the logged-in user\'s own row. The new permissions apply from their next request.',
    ],

    'api.v1.admin.staff.block' => [
        'what' => 'Blocks a staff member with a reason. Their logins stop working at once.',
        'who' => 'Staff with `staff.manage` (super admin only).',
        'request' => "- Path `staff`: the staff member `id`.\n- `reason` (string, required): up to 255 characters.",
        'success' => "**200** with `data`, the staff member (`status` = `blocked`, `blocked_reason`): {$staffFields}.",
        'errors' => "{$staffNotFound}\n- **422:** `errors.reason` (missing or too long), or `errors.staff`: \"You cannot block your own account.\" or \"This is the last active super admin, so it cannot be blocked or demoted.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Hide the block action on the logged-in user\'s own row. Blocking a blocked staff member answers 200 and only replaces the reason.',
    ],

    'api.v1.admin.staff.unblock' => [
        'what' => 'Unblocks a staff member and clears the reason.',
        'who' => 'Staff with `staff.manage` (super admin only).',
        'request' => '- Path `staff`: the staff member `id`. No body.',
        'success' => "**200** with `data`, the staff member (`status` = `active`): {$staffFields}.",
        'errors' => "{$staffNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Unblocking an active staff member also answers 200.',
    ],

    'api.v1.public.vehicle-types.index' => [
        'what' => 'Lists the active vehicle types (car, motorcycle, scooter, bicycle, …) for the vehicle type picker.',
        'who' => 'Anyone, no login.',
        'request' => "- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page` (optional, starts at 1).\n- `Accept-Language` decides the language of the names.",
        'success' => '**200** with `data`: a list of `{ id, name, requires_license, can_carry_food }` in display order, plus `meta` and `links` for pages.',
        'errors' => "- **422:** `per_page` is out of range.\n{$upgrade}\n{$serverError}",
        'notes' => '- `requires_license` = false (a bicycle) means no plate number, no driving licence and no vehicle licence in the application form: hide those inputs.',
    ],

    'api.v1.client.tayar-profile.store' => [
        'what' => 'Applies to be a tayar: the national ID number, the personal documents and the first vehicle, in one multipart request. It starts as `pending`.',
        'who' => 'Any logged-in customer account (individual or business, not staff) without a tayar application yet.',
        'request' => "Send `multipart/form-data`:\n- `national_id_number` (string, required): the 14 digits on the national ID card; Arabic digits and spaces are fine. The birth date in it (the first 7 digits) must be a real date, not in the future.\n- `national_id_front`, `national_id_back` and `criminal_record` (the فيش) (files, required): {$fileRule}.\n- `personal_photo` (file, required): {$imageRule}.\n- `driving_license` (file): required unless the vehicle type has `requires_license` = false.\n- `vehicle_type_id` (number, required): an `id` from **Get vehicle types**.\n- `plate_number` (string, up to 20 characters): required unless the vehicle type has `requires_license` = false.\n- `model` (up to 100 characters), `color` (up to 50 characters), `year` (1980 to next year): optional.\n- `vehicle_license` (file): required unless the vehicle type has `requires_license` = false.\n- `vehicle_photos[]` (files, required): 1 to 4 photos of the vehicle, each {$imageRule}.",
        'success' => "**201** with `data`, the application: {$tayarProfileFields}. Show the \"waiting for review\" state.",
        'errors' => "- **403:** the user already applied (\"You have already applied to be a tayar.\"), or one of the account checks below.\n- **422:** field errors, for example a national ID number that isn't 14 digits, has an impossible or future birth date, or is used by another application (\"This national ID number is already used in another tayar application.\"), a plate used by another vehicle, a missing document, more than 4 photos, or an inactive vehicle type.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => '- The tayar uploads its own national ID here, even if the account already sent one for the optional ID check. Every document is private: it is only reachable through the signed URLs in the responses.',
    ],

    'api.v1.client.tayar-profile.show' => [
        'what' => 'Returns the logged-in user\'s tayar application with its review status.',
        'who' => 'A logged-in customer account with a tayar application.',
        'request' => '- No body.',
        'success' => "**200** with `data`, the application: {$tayarProfileFields}.",
        'errors' => "- **404:** no application yet: show **Become a tayar**.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- `pending` = waiting for review; `approved` = tayar mode is open; `rejected` = show `rejection_reason`, and allow changes from `can_reapply_at` (about :tayar_reapply_hours hours after the rejection).\n- Document `url`s expire after :signed_url_minutes minutes.",
    ],

    'api.v1.client.tayar-profile.update' => [
        'what' => 'Changes the national ID number of the application (JSON).',
        'who' => 'The logged-in user who owns the application.',
        'request' => '- `national_id_number` (string, optional): same rules as the application.',
        'success' => "**200** with `data`, the application: {$tayarProfileFields}.",
        'errors' => "- **403:** no application yet, or one of the account checks below.\n{$reapplyLater}\n- **422:** field errors.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- A new number: {$backToReview} The same number changes nothing.",
    ],

    'api.v1.client.tayar-profile.documents.store' => [
        'what' => 'Uploads or replaces one personal document of the application.',
        'who' => 'The logged-in user who owns the application.',
        'request' => "- Path `collection`: `national_id_front`, `national_id_back`, `criminal_record`, `personal_photo` or `driving_license`.\n- `file` (file, required, `multipart/form-data`): {$fileRule}; for `personal_photo`, {$imageRule}.",
        'success' => "**200** with `data`, the application: {$tayarProfileFields}. The new file replaces the old one.",
        'errors' => "- **404:** unknown document name in the path.\n- **403:** no application yet, or one of the account checks below.\n{$reapplyLater}\n- **422:** the file is missing, too big or of the wrong type.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- {$backToReview}",
    ],

    'api.v1.client.tayar-profile.vehicle.update' => [
        'what' => 'Updates the vehicle details. Send only the fields that change (JSON, no files).',
        'who' => 'The logged-in user who owns the application.',
        'request' => "- Any of `vehicle_type_id`, `plate_number`, `model`, `color`, `year` (same rules as the application).\n- Moving to a vehicle type that needs a licence needs a plate, and the driving licence and the vehicle licence already uploaded: upload them first.",
        'success' => "**200** with `data`, the application: {$tayarProfileFields}.",
        'errors' => "- **403:** no application yet, or one of the account checks below.\n{$reapplyLater}\n- **422:** field errors, for example a plate used by another vehicle, a missing plate, or `errors.driving_license` / `errors.vehicle_license` when a licence must be uploaded first.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- Changing `vehicle_type_id` or `plate_number`: {$backToReview}\n- `model`, `color` and `year` can change without a new review.",
    ],

    'api.v1.client.tayar-profile.vehicle.documents.store' => [
        'what' => 'Replaces the vehicle licence, or all the vehicle photos at once.',
        'who' => 'The logged-in user who owns the application.',
        'request' => "Send `multipart/form-data`:\n- Path `collection`: `vehicle_license` or `vehicle_photos`.\n- For `vehicle_license`: `file` (file, required): {$fileRule}.\n- For `vehicle_photos`: `files[]` (files, required): 1 to 4 photos, each {$imageRule}. They replace all the current photos.",
        'success' => "**200** with `data`, the application: {$tayarProfileFields}.",
        'errors' => "- **404:** unknown document name in the path.\n- **403:** no application yet, or one of the account checks below.\n{$reapplyLater}\n- **422:** a file is missing, too big or of the wrong type, or more than 4 photos.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- {$backToReview}",
    ],

    'api.v1.auth.mode.update' => [
        'what' => 'Switches the app between client mode and tayar mode, and saves it so the app reopens in the same mode.',
        'who' => 'Any logged-in user who is not blocked.',
        'request' => '- `mode` (required): `client` or `tayar`.',
        'success' => "**200** with `data`, the user: {$userFields}.",
        'errors' => "- **403:** `tayar` needs an approved tayar application (\"Tayar mode is only available after your tayar application is approved.\").\n- **422:** an unknown `mode`.\n{$loggedOut}\n{$blocked}\n{$upgrade}\n{$serverError}",
        'notes' => "- Switching to `client` while online sets the tayar offline and closes the work session.\n- Approval doesn't change the mode: when `register_as` = `tayar`, `next_step` = `ready` and `last_mode` = `client`, call this once with `tayar` before **Go online**.",
    ],

    'api.v1.tayar.online' => [
        'what' => 'Goes online in a mode at the current position, with the active vehicle, and starts a work session.',
        'who' => 'An approved tayar (active account, verified phone) in tayar mode, with an approved, active vehicle.',
        'request' => "- `lat` (number, required): -90 to 90, up to 7 decimals.\n- `lng` (number, required): -180 to 180, up to 7 decimals.\n- `mode_id` (number, required): an active mode from **Get modes**.\n- `area_id` (number): required when the mode has `requires_area` (the area mode), from **Get areas**; refused for the other modes.\n- `radius_km` (number): required when the mode has `requires_radius` (the zone mode), :zone_min_radius_km to :zone_max_radius_km, up to 2 decimals; refused for the other modes.\n- `destination_address` (string, up to 255), `destination_lat` and `destination_lng` (numbers, up to 7 decimals): all three required when the mode has `requires_destination` (long distance); refused for the other modes.",
        'success' => "**200** with `data`: {$workStatusFields}. Start the background location service.",
        'errors' => "- **422:** a missing or out-of-range field, `mode_id` or `area_id` unknown or inactive, a field the picked mode doesn't take (`errors.<field>` = \"The … field is prohibited.\"), or `errors.vehicle` = \"You need an approved vehicle to go online.\"\n- **403:** the app is in client mode (`message`: \"Switch to tayar mode before going online.\"): call **Switch mode** with `tayar` first.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => "- Going online again with the same mode and settings keeps the work session and only moves the position. Different settings (another mode, area, radius or destination) end the session and start a new one: use this to change the mode without going offline.\n- A newly approved tayar is still in client mode: when `register_as` = `tayar`, `next_step` = `ready` and `last_mode` = `client`, call **Switch mode** with `tayar` once first.",
    ],

    'api.v1.tayar.offline' => [
        'what' => 'Goes offline and closes the work session.',
        'who' => 'An approved tayar.',
        'request' => '- No body.',
        'success' => "**200** with `data`: {$workStatusFields} (`is_online` = false, `work_session` = null). Stop the background location service.",
        'errors' => "{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => '- Going offline while offline also answers 200. The last position is kept.',
    ],

    'api.v1.tayar.location' => [
        'what' => 'Sends the current position while online. The server keeps only the latest position, never a history.',
        'who' => 'An approved tayar who is online.',
        'request' => '- `lat` (number, required): -90 to 90, up to 7 decimals.\n- `lng` (number, required): -180 to 180, up to 7 decimals.',
        'success' => '**204** with no body.',
        'errors' => "- **422:** `lat`/`lng` missing or out of range, or `errors.status` = \"Go online before sending your location.\" (the tayar was set offline: show the offline state).\n- **429:** more than :tayar_location_per_minute updates in a minute. Wait for `Retry-After` seconds, then send only the latest position.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => '- Send every 5 to 10 seconds while online (at most :tayar_location_per_minute a minute). A tayar with no location for :tayar_offline_minutes minutes is set offline by the server.',
    ],

    'api.v1.admin.tayar-applications.index' => [
        'what' => 'Lists tayar applications for review, filtered by status, oldest first.',
        'who' => 'Staff with `tayars.view` (support, ops, admin, super admin).',
        'request' => "- Query `status` (optional): `pending` (default), `approved` or `rejected`.\n- Query `vehicle_type_id` (optional): only applications with this vehicle type.\n- Query `search` (optional, up to 100 characters): the same rules as **List users** (a full phone, 4 or more digits of a phone, or part of the name).\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of applications, each with {$tayarAdminFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an unknown `status` or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Open the Review details page with **Get tayar application**. The document `url`s expire after :signed_url_minutes minutes.',
    ],

    'api.v1.admin.tayar-applications.show' => [
        'what' => 'Returns one tayar application with every document, the vehicle, and the review history.',
        'who' => 'Staff with `tayars.view`.',
        'request' => '- Path `tayarProfile`: the application `id`. No body.',
        'success' => "**200** with `data`: {$tayarAdminDetailFields}.",
        'errors' => "- **404:** no application with this id.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Call it again to refresh the document `url`s; they expire after :signed_url_minutes minutes.',
    ],

    'api.v1.admin.tayar-applications.approve' => [
        'what' => 'Approves a pending tayar application and its vehicle, which becomes the active vehicle.',
        'who' => 'Staff with `tayars.review` (ops, admin, super admin).',
        'request' => '- Path `tayarProfile`: the application `id`. No body.',
        'success' => "**200** with `data`: {$tayarAdminDetailFields} (`status` = `approved`). Remove it from the pending tab.",
        'errors' => "- **404:** no application with this id.\n- **422:** the application is not pending any more (`errors.status`): reload the list.\n{$loggedOut}\n{$staffOnly} Staff (except super admins) can't review their own application.\n{$serverError}",
        'notes' => '- The tayar can switch to tayar mode and go online at once.',
    ],

    'api.v1.admin.tayar-applications.reject' => [
        'what' => 'Rejects a pending tayar application with a reason the applicant sees.',
        'who' => 'Staff with `tayars.review` (ops, admin, super admin).',
        'request' => "- Path `tayarProfile`: the application `id`.\n- `reason` (string, required): up to 255 characters. Write it for the applicant: it is shown in their app.",
        'success' => "**200** with `data`: {$tayarAdminDetailFields} (`status` = `rejected`, `rejection_reason`, `can_reapply_at`). Close the dialog and remove it from the pending tab.",
        'errors' => "- **404:** no application with this id.\n- **422:** `errors.reason` (missing or too long) or `errors.status` (not pending any more: reload the list).\n{$loggedOut}\n{$staffOnly} Staff (except super admins) can't review their own application.\n{$serverError}",
        'notes' => '- The applicant can fix the application :tayar_reapply_hours hours after the rejection; it then shows up as pending again.',
    ],

    'api.v1.admin.tayars.index' => [
        'what' => 'Lists approved tayars, newest first, with filters.',
        'who' => 'Staff with `tayars.view` (support, ops, admin, super admin).',
        'request' => "- Query `is_online` (optional): `true` for tayars online now, `false` for offline.\n- Query `vehicle_type_id` (optional): only tayars with this vehicle type.\n- Query `blocked` (optional): `true` for blocked accounts, `false` for active ones.\n- Query `search` (optional): the same rules as **List users**.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of tayars, each with {$tayarAdminFields} (in the list, `documents` are left out). Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid filter or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Block or unblock a tayar from **Users › Block user** (`user.id` is the user id): a blocked tayar is out of tayar mode at once and is set offline within :tayar_offline_minutes minutes.',
    ],

    'api.v1.admin.tayars.show' => [
        'what' => 'Returns one approved tayar: documents, vehicle, last position and the review history.',
        'who' => 'Staff with `tayars.view`.',
        'request' => '- Path `tayar`: the tayar profile `id` (from the list). No body.',
        'success' => "**200** with `data`: {$tayarAdminDetailFields}.",
        'errors' => "- **404:** no approved tayar with this id (pending and rejected applications are under **Tayar applications**).\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- `location.updated_at` shows how fresh the position is.',
    ],

    'api.v1.admin.vehicle-types.index' => [
        'what' => 'Lists every vehicle type, active and inactive, in sort order.',
        'who' => 'Staff with `vehicle_types.view` (support, ops, admin, super admin).',
        'request' => "- Query `is_active` (optional): `true` for the Active tab, `false` for the Inactive tab; leave it out for all.\n- Query `search` (optional, up to 100 characters): part of the Arabic or English name, any letter case.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of vehicle types, each with {$vehicleTypeFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid `is_active`, `search` too long or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Show **Delete** only when `vehicles_count` is 0; otherwise offer **Deactivate**.',
    ],

    'api.v1.admin.vehicle-types.show' => [
        'what' => 'Returns one vehicle type with its name in both languages, to fill the edit form.',
        'who' => 'Staff with `vehicle_types.view`.',
        'request' => '- Path `vehicleType`: the type `id`. No body.',
        'success' => "**200** with `data`: {$vehicleTypeFields}.",
        'errors' => "{$vehicleTypeNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.vehicle-types.store' => [
        'what' => 'Creates a vehicle type.',
        'who' => 'Staff with `vehicle_types.manage` (admin, super admin).',
        'request' => "- `name.ar` and `name.en` (strings, both required): up to 100 characters each, as `{ \"name\": { \"ar\": \"سيارة\", \"en\": \"Car\" } }`.\n- `requires_license` (boolean, optional): default `true`; `false` for a bicycle (no plate, no licences).\n- `can_carry_food` (boolean, optional): default `true`.\n- `is_active` (boolean, optional): default `true`.\n- `sort_order` (number, optional): 0 to 65535. Left out, the type goes to the end of the list.",
        'success' => "**201** with `data`, the new type: {$vehicleTypeFields}.",
        'errors' => "- **422:** field errors. A name another type already uses (ignoring letter case) gets `errors[\"name.ar\"]` or `errors[\"name.en\"]` = \"Another vehicle type already has this name.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- An active type shows up in the public **Get vehicle types** list at once.',
    ],

    'api.v1.admin.vehicle-types.update' => [
        'what' => 'Updates a vehicle type. Send only the fields that change.',
        'who' => 'Staff with `vehicle_types.manage` (admin, super admin).',
        'request' => "- Path `vehicleType`: the type `id`.\n- Any of `name` (both `ar` and `en` when sent), `requires_license`, `can_carry_food`, `is_active`, `sort_order` (same rules as create).",
        'success' => "**200** with `data`, the updated type: {$vehicleTypeFields}.",
        'errors' => "{$vehicleTypeNotFound}\n- **422:** field errors, for example a name with one language only, or a name another type already uses (ignoring letter case).\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Changing `requires_license` only affects new applications and vehicle changes; existing vehicles are not checked again.',
    ],

    'api.v1.admin.vehicle-types.activate' => [
        'what' => 'Activates a vehicle type, so it shows in the public list again.',
        'who' => 'Staff with `vehicle_types.manage` (admin, super admin).',
        'request' => '- Path `vehicleType`: the type `id`. No body.',
        'success' => "**200** with `data`, the type (`is_active` = true): {$vehicleTypeFields}. Move it to the Active tab.",
        'errors' => "{$vehicleTypeNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Activating an active type also answers 200.',
    ],

    'api.v1.admin.vehicle-types.deactivate' => [
        'what' => 'Deactivates a vehicle type: it leaves the public list, and new applications can\'t pick it.',
        'who' => 'Staff with `vehicle_types.manage` (admin, super admin).',
        'request' => '- Path `vehicleType`: the type `id`. No body.',
        'success' => "**200** with `data`, the type (`is_active` = false): {$vehicleTypeFields}. Move it to the Inactive tab.",
        'errors' => "{$vehicleTypeNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Existing vehicles keep the type. Use this instead of delete for a type that is in use.',
    ],

    'api.v1.admin.vehicle-types.destroy' => [
        'what' => 'Deletes a vehicle type that no vehicle and no pricing rule uses.',
        'who' => 'Staff with `vehicle_types.manage` (admin, super admin).',
        'request' => '- Path `vehicleType`: the type `id`. No body.',
        'success' => '**204** with no body. Remove the row.',
        'errors' => "{$vehicleTypeNotFound}\n- **422:** a vehicle uses the type: `errors.vehicle_type` = \"This vehicle type is used by vehicles, so it cannot be deleted. Deactivate it instead.\" Show it in the dialog with a **Deactivate** button.\n- **422:** a pricing rule uses the type: `errors.vehicle_type` = \"This vehicle type is used by pricing rules, so it cannot be deleted. Delete those rules or deactivate the type instead.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Ask for confirmation first; a delete can\'t be undone.',
    ],

    'api.v1.public.categories.index' => [
        'what' => 'Lists the active main categories, each with its active subcategories, for the category picker of a new order.',
        'who' => 'Anyone, no login.',
        'request' => "- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page` (optional, starts at 1). Pages count main categories.\n- `Accept-Language` decides the language of the names.",
        'success' => '**200** with `data`: a list of main categories `{ id, name, is_food, icon_url, children }` in display order, where `children` is a list of `{ id, name, is_food, icon_url }`. Plus `meta` and `links` for pages.',
        'errors' => "- **422:** `per_page` is out of range.\n{$upgrade}\n{$serverError}",
        'notes' => "- An order is placed in a subcategory: the user picks a main category, then one of its `children`.\n- A main category with no active subcategory is left out.\n- `icon_url` is a public image URL (or null: show a default icon).",
    ],

    'api.v1.public.modes.index' => [
        'what' => 'Lists the active modes a tayar can work in, with what **Go online** needs for each.',
        'who' => 'Anyone, no login.',
        'request' => "- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page` (optional, starts at 1).\n- `Accept-Language` decides the language of the names.",
        'success' => '**200** with `data`: a list of `{ id, code, name, description, allows_food, requires_area, requires_radius, requires_destination, radius_km }` in display order, plus `meta` and `links` for pages. `radius_km` is `{ min, max }` for the zone mode, null otherwise.',
        'errors' => "- **422:** `per_page` is out of range.\n{$upgrade}\n{$serverError}",
        'notes' => "- `code` is `long_distance`, `zone` or `area`. Build the next step from the `requires_*` flags, not from the code: `requires_area` → the area picker (**Get areas**), `requires_radius` → a radius slider from `radius_km.min` to `radius_km.max`, `requires_destination` → a destination picker on the map.\n- `description` is the short note to show under each choice.",
    ],

    'api.v1.public.areas.index' => [
        'what' => 'Lists the active areas (districts) for the area mode picker.',
        'who' => 'Anyone, no login.',
        'request' => "- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page` (optional, starts at 1).\n- `Accept-Language` decides the language of the names.",
        'success' => '**200** with `data`: a list of `{ id, name, city, center: { lat, lng }, radius_km }` in display order, plus `meta` and `links` for pages.',
        'errors' => "- **422:** `per_page` is out of range.\n{$upgrade}\n{$serverError}",
        'notes' => '- Draw each area as a circle (`center`, `radius_km`) on the map. Send the chosen `id` as `area_id` in **Go online**.',
    ],

    'api.v1.admin.categories.index' => [
        'what' => 'Lists every category, main categories and subcategories, active and inactive, in sort order.',
        'who' => 'Staff with `categories.view` (support, ops, admin, super admin).',
        'request' => "- Query `main_only` (optional): `true` for main categories only.\n- Query `parent_id` (optional): the subcategories of one main category.\n- Query `is_active` (optional): `true` for the Active tab, `false` for the Inactive tab; leave it out for all.\n- Query `is_food` (optional): `true` or `false`.\n- Query `search` (optional, up to 100 characters): part of the Arabic or English name, any letter case.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of categories, each with {$categoryFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid filter, `search` too long or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Show **Delete** only when `children_count` is 0; otherwise offer **Deactivate**.',
    ],

    'api.v1.admin.categories.show' => [
        'what' => 'Returns one category with its name in both languages, its parent and its icon, to fill the edit form.',
        'who' => 'Staff with `categories.view`.',
        'request' => '- Path `category`: the category `id`. No body.',
        'success' => "**200** with `data`: {$categoryFields}.",
        'errors' => "{$categoryNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.categories.store' => [
        'what' => 'Creates a main category, or a subcategory of a main category.',
        'who' => 'Staff with `categories.manage` (admin, super admin).',
        'request' => "- {$bilingualName}. Each name must be unique among the categories under the same parent (ignoring letter case).\n- `parent_id` (number, optional): a main category; leave it out (or null) for a main category.\n- `is_food` (boolean, optional): left out, a subcategory takes its parent's value and a main category is `false`.\n- `is_active` (boolean, optional): default `true`.\n- `sort_order` (number, optional): 0 to 65535. Left out, it goes to the end of its list.",
        'success' => "**201** with `data`, the new category: {$categoryFields}. Then upload the icon with **Upload category icon**.",
        'errors' => "- **422:** field errors. A name a sibling already uses gets `errors[\"name.ar\"]` or `errors[\"name.en\"]` = \"Another category under the same parent already has this name.\"; a subcategory as `parent_id` gets `errors.parent_id` = \"The parent must be a main category: a subcategory cannot have its own subcategories.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Two levels only: main categories and their subcategories.',
    ],

    'api.v1.admin.categories.update' => [
        'what' => 'Updates or moves a category. Send only the fields that change.',
        'who' => 'Staff with `categories.manage` (admin, super admin).',
        'request' => "- Path `category`: the category `id`.\n- Any of `name` (both `ar` and `en` when sent), `parent_id` (a main category, or null to make it a main category), `is_food`, `is_active`, `sort_order` (same rules as create).",
        'success' => "**200** with `data`, the updated category: {$categoryFields}.",
        'errors' => "{$categoryNotFound}\n- **422:** field errors; `errors.parent_id` when the new parent is a subcategory or the category itself, or = \"This category has subcategories, so it cannot be moved under another category.\"; `errors[\"name.ar\"]` / `errors[\"name.en\"]` when a category under the new parent already has the name.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.categories.activate' => [
        'what' => 'Activates a category, so it shows in the public list again.',
        'who' => 'Staff with `categories.manage` (admin, super admin).',
        'request' => '- Path `category`: the category `id`. No body.',
        'success' => "**200** with `data`, the category (`is_active` = true): {$categoryFields}. Move it to the Active tab.",
        'errors' => "{$categoryNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Activating an active category also answers 200.',
    ],

    'api.v1.admin.categories.deactivate' => [
        'what' => 'Deactivates a category: it leaves the public list. A deactivated main category hides its subcategories too.',
        'who' => 'Staff with `categories.manage` (admin, super admin).',
        'request' => '- Path `category`: the category `id`. No body.',
        'success' => "**200** with `data`, the category (`is_active` = false): {$categoryFields}. Move it to the Inactive tab.",
        'errors' => "{$categoryNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Use this instead of delete for a category that has subcategories.',
    ],

    'api.v1.admin.categories.destroy' => [
        'what' => 'Deletes a category that has no subcategories, with its icon.',
        'who' => 'Staff with `categories.manage` (admin, super admin).',
        'request' => '- Path `category`: the category `id`. No body.',
        'success' => '**204** with no body. Remove the row.',
        'errors' => "{$categoryNotFound}\n- **422:** it has subcategories: `errors.category` = \"This category has subcategories, so it cannot be deleted. Delete or move them first, or deactivate it instead.\" Show it in the dialog with a **Deactivate** button.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Ask for confirmation first; a delete can\'t be undone.',
    ],

    'api.v1.admin.categories.icon.store' => [
        'what' => 'Uploads the category icon, or replaces the current one.',
        'who' => 'Staff with `categories.manage` (admin, super admin).',
        'request' => "- Path `category`: the category `id`.\n- Multipart form: `icon` (file, required): {$imageRule}. SVG is not accepted.",
        'success' => "**200** with `data`, the category with the new `icon_url`: {$categoryFields}.",
        'errors' => "{$categoryNotFound}\n- **422:** `icon` missing, not an image, or too big.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- This is a POST with a multipart body (not PATCH). The icon is public: anyone with `icon_url` can open it.',
    ],

    'api.v1.admin.categories.icon.destroy' => [
        'what' => 'Removes the category icon.',
        'who' => 'Staff with `categories.manage` (admin, super admin).',
        'request' => '- Path `category`: the category `id`. No body.',
        'success' => "**200** with `data`, the category (`icon_url` = null): {$categoryFields}.",
        'errors' => "{$categoryNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Removing a missing icon also answers 200.',
    ],

    'api.v1.admin.modes.index' => [
        'what' => 'Lists the three modes (long distance, zone, area), active and inactive, in sort order.',
        'who' => 'Staff with `modes.view` (support, ops, admin, super admin).',
        'request' => "- Query `is_active` (optional): `true` or `false`; leave it out for all.\n- Query `search` (optional, up to 100 characters): part of the Arabic or English name, any letter case.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of modes, each with {$modeFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid `is_active`, `search` too long or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Modes are fixed: there is no create or delete. Staff edit the names, description, food flag, order and active state.',
    ],

    'api.v1.admin.modes.show' => [
        'what' => 'Returns one mode with its name and description in both languages, to fill the edit form.',
        'who' => 'Staff with `modes.view`.',
        'request' => '- Path `mode`: the mode `id`. No body.',
        'success' => "**200** with `data`: {$modeFields}.",
        'errors' => "{$modeNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.modes.update' => [
        'what' => 'Updates a mode. Send only the fields that change; the code never changes.',
        'who' => 'Staff with `modes.manage` (admin, super admin).',
        'request' => "- Path `mode`: the mode `id`.\n- Any of: {$bilingualName}, unique among modes; `description.ar` and `description.en` (strings, both when sent, up to 500 characters each); `allows_food` (boolean); `is_active` (boolean); `sort_order` (0 to 65535).",
        'success' => "**200** with `data`, the updated mode: {$modeFields}.",
        'errors' => "{$modeNotFound}\n- **422:** field errors, for example a description with one language only, or `errors[\"name.ar\"]` / `errors[\"name.en\"]` = \"Another mode already has this name.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.modes.activate' => [
        'what' => 'Activates a mode: tayars can pick it again when going online.',
        'who' => 'Staff with `modes.manage` (admin, super admin).',
        'request' => '- Path `mode`: the mode `id`. No body.',
        'success' => "**200** with `data`, the mode (`is_active` = true): {$modeFields}.",
        'errors' => "{$modeNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Activating an active mode also answers 200.',
    ],

    'api.v1.admin.modes.deactivate' => [
        'what' => 'Deactivates a mode: it leaves the tayar\'s mode picker and new **Go online** requests in it are refused.',
        'who' => 'Staff with `modes.manage` (admin, super admin).',
        'request' => '- Path `mode`: the mode `id`. No body.',
        'success' => "**200** with `data`, the mode (`is_active` = false): {$modeFields}.",
        'errors' => "{$modeNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Tayars already online in the mode keep working until they go offline.',
    ],

    'api.v1.admin.areas.index' => [
        'what' => 'Lists every area, active and inactive, in sort order.',
        'who' => 'Staff with `areas.view` (support, ops, admin, super admin).',
        'request' => "- Query `is_active` (optional): `true` for the Active tab, `false` for the Inactive tab; leave it out for all.\n- Query `search` (optional, up to 100 characters): part of the Arabic or English name, any letter case.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of areas, each with {$areaFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid `is_active`, `search` too long or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Show **Delete** only when `work_sessions_count` is 0; otherwise offer **Deactivate**.',
    ],

    'api.v1.admin.areas.show' => [
        'what' => 'Returns one area with its name and city in both languages, to fill the edit form.',
        'who' => 'Staff with `areas.view`.',
        'request' => '- Path `area`: the area `id`. No body.',
        'success' => "**200** with `data`: {$areaFields}.",
        'errors' => "{$areaNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.areas.store' => [
        'what' => 'Creates an area: a district drawn as a centre point and a radius.',
        'who' => 'Staff with `areas.manage` (admin, super admin).',
        'request' => "- {$bilingualName}, unique among areas (ignoring letter case).\n- `city.ar` and `city.en` (strings, both required): up to 100 characters each.\n- `center_lat` (number, required): -90 to 90, up to 7 decimals. `center_lng` (number, required): -180 to 180, up to 7 decimals.\n- `radius_km` (number, required): 0.5 to 50, up to 2 decimals.\n- `is_active` (boolean, optional): default `true`.\n- `sort_order` (number, optional): 0 to 65535. Left out, the area goes to the end of the list.",
        'success' => "**201** with `data`, the new area: {$areaFields}.",
        'errors' => "- **422:** field errors. A name another area already uses gets `errors[\"name.ar\"]` or `errors[\"name.en\"]` = \"Another area already has this name.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Pick the centre on a map and show the circle while the radius changes.',
    ],

    'api.v1.admin.areas.update' => [
        'what' => 'Updates an area. Send only the fields that change.',
        'who' => 'Staff with `areas.manage` (admin, super admin).',
        'request' => "- Path `area`: the area `id`.\n- Any of `name`, `city` (both `ar` and `en` when sent), `center_lat`, `center_lng`, `radius_km`, `is_active`, `sort_order` (same rules as create).",
        'success' => "**200** with `data`, the updated area: {$areaFields}.",
        'errors' => "{$areaNotFound}\n- **422:** field errors, for example a name or city with one language only, or a name another area already uses.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.areas.activate' => [
        'what' => 'Activates an area, so it shows in the tayar\'s area picker again.',
        'who' => 'Staff with `areas.manage` (admin, super admin).',
        'request' => '- Path `area`: the area `id`. No body.',
        'success' => "**200** with `data`, the area (`is_active` = true): {$areaFields}. Move it to the Active tab.",
        'errors' => "{$areaNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Activating an active area also answers 200.',
    ],

    'api.v1.admin.areas.deactivate' => [
        'what' => 'Deactivates an area: it leaves the area picker, and new **Go online** requests in it are refused.',
        'who' => 'Staff with `areas.manage` (admin, super admin).',
        'request' => '- Path `area`: the area `id`. No body.',
        'success' => "**200** with `data`, the area (`is_active` = false): {$areaFields}. Move it to the Inactive tab.",
        'errors' => "{$areaNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Tayars already online in the area keep working until they go offline. Use this instead of delete for an area that has been used.',
    ],

    'api.v1.admin.areas.destroy' => [
        'what' => 'Deletes an area no work session has used.',
        'who' => 'Staff with `areas.manage` (admin, super admin).',
        'request' => '- Path `area`: the area `id`. No body.',
        'success' => '**204** with no body. Remove the row.',
        'errors' => "{$areaNotFound}\n- **422:** a work session used the area: `errors.area` = \"Tayars have worked in this area, so it cannot be deleted. Deactivate it instead.\" Show it in the dialog with a **Deactivate** button.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Ask for confirmation first; a delete can\'t be undone.',
    ],

    'api.v1.admin.pricing-rules.index' => [
        'what' => 'Lists the pricing rules, active and inactive, by mode.',
        'who' => 'Staff with `pricing_rules.view` (support, ops, admin, super admin).',
        'request' => "- Query `mode_id` (optional): the rules of one mode.\n- Query `vehicle_type_id` (optional): the rules for one vehicle type.\n- Query `is_active` (optional): `true` or `false`; leave it out for all.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of pricing rules, each with {$pricingRuleFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid filter or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => "- The shipping fee will be: the larger of `minimum_fare` and `base_fare` + `price_per_km` × km, plus `food_extra` for food orders. The tayar gets `tayar_share_percent` of it and Tayari keeps the rest. Prices are only stored for now; orders use them in a later release.\n- A rule with a `vehicle_type` is for that type; the rule with `vehicle_type` null is for every other vehicle in the mode.",
    ],

    'api.v1.admin.pricing-rules.show' => [
        'what' => 'Returns one pricing rule, to fill the edit form.',
        'who' => 'Staff with `pricing_rules.view`.',
        'request' => '- Path `pricingRule`: the rule `id`. No body.',
        'success' => "**200** with `data`: {$pricingRuleFields}.",
        'errors' => "{$pricingRuleNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.pricing-rules.store' => [
        'what' => 'Creates a pricing rule for a mode, for one vehicle type or for every vehicle.',
        'who' => 'Staff with `pricing_rules.manage` (admin, super admin).',
        'request' => "- `mode_id` (number, required): a mode from **List modes**.\n- `vehicle_type_id` (number, optional): a vehicle type; leave it out (or null) for every vehicle.\n- `base_fare`, `price_per_km`, `minimum_fare` (required): {$amountRule}, in EGP.\n- `food_extra` (optional, or null): {$amountRule}, in EGP.\n- `tayar_share_percent` (required): 0 to 100, up to 2 decimals.\n- `is_active` (boolean, optional): default `true`.",
        'success' => "**201** with `data`, the new rule: {$pricingRuleFields}.",
        'errors' => "- **422:** field errors. A mode that already has a rule for this vehicle type (or for every vehicle), active or not, gets `errors.vehicle_type_id` = \"This mode already has a pricing rule for this vehicle type. Edit that rule instead.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Send amounts as numbers or strings; they come back as strings with two decimals.',
    ],

    'api.v1.admin.pricing-rules.update' => [
        'what' => 'Updates a pricing rule. Send only the fields that change.',
        'who' => 'Staff with `pricing_rules.manage` (admin, super admin).',
        'request' => "- Path `pricingRule`: the rule `id`.\n- Any of `mode_id`, `vehicle_type_id` (null for every vehicle), `base_fare`, `price_per_km`, `minimum_fare`, `food_extra` (null removes it), `tayar_share_percent`, `is_active` (same rules as create).",
        'success' => "**200** with `data`, the updated rule: {$pricingRuleFields}.",
        'errors' => "{$pricingRuleNotFound}\n- **422:** field errors, or `errors.vehicle_type_id` when another rule already has the new mode and vehicle type.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.pricing-rules.activate' => [
        'what' => 'Activates a pricing rule.',
        'who' => 'Staff with `pricing_rules.manage` (admin, super admin).',
        'request' => '- Path `pricingRule`: the rule `id`. No body.',
        'success' => "**200** with `data`, the rule (`is_active` = true): {$pricingRuleFields}.",
        'errors' => "{$pricingRuleNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Activating an active rule also answers 200.',
    ],

    'api.v1.admin.pricing-rules.deactivate' => [
        'what' => 'Deactivates a pricing rule without deleting it.',
        'who' => 'Staff with `pricing_rules.manage` (admin, super admin).',
        'request' => '- Path `pricingRule`: the rule `id`. No body.',
        'success' => "**200** with `data`, the rule (`is_active` = false): {$pricingRuleFields}.",
        'errors' => "{$pricingRuleNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- An inactive rule still counts for "one rule per mode and vehicle type": edit it instead of creating another.',
    ],

    'api.v1.admin.pricing-rules.destroy' => [
        'what' => 'Deletes a pricing rule.',
        'who' => 'Staff with `pricing_rules.manage` (admin, super admin).',
        'request' => '- Path `pricingRule`: the rule `id`. No body.',
        'success' => '**204** with no body. Remove the row.',
        'errors' => "{$pricingRuleNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Ask for confirmation first; a delete can\'t be undone. Orders keep the fee they were given.',
    ],

];
