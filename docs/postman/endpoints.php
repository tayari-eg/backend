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
$orderRouteFields = "- `category_id` (number, required): a subcategory from **Get categories** (one of a main category's `children`).\n- `pickup_lat`, `pickup_lng`, `dropoff_lat`, `dropoff_lng` (numbers, required): the points picked on the map, up to 7 decimals.";
$orderFields = '`id`, `code` (the short code people see, e.g. `TY-7K3P9Q`), `status` (`looking_for_tayar` | `pending` | `picked_up` | `ongoing` | `delivered` | `not_delivered` | `cancelled`), `can_cancel` (true until pickup), `category` (`id`, `name`, `is_food`), `details`, `pickup` (`address`, `lat`, `lng`, `contact_phone`), `dropoff` (`address`, `lat`, `lng`), `receiver` (`name`, `phone`), `goods_value`, `shipping_paid_by` (`sender` | `receiver`), `amount_to_collect` (what the receiver pays: the goods value, plus the shipping fee when the receiver pays it), `distance_km`, `food_extra`, `shipping_fee`, `cancel_reason` (`id`, `name`, or null), `tayar` (null until a tayar accepts; then `name`, `phone` (null once the order is delivered, not delivered or cancelled) and `vehicle` (`type`, `model`, `color`, `plate_number`)), `cancelled_by_type` (null unless cancelled; then `sender`, `staff`, or `system` when no tayar accepted in time), `free_cancel_until` (while `pending`: until when cancelling is free; otherwise null), `cancel_fee_now` (what cancelling now would cost: `"0.00"` before a tayar accepts and until `free_cancel_until`, then the cancellation fee, at most the shipping fee), `cancel_fee` (the fee recorded when the order was cancelled, `"0.00"` otherwise), `search_ends_at` (while `looking_for_tayar`: when Tayari stops looking and cancels the order; otherwise null), `price_accepted_at`, `accepted_at` (when the tayar accepted, or null), `cancelled_at`, `created_at`, `updated_at`. Amounts are strings with two decimals, in EGP; coordinates are strings with 7 decimals. Delivery: `tayar.location` (`lat`, `lng`, `updated_at`: the tayar\'s last position while the order is `pending`, `picked_up` or `ongoing`, otherwise null), `pickup_photos` (the tayar\'s photos at pickup, each with a signed `url`), `picked_up_at`, `receiver_confirmed_at`, `codes_sent_at` (when the receiver\'s codes were last sent by SMS), `delivered_at`, `not_delivered_at`, `not_delivered_reason` (`id`, `name`, or null), `not_delivered_note`, `tracking_token` (for the receiver\'s tracking link)';
$adminOrderFields = '`id`, `code`, `status`, `can_cancel`, `sender` (`id`, `name`, `phone`, `business_name`), `category` (`id`, `name` with `ar` and `en`, `is_food`), `mode` (`id`, `code`, `name` with `ar` and `en`: the mode the order was priced in), `details`, `pickup` (`address`, `lat`, `lng`, `contact_phone`), `dropoff` (`address`, `lat`, `lng`), `receiver` (`name`, `phone`, `user_id` when the receiver has an account), `goods_value`, `shipping_paid_by`, `amount_to_collect`, `fee` (`distance_km`, `base_fare`, `price_per_km`, `distance_fare`, `minimum_fare`, `food_extra`, `shipping_fee`, `tayar_share_percent`, `tayar_earning`, `platform_fee`), `tayar` (`id`, `name`, `phone`, or null until a tayar accepts), `vehicle` (the tayar\'s vehicle for this order: `id`, `type` with `ar` and `en`, `model`, `color`, `plate_number`, or null), `work_session_id` (or null), `cancelled_by_type` (null unless cancelled; then `sender`, `staff` or `system`), `cancel_fee` (owed by the sender for cancelling an accepted order after the free period, `"0.00"` otherwise; charged to the business\'s wallet and added to the tayar\'s), `cancel_fee_owed_to` (`id`, `name` of the tayar it is owed to, or null), `price_accepted_at`, `accepted_at`, `cancelled_at`, `created_at`, `updated_at`. Amounts are strings with two decimals, in EGP. Delivery: `picked_up_at`, `receiver_confirmed_at`, `delivered_at`, `not_delivered_at`, `not_delivered_reason` (`id`, `name` with `ar` and `en`, or null), `not_delivered_note`, `tracking_token`, `receiver_codes` (`sent_at`, `expire_at`, `attempts`, `locked`; never the codes)';
$adminOrderDetailFields = "{$adminOrderFields}, plus `cancelled_by` (`id`, `name`, or null), `cancel_reason` (`id`, `name` with `ar` and `en`, or null) and `status_history` (every status change, oldest first: `from_status` (null for the first row), `to_status`, `changed_by` (`id`, `name`, or null for the system), `note`, `lat`, `lng` (where the tayar was, or null for the sender, staff and the system), `created_at`), and `pickup_photos` (each with a signed `url`)";
$orderNotFound = '- **404:** no order with this id.';
$offerFields = '`id`, `status` (`offered` while it can be accepted; `accepted`, `rejected`, `expired` or `withdrawn` otherwise), `round`, `distance_km` (from the tayar to the pickup, in a straight line, when the offer was made), `tayar_earning` (what this tayar earns with this vehicle, EGP), `expires_at`, `expires_in_seconds` (counted on the server: use it for the countdown, not the phone clock), `order` (`id`, `code`, `mode` (`code`, `name`), `category` (`id`, `name`, `is_food`), `details`, `pickup` (`address`, `lat`, `lng`), `dropoff` (`address`, `lat`, `lng`), `distance_km` (the trip, by road)). No phone numbers until the tayar accepts';
$tayarOrderFields = '`id`, `code`, `status` (`pending` | `picked_up` | `ongoing` | `delivered` | `not_delivered` | `cancelled`), `mode` (`code`, `name`), `category` (`id`, `name`, `is_food`), `details`, `sender` (`name`, `phone`, `business_name`), `pickup` (`address`, `lat`, `lng`, `contact_phone`), `dropoff` (`address`, `lat`, `lng`), `receiver` (`name`, `phone`), `distance_km`, `tayar_earning` (what I earn, EGP). The phones (`sender.phone`, `pickup.contact_phone`, `receiver.phone`) are null once the order is delivered, not delivered or cancelled; the names stay, `cancel_fee` (what the sender owes me for cancelling after the free period, `"0.00"` otherwise; added to my wallet), `accepted_at`, `cancelled_at`, `created_at`. Coordinates are strings with 7 decimals. Delivery: `pickup_photos` (each with a signed `url`), `receiver_codes` (`sent_at`, `expire_at`, `attempts_left`, `problem`: null when a code can be checked now, else `codes_not_sent`, `codes_locked` or `codes_expired`; never the codes), `picked_up_at`, `receiver_confirmed_at`, `delivered_at`, `not_delivered_at`, `not_delivered_reason` (`id`, `name`, or null), `not_delivered_note`';
$adminOfferFields = '`id`, `tayar` (`id`, `name`, `phone`), `work_session_id`, `round`, `distance_km` (tayar to pickup when offered), `tayar_share_percent`, `tayar_earning`, `platform_fee` (what the order would earn with this tayar\'s vehicle), `status` (`offered` | `accepted` | `rejected` | `expired` | `withdrawn`, as of now), `expires_at`, `responded_at` (when the tayar accepted or rejected, or null), `created_at`';
$tayarPositionFields = '- `lat` (number, required): -90 to 90, up to 7 decimals.\n- `lng` (number, required): -180 to 180, up to 7 decimals. The current GPS position, saved on the status history.';
$wrongStep = "- **422:** `errors.status` = \"This order can't move to that step now.\" (another step is due: reload the order).";
$codeErrors = '- **422:** `errors.code`: the code is wrong (":left attempts left"; the refusal code counts as wrong here, and the other way round), the codes are locked after :code_max_attempts wrong codes, they expired (after :codes_valid_hours hours), or they were not sent yet. For locked or expired codes, ask the receiver to request new codes from the tracking link, or contact support.';
$codeThrottled = '- **429:** more than :code_requests_per_minute codes in a minute. Wait for `Retry-After` seconds.';
$noPaymentCheck = 'Delivery does not check payment yet: the payment phase comes later.';
$smsNote = 'The receiver gets the codes only once a real SMS provider is configured on the server; until then they are only written to the log on a local server.';
$trackingFields = '`code`, `status`, `sender_name` (the business name), `dropoff_address`, `tayar` (null until a tayar accepts; then `first_name` and `vehicle` (`type`, `color`, `plate_number`)), `location` (`lat`, `lng`, `updated_at`: the tayar\'s last position while the order is `picked_up` or `ongoing`, otherwise null), `can_resend_codes`, `codes_sent_at`, `picked_up_at`, `receiver_confirmed_at`, `delivered_at`, `not_delivered_at`, `cancelled_at`, `is_finished`. No phone numbers, no codes and no amounts';
$trackingNotFound = '- **404:** no order with this tracking token (or a token that is not 40 letters and digits).';
$offerNotFound = '- **404:** no offer with this id.';
$offerErrors = '- **422:** `errors.offer` explains why: "This offer has expired.", "This order is no longer available." (another tayar took it, or it was cancelled), "This offer was for a work session that has ended. Stay online to get new offers." (the tayar went offline or changed the mode settings), "Finish your current orders before accepting another one." (:max_active_orders accepted order in the zone and area modes, :max_active_orders_long_distance in long distance), or "Hand in the cash you collected before accepting new orders." (the tayar owes more than EGP :max_cash_owed in collected cash: see **Get my wallet**). Remove the offer card and show the message.';
$notApprovedBusiness = '- **403:** `message` explains why: only business accounts can send orders for now, or the business profile is not approved yet. Show the message with a link to the business profile page.';
$routeErrors = '- **422:** `errors.route`: the pickup and drop-off are less than 100 m apart (in a straight line), the drop-off is more than :order_max_distance_km km (by road) from the pickup, Tayari does not deliver on this route yet, or a food order is too far (food only goes up to :order_zone_max_distance_km km, or inside one area). Show it on the map step.';
$cancelReasonFields = "- `cancel_reason_id` (number, required): a reason from **Get cancel reasons**.\n- `note` (string, optional, up to 500 characters).";
$otpCodeError = 'The same error for a wrong code, an expired code, a used code, a code with too many wrong tries (:otp_max_attempts per code) and an unknown phone: `errors.code` = "The code is wrong or has expired…". Show it under the code input and offer **Resend code**.';

