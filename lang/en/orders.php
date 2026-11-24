<?php

return [

    'business_account_only' => 'Only business accounts can send orders for now.',
    'business_not_approved' => 'You can send orders once your business profile is approved.',
    'route_too_long' => 'The drop-off is too far from the pickup. Orders can be up to :max km.',
    'route_too_short' => 'The drop-off is too close to the pickup. They must be at least :min m apart.',
    'route_unavailable' => 'Tayari does not deliver on this route yet.',
    'food_too_far' => 'Food can only be delivered over short distances. Pick a closer drop-off.',
    'price_changed' => 'The shipping price is now :fee EGP. Check the new price and confirm again.',
    'idempotency_key_deleted' => 'This request key belongs to a deleted order. Use a new request key.',
    'cannot_cancel' => 'This order can no longer be cancelled.',
    'offer_expired' => 'This offer has expired.',
    'offer_unavailable' => 'This order is no longer available.',
    'offer_session_changed' => 'This offer was for a work session that has ended. Stay online to get new offers.',
    'tayar_busy' => 'Finish your current orders before accepting another one.',
    'tayar_owes_cash' => 'Hand in the cash you collected before accepting new orders.',
    'wrong_step' => 'This order can\'t move to that step now.',
    'codes_not_sent' => 'The receiver\'s codes have not been sent yet. Try again in a minute.',
    'codes_locked' => 'Too many wrong codes. Ask the receiver to request new codes from the tracking link, or contact support.',
    'codes_expired' => 'The receiver\'s codes have expired. Ask the receiver to request new codes from the tracking link.',
    'code_wrong' => 'The code is wrong. :left attempts left.',
    'codes_not_available' => 'Codes are only sent while the order is with the tayar.',
    'cannot_close_not_delivered' => 'Only a picked-up or ongoing order can be closed as not delivered.',
    'receiver_codes_sms' => 'Tayari: order :code from :sender is on its way. Give the tayar the delivery code :delivery when you receive it. Refuse the order only with the code :refusal. Track it: :url',
    'rating_not_delivered' => 'Only delivered orders can be rated.',
    'rating_closed' => 'The time to rate this order is over.',
    'rating_business_only' => 'Only orders sent by a business can be rated for now.',

];
