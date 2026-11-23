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
| Every line starts with a tag, then the method and full path, " — ", what
| changed, and the apps it affects ("Apps: …"; for Breaking, what they must
| change). One endpoint per line; a Docs line may have no endpoint.
|
| - Added: a new endpoint.
| - Changed: an existing endpoint behaves differently, old clients keep working.
| - Breaking: an existing client can break (needs a major version).
|   Before launch, "Breaking (before launch):" instead, with no major version.
| - Removed: an endpoint removed.
| - Docs: documentation only, no behaviour change.
|
*/

return [

    [
        'version' => '1.5.0',
        'date' => '2026-11-23',
        'changes' => [
            'Added: GET /api/v1/categories — lists the active main categories, each with its active subcategories (id, name, is_food, icon_url, children), for the category picker of a new order; no login. Apps: Website, Flutter client mode.',
            'Added: GET /api/v1/modes — lists the active modes (id, code, name, description, allows_food, requires_area, requires_radius, requires_destination, and radius_km min and max for the zone mode) for the mode picker before going online; no login. Apps: Flutter tayar mode.',
            'Added: GET /api/v1/areas — lists the active areas (id, name, city, center, radius_km) for the area mode picker; no login. Apps: Flutter tayar mode.',
            'Breaking (before launch): POST /api/v1/tayar/online — mode_id is now required, plus area_id for the area mode, radius_km (1 to 15 km by default) for the zone mode, or destination_address, destination_lat and destination_lng for the long distance mode; a field the picked mode doesn\'t take is refused. Apps: Flutter tayar mode: show the Choose mode sheet (Get modes, Get areas) before Go online and send these fields.',
            'Changed: POST /api/v1/tayar/online — going online again with other settings (mode, area, radius or destination) ends the work session and starts a new one; the same settings still only move the position. work_session in the response now has mode (id, code, name), area, radius_km and destination. Apps: Flutter tayar mode.',
            'Changed: POST /api/v1/tayar/offline — work_session in the response has the new mode, area, radius_km and destination fields when present (it is null after going offline). Apps: Flutter tayar mode.',
            'Changed: DELETE /api/v1/admin/vehicle-types/{vehicleType} — also refused (422, errors.vehicle_type) when a pricing rule uses the type. Apps: Admin panel.',
            'Added: GET /api/v1/admin/categories — lists main categories and subcategories with main_only, parent_id, is_active, is_food and search filters, in sort order, with parent, icon_url and children_count. Apps: Admin panel.',
            'Added: GET /api/v1/admin/categories/{category} — one category with its name in both languages, its parent and its icon. Apps: Admin panel.',
            'Added: POST /api/v1/admin/categories — creates a main category or a subcategory (parent_id must be a main category; names unique among siblings ignoring letter case; is_food defaults to the parent\'s). Apps: Admin panel.',
            'Added: PATCH /api/v1/admin/categories/{category} — updates or moves a category; two levels at most, and a category with subcategories can\'t move under another one. Apps: Admin panel.',
            'Added: POST /api/v1/admin/categories/{category}/activate — shows the category in the public list again. Apps: Admin panel.',
            'Added: POST /api/v1/admin/categories/{category}/deactivate — hides the category (and a main category\'s subcategories) from the public list. Apps: Admin panel.',
            'Added: DELETE /api/v1/admin/categories/{category} — deletes a category without subcategories, with its icon; otherwise 422 asking to deactivate it instead. Apps: Admin panel.',
            'Added: POST /api/v1/admin/categories/{category}/icon — uploads or replaces the category icon (multipart icon: jpg, png or webp) on the public disk. Apps: Admin panel.',
            'Added: DELETE /api/v1/admin/categories/{category}/icon — removes the category icon. Apps: Admin panel.',
            'Added: GET /api/v1/admin/modes — lists the three fixed modes with is_active and search filters, with work_sessions_count. Apps: Admin panel.',
            'Added: GET /api/v1/admin/modes/{mode} — one mode with its name and description in both languages. Apps: Admin panel.',
            'Added: PATCH /api/v1/admin/modes/{mode} — updates a mode\'s names, description, allows_food, is_active or sort_order; the code never changes and modes can\'t be created or deleted. Apps: Admin panel.',
            'Added: POST /api/v1/admin/modes/{mode}/activate — lets tayars go online in the mode again. Apps: Admin panel.',
            'Added: POST /api/v1/admin/modes/{mode}/deactivate — hides the mode from the tayar\'s mode picker; tayars online in it keep working. Apps: Admin panel.',
            'Added: GET /api/v1/admin/areas — lists areas with is_active and search filters, in sort order, with work_sessions_count. Apps: Admin panel.',
            'Added: GET /api/v1/admin/areas/{area} — one area with its name and city in both languages. Apps: Admin panel.',
            'Added: POST /api/v1/admin/areas — creates an area (names and city in both languages, center_lat, center_lng, radius_km 0.5 to 50). Apps: Admin panel.',
            'Added: PATCH /api/v1/admin/areas/{area} — updates an area; send only the fields that change. Apps: Admin panel.',
            'Added: POST /api/v1/admin/areas/{area}/activate — shows the area in the area picker again. Apps: Admin panel.',
            'Added: POST /api/v1/admin/areas/{area}/deactivate — hides the area from the area picker; tayars online in it keep working. Apps: Admin panel.',
            'Added: DELETE /api/v1/admin/areas/{area} — deletes an area no work session used; otherwise 422 asking to deactivate it instead. Apps: Admin panel.',
            'Added: GET /api/v1/admin/pricing-rules — lists pricing rules with mode_id, vehicle_type_id and is_active filters; amounts are strings with two decimals. Apps: Admin panel.',
            'Added: GET /api/v1/admin/pricing-rules/{pricingRule} — one pricing rule. Apps: Admin panel.',
            'Added: POST /api/v1/admin/pricing-rules — creates a pricing rule for a mode and one vehicle type or every vehicle (base_fare, price_per_km, minimum_fare, food_extra, tayar_share_percent); one rule per mode and vehicle type. Apps: Admin panel.',
            'Added: PATCH /api/v1/admin/pricing-rules/{pricingRule} — updates a pricing rule; send only the fields that change. Apps: Admin panel.',
            'Added: POST /api/v1/admin/pricing-rules/{pricingRule}/activate — turns a pricing rule on. Apps: Admin panel.',
            'Added: POST /api/v1/admin/pricing-rules/{pricingRule}/deactivate — turns a pricing rule off. Apps: Admin panel.',
            'Added: DELETE /api/v1/admin/pricing-rules/{pricingRule} — deletes a pricing rule. Apps: Admin panel.',
            'Docs: Admin panel folders 10. Categories, 11. Modes, 12. Areas and 13. Pricing rules; Account is now folder 14. The permissions list adds categories, modes, areas and pricing_rules (view and manage); ops and support can view all four. Apps: Admin panel.',
            'Docs: Flutter folder 10. Tayar mode · Go online and offline — the Choose mode sheet (Get modes, then Get areas, a radius slider or a destination picker) before Go online, and Change mode while online. Apps: Flutter tayar mode.',
            'Docs: Flutter folder 13. Client mode · Categories and Website folder 6. Categories — the category picker for a new order (Get categories). Apps: Flutter client mode, Website.',
        ],
    ],

    [
        'version' => '1.4.0',
        'date' => '2026-11-23',
        'changes' => [
            'Docs: Flutter folder 4. Push notifications — the server now sends FCM pushes to every saved device: notification title and body in the user\'s language, and data whose values are all strings with a type key (only test for now, from the push:test command); ask for notification permission, show a local notification while the app is open, and open the screen for data.type when the user taps it. Apps: Flutter client mode, Flutter tayar mode.',
            'Docs: Flutter folder 4. Push notifications — Android uses the default notification channel (no channel_id is sent); tokens FCM reports as unregistered or invalid are removed on the server. Apps: Flutter client mode, Flutter tayar mode.',
        ],
    ],

    [
        'version' => '1.3.0',
        'date' => '2026-11-23',
        'changes' => [
            'Breaking (before launch): POST /api/v1/auth/register — register_as (business or tayar, from the choice screen) is now required, and account_type is no longer read (business sets the business account type; tayar keeps individual). The response user has register_as and next_step (complete_business_profile or complete_tayar_application). Apps: Flutter client mode, Flutter tayar mode, Website: send register_as (the website always sends business) and stop sending account_type.',
            'Changed: GET /api/v1/auth/me — the user now has register_as and next_step (complete_business_profile, business_under_review, business_rejected, complete_tayar_application, tayar_under_review, tayar_rejected or ready), and tayar_profile has rejection_reason and can_reapply_at. Apps: Flutter client mode, Flutter tayar mode, Website, Admin panel.',
            'Changed: POST /api/v1/auth/token — the user in data now has register_as, next_step, and rejection_reason and can_reapply_at in tayar_profile. Apps: Flutter client mode, Flutter tayar mode.',
            'Changed: POST /api/v1/auth/login — the user in data now has register_as, next_step, and rejection_reason and can_reapply_at in tayar_profile. Apps: Website, Admin panel.',
            'Changed: POST /api/v1/client/national-id — the user in data now has register_as, next_step, and rejection_reason and can_reapply_at in tayar_profile. Apps: Flutter client mode, Website.',
            'Changed: PATCH /api/v1/auth/mode — the user in data now has register_as, next_step, and rejection_reason and can_reapply_at in tayar_profile. Apps: Flutter tayar mode.',
            'Docs: a new Onboarding section in the Flutter app folder (splash, the Tayar or Business owner choice, sign up, then the screen for next_step), and the website sign up now sends register_as = business.',
            'Docs: PATCH /api/v1/auth/mode — approval does not change the mode: when register_as = tayar, next_step = ready and last_mode = client, the app calls it once with tayar before going online.',
            'Docs: POST /api/v1/tayar/online — a newly approved tayar still in client mode calls Switch mode with tayar once first.',
            'Docs: GET /api/v1/auth/me — next_step ready: staff are always ready; older accounts without register_as are ready unless they are a business account or have a tayar application.',
            'Docs: Flutter folder 5. Business profile — the intro now says business accounts (account_type = business; new sign ups have register_as = business).',
            'Docs: Website folder 4. Business onboarding — the intro now says business accounts (account_type = business; new sign ups have register_as = business).',
            'Docs: Website folder 2. Sign up and login — after Log in, complete_tayar_application, tayar_under_review and tayar_rejected open a "continue in the Tayari app" page, since the website is for business accounts only.',
        ],
    ],

    [
        'version' => '1.2.0',
        'date' => '2026-11-21',
        'changes' => [
            'Added: GET /api/v1/vehicle-types — lists the active vehicle types (name, requires_license, can_carry_food) for the vehicle type picker; no login. Apps: Flutter tayar mode.',
            'Added: POST /api/v1/client/tayar-profile — applies to be a tayar (multipart): national ID number (its birth date must be real and not in the future), national ID front and back, criminal record, personal photo (image), driving licence, and the first vehicle (type, plate, model, colour, year, vehicle licence, 1 to 4 photos as images). A bicycle needs no plate and no licences. Apps: Flutter tayar mode.',
            'Added: GET /api/v1/client/tayar-profile — the user\'s tayar application: status, rejection reason, can_reapply_at, documents with signed URLs, and the vehicle. Apps: Flutter tayar mode.',
            'Added: PATCH /api/v1/client/tayar-profile — changes the national ID number; a new number sends an approved or rejected application back to pending, and waits for can_reapply_at after a rejection. Apps: Flutter tayar mode.',
            'Added: POST /api/v1/client/tayar-profile/documents/{collection} — replaces one personal document (national_id_front, national_id_back, criminal_record, personal_photo, driving_license) and sends the application back to pending. Apps: Flutter tayar mode.',
            'Added: PATCH /api/v1/client/tayar-profile/vehicle — updates the vehicle; a new type or plate sends the application back to pending, model, colour and year do not. Apps: Flutter tayar mode.',
            'Added: POST /api/v1/client/tayar-profile/vehicle/documents/{collection} — replaces the vehicle licence, or all the vehicle photos at once (1 to 4 images), and sends the application back to pending. Apps: Flutter tayar mode.',
            'Added: PATCH /api/v1/auth/mode — switches between client and tayar mode and saves last_mode; tayar mode needs an approved application, and switching to client sets an online tayar offline. Apps: Flutter tayar mode.',
            'Added: POST /api/v1/tayar/online — goes online at a position (lat, lng) with the active vehicle and starts a work session; answers 403 until the app is in tayar mode. Apps: Flutter tayar mode.',
            'Added: POST /api/v1/tayar/offline — goes offline and closes the work session. Apps: Flutter tayar mode.',
            'Added: POST /api/v1/tayar/location — sends the current position (lat, lng) while online, with its own rate limit (20 a minute by default); a tayar with no location for a few minutes (5 by default) is set offline. Apps: Flutter tayar mode.',
            'Added: GET /api/v1/admin/tayar-applications — lists tayar applications by status (pending by default), with vehicle type and search filters, oldest first. Apps: Admin panel.',
            'Added: GET /api/v1/admin/tayar-applications/{tayarProfile} — one application with every document, the vehicle and the review history. Apps: Admin panel.',
            'Added: POST /api/v1/admin/tayar-applications/{tayarProfile}/approve — approves a pending application and its vehicle. Apps: Admin panel.',
            'Added: POST /api/v1/admin/tayar-applications/{tayarProfile}/reject — rejects a pending application with a reason; the applicant may change it again after a wait (24 hours by default). Apps: Admin panel.',
            'Added: GET /api/v1/admin/tayars — lists approved tayars with online, vehicle type, blocked and search filters, newest first. Apps: Admin panel.',
            'Added: GET /api/v1/admin/tayars/{tayar} — one approved tayar with documents, vehicle, last position and review history. Apps: Admin panel.',
            'Added: GET /api/v1/admin/vehicle-types — lists active and inactive vehicle types with is_active and search filters, in sort order, with vehicles_count. Apps: Admin panel.',
            'Added: GET /api/v1/admin/vehicle-types/{vehicleType} — one vehicle type with its name in both languages. Apps: Admin panel.',
            'Added: POST /api/v1/admin/vehicle-types — creates a vehicle type (names unique per language ignoring letter case, requires_license, can_carry_food). Apps: Admin panel.',
            'Added: PATCH /api/v1/admin/vehicle-types/{vehicleType} — updates a vehicle type; send only the fields that change. Apps: Admin panel.',
            'Added: POST /api/v1/admin/vehicle-types/{vehicleType}/activate — shows the type in the public list again. Apps: Admin panel.',
            'Added: POST /api/v1/admin/vehicle-types/{vehicleType}/deactivate — hides the type from the public list and new applications. Apps: Admin panel.',
            'Added: DELETE /api/v1/admin/vehicle-types/{vehicleType} — deletes a type no vehicle uses; otherwise 422 asking to deactivate it instead. Apps: Admin panel.',
            'Changed: GET /api/v1/auth/me — the user now has last_mode (client or tayar) and tayar_profile (id, status, is_online, or null); staff permissions can now include tayars.view, tayars.review, vehicle_types.view and vehicle_types.manage. Apps: Flutter client mode, Flutter tayar mode, Website, Admin panel.',
            'Changed: POST /api/v1/auth/token — the user in data now has last_mode and tayar_profile. Apps: Flutter client mode, Flutter tayar mode.',
            'Changed: POST /api/v1/auth/login — the user in data now has last_mode and tayar_profile, and the new staff permissions. Apps: Website, Admin panel.',
            'Changed: POST /api/v1/auth/register — the user in data now has last_mode (client) and tayar_profile (null). Apps: Flutter client mode, Website.',
            'Changed: POST /api/v1/client/national-id — the user in data now has last_mode and tayar_profile. Apps: Flutter client mode, Website.',
            'Changed: GET /api/v1/media/{media} — also opens tayar and vehicle documents, through signed URLs given only to the owner and to staff with tayars.view. Apps: Flutter tayar mode, Admin panel.',
            'Changed: POST /api/v1/admin/users/{customer}/block — blocking a tayar also ends tayar mode: their mobile logins are signed out (401 on the tayar endpoints), and the tayar is set offline within a few minutes (5 by default). Apps: Admin panel.',
            'Docs: new Flutter folders 7 to 12 (Tayar mode: become a tayar, application status, switch mode, go online and offline, location updates, vehicle) and new admin folders 7 to 9 (tayar applications review, tayars, vehicle types); the admin Account folder moves to 10.',
        ],
    ],

    [
        'version' => '1.1.1',
        'date' => '2026-11-21',
        'changes' => [
            'Breaking (before launch): POST /api/v1/client/business-profile — the document field tax_register is now commercial_register (the commercial register, السجل التجاري), in the form and in documents in the response. Apps: Flutter client mode, Website: send the file as commercial_register and read documents.commercial_register.',
            'Breaking (before launch): POST /api/v1/client/business-profile/documents/{collection} — the path value tax_register is now commercial_register, and so is the key in documents. Apps: Flutter client mode, Website: call documents/commercial_register and read documents.commercial_register.',
            'Breaking (before launch): GET /api/v1/client/business-profile — documents.tax_register is now documents.commercial_register. Apps: Flutter client mode, Website: read documents.commercial_register.',
            'Breaking (before launch): PATCH /api/v1/client/business-profile — documents.tax_register is now documents.commercial_register in the response. Apps: Flutter client mode, Website: read documents.commercial_register.',
            'Breaking (before launch): GET /api/v1/auth/me — business_profile.documents.tax_register is now business_profile.documents.commercial_register. Apps: Flutter client mode, Website: read business_profile.documents.commercial_register.',
            'Breaking (before launch): POST /api/v1/auth/token — business_profile.documents.tax_register in data is now business_profile.documents.commercial_register. Apps: Flutter client mode: read the new key.',
            'Breaking (before launch): POST /api/v1/auth/login — business_profile.documents.tax_register in data is now business_profile.documents.commercial_register. Apps: Website: read the new key.',
            'Breaking (before launch): POST /api/v1/client/national-id — business_profile.documents.tax_register in data is now business_profile.documents.commercial_register. Apps: Flutter client mode, Website: read the new key.',
            'Breaking (before launch): GET /api/v1/admin/business-profiles — documents.tax_register is now documents.commercial_register. Apps: Admin panel: read documents.commercial_register.',
            'Breaking (before launch): GET /api/v1/admin/business-profiles/{businessProfile} — documents.tax_register is now documents.commercial_register. Apps: Admin panel: read documents.commercial_register.',
            'Breaking (before launch): POST /api/v1/admin/business-profiles/{businessProfile}/approve — documents.tax_register is now documents.commercial_register in the response. Apps: Admin panel: read the new key.',
            'Breaking (before launch): POST /api/v1/admin/business-profiles/{businessProfile}/reject — documents.tax_register is now documents.commercial_register in the response. Apps: Admin panel: read the new key.',
            'Breaking (before launch): GET /api/v1/admin/users/{customer} — business_profile.documents.tax_register is now business_profile.documents.commercial_register. Apps: Admin panel: read the new key.',
            'Breaking (before launch): POST /api/v1/admin/users/{customer}/block — business_profile.documents.tax_register is now business_profile.documents.commercial_register in the response. Apps: Admin panel: read the new key.',
            'Breaking (before launch): POST /api/v1/admin/users/{customer}/unblock — business_profile.documents.tax_register is now business_profile.documents.commercial_register in the response. Apps: Admin panel: read the new key.',
            'Changed: POST /api/v1/client/business-profile — two submits at the same moment now answer 403 "You already have a business profile.", the same as a second submit later, instead of 500. Apps: Flutter client mode, Website.',
            'Changed: GET /api/v1/admin/users — `%`, `_` and `\\` in search are now matched as typed, not as wildcards. Apps: Admin panel.',
            'Changed: GET /api/v1/admin/business-types — `%`, `_` and `\\` in search are now matched as typed, not as wildcards. Apps: Admin panel.',
            'Changed: POST /api/v1/auth/otp/resend — a request that sends nothing (unknown phone, verified phone, cooldown) now takes about as long as a real send; the answer is the same. Apps: Flutter client mode, Website (no change needed).',
            'Changed: POST /api/v1/auth/forgot-password — a request that sends nothing (unknown phone, cooldown) now takes about as long as a real send; the answer is the same. Apps: Flutter client mode, Website (no change needed).',
            'Docs: POST /api/v1/admin/business-profiles/{businessProfile}/approve — documents that staff (except super admins) cannot approve their own business profile (403).',
            'Docs: POST /api/v1/admin/business-profiles/{businessProfile}/reject — documents that staff (except super admins) cannot reject their own business profile (403).',
        ],
    ],

    [
        'version' => '1.1.0',
        'date' => '2026-11-21',
        'changes' => [
            'Added: GET /api/v1/admin/business-types — lists active and inactive business types with is_active and search filters (Arabic or English name, any letter case), in sort order, with business_profiles_count. Apps: Admin panel.',
            'Added: GET /api/v1/admin/business-types/{businessType} — one business type with its name in both languages. Apps: Admin panel.',
            'Added: POST /api/v1/admin/business-types — creates a business type (names unique per language ignoring letter case, is_active, sort_order). Apps: Admin panel.',
            'Added: PATCH /api/v1/admin/business-types/{businessType} — updates a business type; send only the fields that change. Apps: Admin panel.',
            'Added: POST /api/v1/admin/business-types/{businessType}/activate — shows the type in the public list again. Apps: Admin panel.',
            'Added: POST /api/v1/admin/business-types/{businessType}/deactivate — hides the type from the public list; existing profiles keep it. Apps: Admin panel.',
            'Added: DELETE /api/v1/admin/business-types/{businessType} — deletes a type no business profile uses; otherwise 422 asking to deactivate it instead. Apps: Admin panel.',
            'Added: GET /api/v1/admin/users — lists customer and business accounts (never staff), newest first, with search by name, full phone or part of a phone, and filters (account type, status, phone verified, national ID status, business profile status). Apps: Admin panel.',
            'Added: GET /api/v1/admin/users/{customer} — one customer with the national ID and the business profile, links and documents. Apps: Admin panel.',
            'Added: POST /api/v1/admin/users/{customer}/block — blocks a customer with a reason and signs out their mobile logins; blocking again replaces the reason. Apps: Admin panel.',
            'Added: POST /api/v1/admin/users/{customer}/unblock — unblocks a customer. Apps: Admin panel.',
            'Added: GET /api/v1/admin/staff — lists staff with their role and status (super admins only). Apps: Admin panel.',
            'Added: POST /api/v1/admin/staff — creates a staff member with the admin, ops or support role (super admins only). Apps: Admin panel.',
            'Added: PUT /api/v1/admin/staff/{staff}/role — changes a staff member\'s role; nobody can change their own role, and the last active super admin cannot be demoted. Apps: Admin panel.',
            'Added: POST /api/v1/admin/staff/{staff}/block — blocks a staff member with a reason; nobody can block themselves, and the last active super admin cannot be blocked. Apps: Admin panel.',
            'Added: POST /api/v1/admin/staff/{staff}/unblock — unblocks a staff member. Apps: Admin panel.',
            'Added: GET /api/v1/admin/business-profiles/{businessProfile} — one business profile for the Review details page, with reviewed_by. Apps: Admin panel.',
            'Changed: GET /api/v1/auth/me — staff permissions can now include business_types.view, business_types.manage, users.view, users.block and staff.manage; admin, ops and support get more of them. Apps: Admin panel.',
            'Changed: POST /api/v1/auth/login — the user in data has the same new staff permissions. Apps: Admin panel.',
            'Docs: POST /api/v1/auth/token — the login rate limit is per phone number from the same IP address, plus an hourly limit per phone number.',
            'Docs: POST /api/v1/auth/login — the login rate limit is per phone number from the same IP address, plus an hourly limit per phone number.',
            'Docs: the reason example in the reject and block requests is now a neutral placeholder.',
        ],
    ],

    [
        'version' => '1.0.0',
        'date' => '2026-11-21',
        'changes' => [
            'Added: GET /api/v1/app-config — the minimum and latest app versions per platform, the phone verification switch and the server time; an app below the minimum version gets 426 on the other endpoints and must update. Apps: Flutter client mode.',
            'Added: GET /sanctum/csrf-cookie — starts a web session (the XSRF-TOKEN cookie) before logging in, and again after a 419. Apps: Website, Admin panel.',
            'Added: POST /api/v1/auth/register — creates an individual or business account and, when phone verification is on, sends an SMS code. Apps: Flutter client mode, Website.',
            'Added: POST /api/v1/auth/verify-phone — confirms the phone with the SMS code. Apps: Flutter client mode, Website.',
            'Added: POST /api/v1/auth/otp/resend — sends a new code for phone verification or a password reset, with the same answer for registered and unknown phones. Apps: Flutter client mode, Website.',
            'Added: POST /api/v1/auth/token — mobile login; returns a Bearer token. Apps: Flutter client mode.',
            'Added: POST /api/v1/auth/login — web login with a session cookie. Apps: Website, Admin panel.',
            'Added: POST /api/v1/auth/forgot-password — sends a password reset code by SMS, with the same answer for registered and unknown phones. Apps: Flutter client mode, Website.',
            'Added: POST /api/v1/auth/reset-password — sets a new password with the reset code and signs the user out of every device. Apps: Flutter client mode, Website.',
            'Added: GET /api/v1/auth/me — the logged-in user with the national ID, the business profile, and roles and permissions (always returned: empty for customers, every permission for a super admin). Apps: Flutter client mode, Website, Admin panel.',
            'Added: PUT /api/v1/auth/password — changes the password of the logged-in user. Apps: Flutter client mode, Website, Admin panel.',
            'Added: POST /api/v1/auth/logout — ends the mobile token or the web session. Apps: Flutter client mode, Website, Admin panel.',
            'Added: POST /api/v1/auth/devices — saves the FCM push token of the device. Apps: Flutter client mode.',
            'Added: DELETE /api/v1/auth/devices — removes the FCM push token of the device. Apps: Flutter client mode.',
            'Added: GET /api/v1/business-types — lists the active business types for the business type picker. Apps: Flutter client mode, Website.',
            'Added: GET /api/v1/client/business-profile — the business profile with its review status. Apps: Flutter client mode, Website.',
            'Added: POST /api/v1/client/business-profile — creates the business profile with the documents (national ID front and back, commercial register and tax card). Apps: Flutter client mode, Website.',
            'Added: PATCH /api/v1/client/business-profile — updates the business details; the name, type or tax number sends the profile back to pending. Apps: Flutter client mode, Website.',
            'Added: POST /api/v1/client/business-profile/documents/{collection} — replaces one business document and sends the profile back to pending. Apps: Flutter client mode, Website.',
            'Added: POST /api/v1/client/national-id — uploads the national ID front and back for an optional identity check. Apps: Flutter client mode, Website.',
            'Added: GET /api/v1/media/{media} — opens a private document from the signed url the API returns; apps never build this URL. Apps: Flutter client mode, Website, Admin panel.',
            'Added: GET /api/v1/admin/business-profiles — lists business profiles for review by status. Apps: Admin panel.',
            'Added: POST /api/v1/admin/business-profiles/{businessProfile}/approve — approves a pending business profile. Apps: Admin panel.',
            'Added: POST /api/v1/admin/business-profiles/{businessProfile}/reject — rejects a pending business profile with a reason. Apps: Admin panel.',
            'Added: GET /api/v1/admin/national-ids — lists national IDs waiting for review. Apps: Admin panel.',
            'Added: POST /api/v1/admin/users/{user}/national-id/verify — verifies a pending national ID. Apps: Admin panel.',
            'Added: POST /api/v1/admin/users/{user}/national-id/reject — rejects a pending national ID with a reason. Apps: Admin panel.',
            'Docs: the Postman setup for the cookie flow: allowlist the host of `{{appUrl}}` under Cookies › Domains Allowlist, then call Get CSRF cookie before Log in.',
            'Docs: rate limits are per phone number and per IP address (users on the same network share the IP limit).',
        ],
    ],

];