$transactionTypes = '`goods_payout` (the goods value of a delivered order, owed to the business), `shipping_fee` (the shipping fee a business pays at delivery when it pays the shipping), `delivery_earning`, `cash_collected` (cash the tayar collected from the receiver and owes Tayari), `commission`, `cancel_fee_earned`, `cancel_fee_charged`, `bonus`, `cash_handed_in`, `payout`, `adjustment`';
$postingTypes = '`order_delivered` | `cancel_fee` | `signup_bonus` | `payout` | `cash_handed_in` | `adjustment` | `reversal`';
$walletFields = '`id`, `type` (`tayar` | `business`), `balance` (what Tayari owes me; negative = what I owe Tayari), `available_balance` (the balance without today\'s earnings and bonuses, which count from :available_from_hour:00 Cairo time), `payable_balance` (what Tayari can pay out now, never below 0), `updated_at`; a tayar wallet also has `locked_bonus` (the signup bonus, held until `bonus_unlock_delivered_orders` delivered orders), `bonus_unlock_delivered_orders`, `cash_to_hand_in` (the collected cash I owe, net of my earnings), `max_cash_owed` and `blocked_from_offers` (true while I owe more than `max_cash_owed`). Amounts are strings with two decimals, in EGP';
$walletTransactionFields = "`id`, `type` ({$transactionTypes}), `amount` (signed: positive adds to the balance, negative takes from it), `balance_after`, `available_at` (when it can be paid out), `order` (`id`, `code`, or null), `posting` (`id`, `type` ({$postingTypes}), `reference` (the transfer reference of a payout or hand-in, or null), `reason` (the adjustment reason in my language, or null), `reversal_of_id` (for a reversal: the posting it undoes)), `created_at`";
$adminWalletFields = '`id`, `type` (`tayar` | `business` | `system`), `system_code` (for Tayari\'s own wallets: `revenue` (commission), `bonus_expense`, `adjustments` or `treasury` (Tayari\'s bank and cash box, shown as the mirror of what Tayari holds); null otherwise), `owner` (`id`, `name`, `phone`, or null for a system wallet), `balance`, `created_at`, `updated_at`';
$adminPostingFields = "`id`, `type` ({$postingTypes}), `reference`, `note` (the staff note), `reason` (`id`, `name` with `ar` and `en`, or null), `created_by` (`id`, `name` of the staff member, or null for the system), `reversal_of_id`, `reversed_by_id` (the reversal that undid it, or null), `can_reverse` (true for a payout, cash handed in or adjustment not reversed yet), `created_at`";
$adminWalletTransactionFields = "`id`, `type`, `amount`, `balance_after`, `available_at`, `order` (`id`, `code`, or null), `posting` ({$adminPostingFields}), `created_at`";
$walletAdjustmentReasonFields = '`id`, `name` (`ar` and `en`), `is_active`, `sort_order`, `postings_count` (how many adjustments and reversals used it), `created_at`, `updated_at`';
$walletNotFound = '- **404:** no wallet with this id.';
$walletReasonNotFound = '- **404:** no adjustment reason with this id.';
$transactionListRequest = "- Query `type` (optional): one transaction type, e.g. `delivery_earning` or `payout`.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `cursor` (from `meta.next_cursor`).";
$businessWalletOnly = '- **403:** `message` = "Only business accounts have a wallet here. Tayars see their wallet in tayar mode.", or the account is blocked or the phone is not verified.';
$idempotencyKey = '- `idempotency_key` (string, required): a UUID the panel makes once per form. Sending the same one again (a double click or a retry) returns the first result with 200 instead of recording it twice.';
$cashOnly = 'Every order is cash for now: the tayar collects `amount_to_collect` from the receiver and owes it to Tayari.';

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
        'errors' => "- **403:** `tayar` needs an approved tayar application (\"Tayar mode is only available after your tayar application is approved.\").\n- **422:** an unknown `mode`, or `errors.status` = \"Finish your orders before going offline or switching to client mode.\" when switching to `client` while holding an accepted, picked-up or ongoing order.\n{$loggedOut}\n{$blocked}\n{$upgrade}\n{$serverError}",
        'notes' => "- Switching to `client` while online sets the tayar offline and closes the work session; it is refused while the tayar holds an accepted, picked-up or ongoing order, even when the tayar is already offline.\n- Approval doesn't change the mode: when `register_as` = `tayar`, `next_step` = `ready` and `last_mode` = `client`, call this once with `tayar` before **Go online**.",
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
        'errors' => "- **422:** `errors.status` = \"Finish your orders before going offline or switching to client mode.\" while holding an accepted, picked-up or ongoing order: keep the location service running.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => "- Going offline while offline also answers 200. The last position is kept.\n- While the tayar holds an order, the server never sets them offline for a stale location either: the sender sees how old the last position is.",
    ],

    'api.v1.tayar.location' => [
        'what' => 'Sends the current position while online. The server keeps only the latest position, never a history.',
        'who' => 'An approved tayar who is online.',
        'request' => '- `lat` (number, required): -90 to 90, up to 7 decimals.\n- `lng` (number, required): -180 to 180, up to 7 decimals.',
        'success' => '**204** with no body.',
        'errors' => "- **422:** `lat`/`lng` missing or out of range, or `errors.status` = \"Go online before sending your location.\" (the tayar was set offline: show the offline state).\n- **429:** more than :tayar_location_per_minute updates in a minute. Wait for `Retry-After` seconds, then send only the latest position.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => '- Send every 5 to 10 seconds while online (at most :tayar_location_per_minute a minute). A tayar with no location for :tayar_offline_minutes minutes is set offline by the server.',
    ],

    'api.v1.tayar.offers.index' => [
        'what' => 'Lists my offers that can still be accepted, for my current work session, newest first.',
        'who' => 'An approved tayar. Offline tayars get an empty list.',
        'request' => "- Query `per_page` (optional, 1 to :max_per_page, default :per_page).\n- Query `cursor` (optional): `meta.next_cursor` from the previous page.",
        'success' => "**200** with `data`: a list of offers, each with {$offerFields}. Plus `meta` (`path`, `per_page`, `next_cursor`, `prev_cursor`) and `links`.",
        'errors' => "- **422:** an invalid `per_page` or `cursor`.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => "- Call it every 5 to 10 seconds while online and on the work screen, and at once when an `order_offer` push arrives: there are no live updates, and a push can arrive late or not at all.\n- An offer lasts :offer_timeout_seconds seconds. Count down from `expires_in_seconds` and remove the card at 0; an offer past its time is gone from the list even before the server marks it expired.\n- Several tayars get the same order at once: the first to accept gets it.",
    ],

    'api.v1.tayar.offers.accept' => [
        'what' => 'Accepts an offer. The order becomes mine (`pending`) if no other tayar took it first.',
        'who' => 'The tayar the offer was made to, online in the same work session.',
        'request' => '- Path `offer`: the offer `id`. No body.',
        'success' => "**200** with `data`, the order: {$tayarOrderFields}. Accepting the same offer again also answers 200 with the order while it is still mine and `pending`; after that it answers 422 \"This order is no longer available.\"",
        'errors' => "{$offerNotFound}\n- **403:** the offer was made to another tayar.\n{$offerErrors}\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => "- Disable the **Accept** button while the request runs.\n- After a 200, open the order screen: the sender and the receiver phones are now included.\n- When this order brings the tayar to the mode's limit (:max_active_orders accepted order in the zone and area modes, :max_active_orders_long_distance in long distance), the tayar's other open offers are withdrawn: reload **Get my offers**.",
    ],

    'api.v1.tayar.offers.reject' => [
        'what' => 'Rejects an offer. The order is never offered to me again.',
        'who' => 'The tayar the offer was made to.',
        'request' => '- Path `offer`: the offer `id`. No body, no reason.',
        'success' => '**204** with no body. Remove the card. Rejecting an offer that already ended also answers 204.',
        'errors' => "{$offerNotFound}\n- **403:** the offer was made to another tayar.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
    ],

    'api.v1.tayar.orders.index' => [
        'what' => 'Lists the orders I accepted, newest first: the Active tab or the Past tab.',
        'who' => 'An approved tayar.',
        'request' => "- Query `status` (optional): `active` (default: pending, picked up, ongoing) or `past` (delivered, not delivered, cancelled).\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page).\n- Query `cursor` (optional): `meta.next_cursor` from the previous page.",
        'success' => "**200** with `data`: a list of orders, each with {$tayarOrderFields}. Plus `meta` (`path`, `per_page`, `next_cursor`, `prev_cursor`) and `links`.",
        'errors' => "- **422:** an invalid `status`, `per_page` or `cursor`.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => '- In the zone and area modes a tayar holds :max_active_orders accepted order at a time; in long distance up to :max_active_orders_long_distance.',
    ],

    'api.v1.tayar.orders.show' => [
        'what' => 'Returns one order I accepted.',
        'who' => 'The tayar who accepted the order.',
        'request' => '- Path `order`: the order `id`. No body.',
        'success' => "**200** with `data`: {$tayarOrderFields}.",
        'errors' => "{$orderNotFound}\n- **403:** another tayar's order, or an order I never accepted.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => '- Reload it when an `order_cancelled` push arrives: the sender or staff can cancel an accepted order before pickup. Then show it as cancelled and stop the trip, with `cancel_fee` when the sender owes me one (added to my wallet).',
    ],

    'api.v1.tayar.orders.pickup' => [
        'what' => 'Confirms the pickup with photos of the items. The order becomes `picked_up`, and the receiver gets the delivery and refusal codes and the tracking link in one SMS.',
        'who' => 'The tayar who accepted the order (`pending`).',
        'request' => "- Multipart form (POST).\n- Path `order`: the order `id`.\n- `photos[]` (files, required): 1 to 4 images (jpg, jpeg, png or webp), up to :max_upload_mb MB each.\n{$tayarPositionFields}",
        'success' => "**200** with `data`, the order: {$tayarOrderFields}.",
        'errors' => "{$orderNotFound}\n- **403:** another tayar's order.\n{$wrongStep}\n- **422:** `photos` missing, more than 4, or not images.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => "- The codes are made and sent by the server within about a minute; `receiver_codes.sent_at` shows when. {$smsNote}\n- From now on nobody can cancel the order.",
    ],

    'api.v1.tayar.orders.receiver-confirmed' => [
        'what' => 'The tayar called the receiver, who is there today: the order is on the way (`ongoing`).',
        'who' => 'The tayar of a `picked_up` order.',
        'request' => "- Path `order`: the order `id`.\n{$tayarPositionFields}",
        'success' => "**200** with `data`, the order: {$tayarOrderFields}.",
        'errors' => "{$orderNotFound}\n- **403:** another tayar's order.\n{$wrongStep}\n- **422:** `lat`/`lng` missing or out of range.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => '- Receiver unreachable or not there today: don\'t confirm; contact support, who closes the order as not delivered.',
    ],

    'api.v1.tayar.orders.deliver' => [
        'what' => 'Delivers the order with the receiver\'s delivery code. The order becomes `delivered`.',
        'who' => 'The tayar of an `ongoing` order.',
        'request' => "- Path `order`: the order `id`.\n- `code` (string, required): the :code_length-digit delivery code the receiver reads out.\n{$tayarPositionFields}",
        'success' => "**200** with `data`, the order: {$tayarOrderFields}.",
        'errors' => "{$orderNotFound}\n- **403:** another tayar's order.\n{$wrongStep}\n{$codeErrors}\n{$codeThrottled}\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => "- The code is never shown to the tayar: the receiver has it. {$noPaymentCheck}\n- Wrong codes are shared with **Mark not delivered**: :code_max_attempts in total, then the codes lock until new ones are sent.\n- {$cashOnly} Delivering adds my `delivery_earning` to my wallet and the collected `amount_to_collect` as `cash_collected` (see **Get my wallet**).",
    ],

    'api.v1.tayar.orders.not-delivered' => [
        'what' => 'The receiver refused the order at the door and gave the refusal code: the order becomes `not_delivered`, with a reason.',
        'who' => 'The tayar of an `ongoing` order.',
        'request' => "- Path `order`: the order `id`.\n- `code` (string, required): the :code_length-digit refusal code.\n- `not_delivered_reason_id` (number, required): a reason from **Get not-delivered reasons**.\n- `note` (string, optional, up to 500 characters).\n{$tayarPositionFields}",
        'success' => "**200** with `data`, the order: {$tayarOrderFields}.",
        'errors' => "{$orderNotFound}\n- **403:** another tayar's order.\n{$wrongStep}\n{$codeErrors}\n- **422:** `not_delivered_reason_id` is not an active not-delivered reason.\n{$codeThrottled}\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => '- Not delivered is final for now: returning the goods to the sender comes in a later version. Contact support for what to do with them.',
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
        'notes' => '- The tayar can switch to tayar mode and go online at once.\n- A first approval adds the EGP :signup_bonus signup bonus to the tayar\'s wallet (once per tayar; an application approved again after a review gets none).',
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
        'notes' => "- The shipping fee will be: the larger of `minimum_fare` and `base_fare` + `price_per_km` × km, plus `food_extra` for food orders. The tayar gets `tayar_share_percent` of it and Tayari keeps the rest. New orders are priced with the mode's rule for every vehicle (`vehicle_type` null); a rule for one vehicle type doesn't change the price a customer sees.\n- A rule with a `vehicle_type` is for that type; the rule with `vehicle_type` null is for every other vehicle in the mode.",
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

    'api.v1.public.order-reasons.index' => [
        'what' => 'Lists the active reasons of one type, in display order, for the cancel dialog.',
        'who' => 'Anyone, no login.',
        'request' => "- Query `type` (required): `cancel` (the cancel dialog) or `not_delivered` (the tayar's and staff's not-delivered dialog).\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page` (optional, starts at 1).\n- `Accept-Language` decides the language of the names.",
        'success' => '**200** with `data`: a list of `{ id, name }` in display order, plus `meta` and `links` for pages.',
        'errors' => "- **422:** `type` is missing or unknown, or `per_page` is out of range.\n{$upgrade}\n{$serverError}",
        'notes' => '- Send the chosen `id` as `cancel_reason_id` in **Cancel order**. Load the list when the cancel dialog opens.',
    ],

    'api.v1.client.orders.estimate' => [
        'what' => 'Prices a trip before the order is created. Nothing is saved.',
        'who' => 'An approved business account (individual customers come later).',
        'request' => $orderRouteFields,
        'success' => '**200** with `data`: `{ distance_km, food_extra, shipping_fee }`, strings with two decimals (km and EGP). Show `shipping_fee` as the price, and keep it to send with **Create order**.',
        'errors' => "- **422:** field errors.\n{$routeErrors}\n{$loggedOut}\n{$notApprovedBusiness}\n{$upgrade}\n{$serverError}",
        'notes' => "- The price is the larger of the minimum fare and the base fare + price per km × distance, plus a food extra for food categories. The distance is the straight line times a road factor, so it is a little longer than on the map.\n- The customer never picks a mode: Tayari picks it from the trip.\n- Call it again whenever the category or a point changes.",
    ],

    'api.v1.client.orders.store' => [
        'what' => 'Creates an order at the price from **Estimate price**. The order starts in `looking_for_tayar`.',
        'who' => 'An approved business account (individual customers come later).',
        'request' => "- `idempotency_key` (string, required): a UUID made once when the order form opens, and sent again on every retry of the same order.\n{$orderRouteFields}\n- `details` (string, optional, up to 2000 characters): what is in the package.\n- `pickup_address`, `dropoff_address` (strings, required, up to 255 characters).\n- `pickup_contact_phone` (string, optional): someone to call at the pickup, if not the sender; any Egyptian mobile format.\n- `receiver_name` (string, required, up to 255 characters) and `receiver_phone` (string, required, any Egyptian mobile format).\n- `goods_value` (required): the price of the goods the receiver pays, 0 to 1000000, up to 2 decimals.\n- `shipping_paid_by` (required): `sender` or `receiver`.\n- `shipping_fee` (required): the `shipping_fee` from **Estimate price**, as shown to the user.",
        'success' => "**201** with `data`, the new order: {$orderFields}. **200** with the same order when this `idempotency_key` already made one (a double tap or a retry).",
        'errors' => "- **422:** field errors. When the price changed since the estimate: `errors.shipping_fee` = \"The shipping price is now 45.00 EGP. Check the new price and confirm again.\" Show the new price and ask the user to confirm again (send the new `shipping_fee` with the same `idempotency_key`). When the order this `idempotency_key` made was deleted: `errors.idempotency_key` = \"This request key belongs to a deleted order. Use a new request key.\" Make a new key and send again.\n{$routeErrors}\n{$loggedOut}\n{$notApprovedBusiness}\n{$upgrade}\n{$serverError}",
        'notes' => "- Disable the **Send** button while the request runs, but keep the same `idempotency_key` for retries: the server never creates two orders for one key. Make a new key only for a new order.\n- Phones come back as `+201XXXXXXXXX`.\n- The search for a tayar starts at once: the order is offered to up to :offers_per_round nearby tayars at a time, each offer lasting :offer_timeout_seconds seconds, in a wider circle each round around the pickup (:first_radius_km km, then :radius_step_km km more each round, up to :max_radius_km km; a round with nobody in reach widens at once). When no tayar accepts by `search_ends_at` (:search_timeout_minutes minutes after the price was accepted), Tayari cancels the order (`cancelled_by_type` = `system`) and sends an `order_no_tayar` push.",
    ],

    'api.v1.client.orders.index' => [
        'what' => 'Lists my orders, newest first: the Active tab or the Past tab.',
        'who' => 'The sender (an active, verified client account).',
        'request' => "- Query `status` (optional): `active` (default: looking for a tayar, pending, picked up, ongoing) or `past` (delivered, not delivered, cancelled).\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page).\n- Query `cursor` (optional): `meta.next_cursor` from the previous page.",
        'success' => "**200** with `data`: a list of orders, each with {$orderFields}. Plus `meta` (`path`, `per_page`, `next_cursor`, `prev_cursor`) and `links` (`first`, `last`, `prev`, `next`).",
        'errors' => "- **422:** an invalid `status`, `per_page` or `cursor`.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- This list uses **cursor pages**, not page numbers: for infinite scroll, send `cursor` = `meta.next_cursor` until it is null. There is no total.\n- Pull to refresh starts again without `cursor`.",
    ],

    'api.v1.client.orders.show' => [
        'what' => 'Returns one of my orders.',
        'who' => 'The sender of the order.',
        'request' => '- Path `order`: the order `id`. No body.',
        'success' => "**200** with `data`: {$orderFields}.",
        'errors' => "{$orderNotFound}\n- **403:** the order belongs to someone else.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- Show **Cancel order** only when `can_cancel` is true.\n- While `status` = `looking_for_tayar`, call it again every 10 seconds while the screen is open (and when an `order_accepted` or `order_no_tayar` push arrives): there are no live updates. Stop when the status changes.\n- Once a tayar accepts (`status` = `pending`), show the `tayar` card: name, a call button for `phone`, and the vehicle. The tayar's location comes in a later version.\n- **Cancel order** stays available until pickup. Before confirming, show the cost from `cancel_fee_now`: free until `free_cancel_until` (:free_cancel_seconds seconds after the tayar accepted), then a cancellation fee owed to the tayar. Reload the order when the dialog opens, so the amount is current.\n- `cancelled_by_type` = `system` means no tayar accepted in time: offer to send the order again.\n- While the order is `pending`, `picked_up` or `ongoing`, show the tayar on the map from `tayar.location` and how old `updated_at` is, and keep calling this every 10 to 15 seconds while the screen is open. There is no ETA.\n- After pickup, nobody can cancel. The receiver gets the delivery and refusal codes and the tracking link by SMS (only once a real SMS provider is configured); share the link from `tracking_token` if needed. Delivery does not check payment yet.",
    ],

    'api.v1.client.orders.cancel' => [
        'what' => 'Cancels one of my orders before pickup. Free before a tayar accepts and for :free_cancel_seconds seconds after; later, a cancellation fee owed to the tayar is charged to my business wallet.',
        'who' => 'The sender of the order.',
        'request' => "- Path `order`: the order `id`.\n{$cancelReasonFields}",
        'success' => "**200** with `data`, the order (`status` = `cancelled`, `can_cancel` = false, `cancel_fee` = the fee that was recorded, `\"0.00\"` when free): {$orderFields}.",
        'errors' => "{$orderNotFound}\n- **422:** field errors, or `errors.status` = \"This order can no longer be cancelled.\" once the order is picked up or finished (reload the order).\n- **403:** the order belongs to someone else.\n{$loggedOut}\n{$clientOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- Ask for confirmation in a dialog with the reasons from **Get cancel reasons** and an optional note. For an accepted order, show `cancel_fee_now` from **Get order** in the dialog.\n- Whether the cancel is free is decided by the server at the moment it cancels: a cancel sent after `free_cancel_until` records the fee even if the dialog showed it as free. The fee is at most the shipping fee.\n- The fee is taken from my business wallet (`cancel_fee_charged`) and added to the tayar's wallet, in the same moment as the cancel.\n- The tayar of an accepted order gets an `order_cancelled` push.",
    ],

    'api.v1.admin.orders.index' => [
        'what' => 'Lists every order, newest first, with filters and search.',
        'who' => 'Staff with `orders.view` (support, ops, admin, super admin).',
        'request' => "- Query `status` (optional): one order status, e.g. `looking_for_tayar` or `cancelled`.\n- Query `sender_id`, `tayar_id` (the tayar's user `id`), `category_id`, `mode_id` (optional numbers).\n- Query `created_from`, `created_to` (optional, `YYYY-MM-DD`): business days in Cairo time, both included.\n- Query `search` (optional, up to 100 characters): an order code (`TY-7K3P9Q` or `7k3p9q`), or a full phone number matched against the receiver's and the sender's phone.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of orders, each with {$adminOrderFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid filter, `created_to` before `created_from`, `search` too long or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- `fee.tayar_earning` and `fee.platform_fee` are estimates from the general pricing rule until a tayar accepts the order; then they are what the accepting tayar earns with their vehicle (the mode\'s rule for that vehicle type when there is one).',
    ],

    'api.v1.admin.orders.show' => [
        'what' => 'Returns one order with its fee breakdown and its full status history.',
        'who' => 'Staff with `orders.view`.',
        'request' => '- Path `order`: the order `id`. No body.',
        'success' => "**200** with `data`: {$adminOrderDetailFields}.",
        'errors' => "{$orderNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- A `status_history` row with `changed_by` = null was made by the system (an order cancelled because no tayar accepted in time).',
    ],

    'api.v1.admin.orders.offers.index' => [
        'what' => 'Lists every offer of one order, newest first: which tayars got it, in which round, and whether they accepted, rejected, let it expire, or lost it to another tayar.',
        'who' => 'Staff with `orders.view` (support, ops, admin, super admin).',
        'request' => "- Path `order`: the order `id`.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of offers, each with {$adminOfferFields}. Plus `meta` and `links` for pages.",
        'errors' => "{$orderNotFound}\n- **422:** `per_page` is out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => "- `withdrawn` means the order was taken by another tayar or cancelled while the offer was open.\n- An offer lasts :offer_timeout_seconds seconds; each round goes to up to :offers_per_round tayars.",
    ],

    'api.v1.admin.orders.cancel' => [
        'what' => 'Cancels an order before pickup, for example at the sender\'s request, or when the tayar who accepted it stopped answering.',
        'who' => 'Staff with `orders.manage` (ops, admin, super admin).',
        'request' => "- Path `order`: the order `id`.\n{$cancelReasonFields}",
        'success' => "**200** with `data`, the order (`status` = `cancelled`, `cancelled_by_type` = `staff`): {$adminOrderDetailFields}. The last `status_history` row names the staff member.",
        'errors' => "{$orderNotFound}\n- **422:** field errors, or `errors.status` = \"This order can no longer be cancelled.\" once the order is picked up or finished.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => "- Ask for confirmation first, with the reasons from **Get cancel reasons**.\n- Staff can cancel an accepted (`pending`) order at any time, never with a cancellation fee (`cancel_fee` stays `\"0.00\"`). The order keeps its `tayar`, and the tayar gets an `order_cancelled` push with `cancelled_by_type` = `staff`.\n- Offers still open are withdrawn.",
    ],

    'api.v1.admin.orders.not-delivered' => [
        'what' => 'Closes a picked-up or ongoing order as not delivered, without a code, when the receiver can\'t be reached, so the tayar is never stuck holding it. The only status staff can force.',
        'who' => 'Staff with `orders.manage` (ops, admin, super admin).',
        'request' => "- Path `order`: the order `id`.\n- `not_delivered_reason_id` (number, required): a reason from **Get not-delivered reasons**.\n- `note` (string, required, up to 500 characters): what happened, for the record.",
        'success' => "**200** with `data`, the order (`status` = `not_delivered`): {$adminOrderDetailFields}. The last `status_history` row names the staff member and has no position.",
        'errors' => "{$orderNotFound}\n- **422:** field errors, or `errors.status` = \"Only a picked-up or ongoing order can be closed as not delivered.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => "- The sender and the tayar both get an `order_not_delivered` push.\n- Ask for confirmation first: it can't be undone, and returning the goods comes in a later version.",
    ],

    'api.v1.admin.orders.resend-codes' => [
        'what' => 'Sends the receiver new delivery and refusal codes by SMS, for example after a lock-out or a lost SMS. The old codes stop working and the wrong-code count starts again.',
        'who' => 'Staff with `orders.manage` (ops, admin, super admin).',
        'request' => '- Path `order`: the order `id`. No body.',
        'success' => '**204** with no body. `receiver_codes.sent_at` changes within about a minute.',
        'errors' => "{$orderNotFound}\n- **422:** `errors.status` = \"Codes are only sent while the order is with the tayar.\" (not picked up or ongoing).\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => "- Staff never see the codes. {$smsNote}",
    ],

    'api.v1.public.tracking.show' => [
        'what' => 'The receiver\'s tracking page: the order\'s status, the tayar\'s first name and vehicle, and the tayar\'s last position while the goods are on the way.',
        'who' => 'Anyone with the tracking link (no login). The receiver gets it by SMS at pickup; the sender has `tracking_token` on the order.',
        'request' => '- Path `trackingToken`: the 40-character token from the link.',
        'success' => "**200** with `data`: {$trackingFields}.",
        'errors' => "{$trackingNotFound}\n- **429:** more than :tracking_per_minute requests a minute from this IP address. Wait for `Retry-After` seconds.\n{$serverError}",
        'notes' => "- Reload every 15 seconds while `is_finished` is false; there are no live updates. Show how old `location.updated_at` is.\n- The page keeps answering after the order ends, with the final status and no position.\n- The codes are never shown here: they come only by SMS. {$smsNote}",
    ],

    'api.v1.public.tracking.resend-codes' => [
        'what' => 'Sends the receiver new delivery and refusal codes by SMS to the receiver\'s phone (never shown on the page).',
        'who' => 'Anyone with the tracking link (no login), while `can_resend_codes` is true.',
        'request' => '- Path `trackingToken`: the 40-character token from the link. No body.',
        'success' => '**204** with no body. Tell the receiver a new SMS is on its way; the old codes stop working.',
        'errors' => "{$trackingNotFound}\n- **422:** `errors.status` = \"Codes are only sent while the order is with the tayar.\"\n- **429:** more than :resend_codes_per_hour resends an hour for this order, or too many requests from this IP address. Wait for `Retry-After` seconds.\n{$serverError}",
        'notes' => "- {$smsNote}",
    ],

    'api.v1.tayar.wallet.show' => [
        'what' => 'Returns my tayar wallet: what Tayari owes me, what can be paid out now, the locked signup bonus and the collected cash I owe.',
        'who' => 'An approved tayar (tayar mode).',
        'request' => '- No body.',
        'success' => "**200** with `data`: {$walletFields}.",
        'errors' => "{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => "- {$cashOnly} A delivered order adds my `delivery_earning` and takes the `cash_collected`, so the balance goes negative while I hold cash.\n- While `blocked_from_offers` is true (I owe more than EGP :max_cash_owed net), no new offers arrive and accepting answers 422. Hand the cash in to Tayari; staff record it and the block lifts.\n- Payouts are made by Tayari staff (no withdrawal button yet). My earnings and bonuses count for payouts from :available_from_hour:00 Cairo time on the day I earn them.",
    ],

    'api.v1.tayar.wallet.transactions' => [
        'what' => 'Lists my tayar wallet\'s transactions, newest first.',
        'who' => 'An approved tayar (tayar mode).',
        'request' => $transactionListRequest,
        'success' => "**200** with `data`: a list of transactions, each with {$walletTransactionFields}. Plus `meta` (`next_cursor`, `prev_cursor`, `per_page`) and `links`.",
        'errors' => "- **422:** an unknown `type` or `per_page` out of range.\n{$loggedOut}\n{$notTayar}\n{$upgrade}\n{$serverError}",
        'notes' => '- A delivered order shows two rows: `delivery_earning` (plus) and `cash_collected` (minus). Tap a row with an `order` to open **My order**.',
    ],

    'api.v1.client.wallet.show' => [
        'what' => 'Returns my business wallet: what Tayari owes me for delivered goods, less shipping and cancellation fees.',
        'who' => 'A business account (approved or not; a new one shows zeros).',
        'request' => '- No body.',
        'success' => "**200** with `data`: {$walletFields}.",
        'errors' => "{$loggedOut}\n{$businessWalletOnly}\n{$upgrade}\n{$serverError}",
        'notes' => "- {$cashOnly} Tayari then owes the business the goods value (`goods_payout`), less the shipping fee when the business pays the shipping (`shipping_fee`) and any cancellation fee (`cancel_fee_charged`). The balance can go negative.\n- Tayari pays the business by transfer, recorded by staff (`payout`); there is no withdrawal button yet.",
    ],

    'api.v1.client.wallet.transactions' => [
        'what' => 'Lists my business wallet\'s transactions, newest first.',
        'who' => 'A business account.',
        'request' => $transactionListRequest,
        'success' => "**200** with `data`: a list of transactions, each with {$walletTransactionFields}. Plus `meta` (`next_cursor`, `prev_cursor`, `per_page`) and `links`.",
        'errors' => "- **422:** an unknown `type` or `per_page` out of range.\n{$loggedOut}\n{$businessWalletOnly}\n{$upgrade}\n{$serverError}",
        'notes' => '- Tap a row with an `order` to open **Order details**.',
    ],

    'api.v1.admin.wallets.index' => [
        'what' => 'Lists every wallet, Tayari\'s own system wallets included, newest first or by balance.',
        'who' => 'Staff with `wallets.view` (support, ops, admin, super admin).',
        'request' => "- Query `type` (optional): `tayar`, `business` or `system`.\n- Query `owing` (optional): `true` for tayar and business wallets below zero (tayars holding cash, businesses owing fees); system wallets are never listed.\n- Query `search` (optional, up to 100 characters): a full phone number, or part of the owner's name.\n- Query `sort` (optional): `balance` (lowest first: who owes most) or `-balance` (highest first: who is owed most).\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of wallets, each with {$adminWalletFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid filter or sort, `search` too long or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- All balances together always add up to zero: every money movement is written on two sides.',
    ],

    'api.v1.admin.wallets.show' => [
        'what' => 'Returns one wallet with its payout summary.',
        'who' => 'Staff with `wallets.view`.',
        'request' => '- Path `wallet`: the wallet `id`. No body.',
        'success' => "**200** with `data`: {$adminWalletFields}, plus `summary` (`available_balance`, `payable_balance` (the most a payout can take now), `locked_bonus`, `cash_to_hand_in` (the most a cash hand-in can take), `blocked_from_offers`).",
        'errors' => "{$walletNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.wallets.transactions' => [
        'what' => 'Lists one wallet\'s transactions, newest first, with each posting\'s staff note and whether it can be reversed.',
        'who' => 'Staff with `wallets.view`.',
        'request' => "- Path `wallet`: the wallet `id`.\n{$transactionListRequest}",
        'success' => "**200** with `data`: a list of transactions, each with {$adminWalletTransactionFields}. Plus `meta` (`next_cursor`, `prev_cursor`, `per_page`) and `links`.",
        'errors' => "{$walletNotFound}\n- **422:** an unknown `type` or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Show **Reverse** on a row only while `posting.can_reverse` is true.',
    ],

    'api.v1.admin.wallets.settlements.store' => [
        'what' => 'Records money that changed hands outside the app: a payout to a tayar or a business, or cash a tayar handed in.',
        'who' => 'Staff with `wallets.settle` (ops, admin, super admin).',
        'request' => "- Path `wallet`: the wallet `id`.\n{$idempotencyKey}\n- `kind` (string, required): `payout` (Tayari paid the owner) or `cash_handed_in` (a tayar handed in collected cash).\n- `amount` (string, required): more than 0, up to 2 decimals. A payout is at most `summary.payable_balance`; a hand-in at most `summary.cash_to_hand_in`.\n- `reference` (string, required, up to 100 characters): the transfer or receipt reference.\n- `note` (string, optional, up to 500 characters).",
        'success' => "**201** with `data`, the posting: {$adminPostingFields}, plus `transactions` (`id`, `wallet_id`, `type`, `amount`, `balance_after`, `available_at`: the owner's wallet and Tayari's treasury). **200** with the same body for a repeated `idempotency_key`.",
        'errors' => "{$walletNotFound}\n- **422:** field errors; `errors.amount` = \"The most you can pay out now is EGP …\" or \"The tayar owes EGP … in cash at most.\"; `errors.wallet` for a system wallet, or a hand-in on a business wallet.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- The owner gets a `wallet_payout_recorded` or `wallet_cash_received` push.\n- A mistake is undone with **Reverse posting**, never edited.',
    ],

    'api.v1.admin.wallets.adjustments.store' => [
        'what' => 'Adjusts a wallet by hand, with a reason: a positive amount gives the owner money, a negative one takes it.',
        'who' => 'Staff with `wallets.adjust` (admin, super admin).',
        'request' => "- Path `wallet`: the wallet `id` (a tayar or business wallet).\n{$idempotencyKey}\n- `amount` (string, required): not 0, up to 2 decimals, -99999999.99 to 99999999.99.\n- `reason_id` (number, required): an active reason from **List adjustment reasons**.\n- `note` (string, required, up to 500 characters).",
        'success' => "**201** with `data`, the posting: {$adminPostingFields}, plus `transactions`. **200** for a repeated `idempotency_key`.",
        'errors' => "{$walletNotFound}\n- **422:** field errors (`errors.amount` = \"The amount can't be zero.\"), or `errors.wallet` = \"Tayari's own wallets can't be adjusted.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- The balance may go below zero. The owner gets a `wallet_adjusted` push and sees the reason, never the note.',
    ],

    'api.v1.admin.wallet-postings.reverse' => [
        'what' => 'Undoes a payout, cash hand-in or adjustment recorded by mistake, with a mirror posting. The original stays in the history.',
        'who' => 'Staff with `wallets.adjust` (admin, super admin).',
        'request' => "- Path `walletPosting`: the posting `id` (`posting.id` on a transaction row).\n- `reason_id` (number, required): an active reason from **List adjustment reasons**.\n- `note` (string, required, up to 500 characters).",
        'success' => "**201** with `data`, the reversal (`type` = `reversal`, `reversal_of_id` = the posting it undoes): {$adminPostingFields}, plus `transactions`. **200** with the same reversal when the posting was already reversed.",
        'errors' => "- **404:** no posting with this id.\n- **422:** field errors, or `errors.posting` = \"Only payouts, cash handed in and adjustments can be reversed. Correct other entries with an adjustment.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Order, cancellation fee and bonus postings are never reversed: correct them with an adjustment. The owner gets a `wallet_adjusted` push.',
    ],

    'api.v1.admin.wallet-adjustment-reasons.index' => [
        'what' => 'Lists the reasons staff pick for adjustments and reversals, active and inactive, in sort order.',
        'who' => 'Staff with `wallets.view` (support, ops, admin, super admin).',
        'request' => "- Query `is_active` (optional): `true` for the Active tab and the reason pickers, `false` for the Inactive tab; leave it out for all.\n- Query `search` (optional, up to 100 characters): part of the Arabic or English name, any letter case.\n- Query `per_page` (optional, 1 to :max_per_page, default :per_page) and `page`.",
        'success' => "**200** with `data`: a list of reasons, each with {$walletAdjustmentReasonFields}. Plus `meta` and `links` for pages.",
        'errors' => "- **422:** an invalid `is_active`, `search` too long or `per_page` out of range.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Show **Delete** only when `postings_count` is 0; otherwise offer **Deactivate**.',
    ],

    'api.v1.admin.wallet-adjustment-reasons.show' => [
        'what' => 'Returns one adjustment reason with its name in both languages, to fill the edit form.',
        'who' => 'Staff with `wallets.view`.',
        'request' => '- Path `walletAdjustmentReason`: the reason `id`. No body.',
        'success' => "**200** with `data`: {$walletAdjustmentReasonFields}.",
        'errors' => "{$walletReasonNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.wallet-adjustment-reasons.store' => [
        'what' => 'Creates an adjustment reason.',
        'who' => 'Staff with `wallets.adjust` (admin, super admin).',
        'request' => "- `name.ar` and `name.en` (strings, both required): up to 100 characters each, as `{ \"name\": { \"ar\": \"تعويض\", \"en\": \"Compensation\" } }`.\n- `is_active` (boolean, optional): default `true`.\n- `sort_order` (number, optional): 0 to 65535. Left out, the reason goes to the end of the list.",
        'success' => "**201** with `data`, the new reason: {$walletAdjustmentReasonFields}.",
        'errors' => "- **422:** field errors. A name another reason already uses (ignoring letter case) gets `errors[\"name.ar\"]` or `errors[\"name.en\"]` = \"Another adjustment reason already has this name.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.wallet-adjustment-reasons.update' => [
        'what' => 'Updates an adjustment reason. Send only the fields that change.',
        'who' => 'Staff with `wallets.adjust` (admin, super admin).',
        'request' => "- Path `walletAdjustmentReason`: the reason `id`.\n- Any of `name` (both `ar` and `en` when sent), `is_active`, `sort_order` (same rules as create).",
        'success' => "**200** with `data`, the updated reason: {$walletAdjustmentReasonFields}.",
        'errors' => "{$walletReasonNotFound}\n- **422:** field errors, for example a name with one language only, or a name another reason already uses.\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Renaming a reason changes the name past adjustments show to their owners.',
    ],

    'api.v1.admin.wallet-adjustment-reasons.activate' => [
        'what' => 'Activates an adjustment reason, so staff can pick it again.',
        'who' => 'Staff with `wallets.adjust` (admin, super admin).',
        'request' => '- Path `walletAdjustmentReason`: the reason `id`. No body.',
        'success' => "**200** with `data`, the reason (`is_active` = true): {$walletAdjustmentReasonFields}.",
        'errors' => "{$walletReasonNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.wallet-adjustment-reasons.deactivate' => [
        'what' => 'Deactivates an adjustment reason: staff can no longer pick it; past adjustments keep it.',
        'who' => 'Staff with `wallets.adjust` (admin, super admin).',
        'request' => '- Path `walletAdjustmentReason`: the reason `id`. No body.',
        'success' => "**200** with `data`, the reason (`is_active` = false): {$walletAdjustmentReasonFields}.",
        'errors' => "{$walletReasonNotFound}\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
    ],

    'api.v1.admin.wallet-adjustment-reasons.destroy' => [
        'what' => 'Deletes an adjustment reason no adjustment or reversal uses.',
        'who' => 'Staff with `wallets.adjust` (admin, super admin).',
        'request' => '- Path `walletAdjustmentReason`: the reason `id`. No body.',
        'success' => '**204** with no body. Remove the row.',
        'errors' => "{$walletReasonNotFound}\n- **422:** a posting uses the reason: `errors.wallet_adjustment_reason` = \"This reason is used by wallet adjustments, so it cannot be deleted. Deactivate it instead.\"\n{$loggedOut}\n{$staffOnly}\n{$serverError}",
        'notes' => '- Ask for confirmation first; a delete can\'t be undone.',
    ],

];
