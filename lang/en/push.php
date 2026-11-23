<?php

return [

    'test' => [
        'title' => 'Tayari test notification',
        'body' => 'Push notifications are working on this device.',
    ],

    'order_offer' => [
        'title' => 'New order nearby',
        'body' => 'Earn :earning EGP. The pickup is :distance km away. Open Tayari to accept it before it expires.',
    ],

    'order_accepted' => [
        'title' => 'A tayar accepted your order',
        'body' => ':tayar is on the way to pick up order :code.',
    ],

    'order_no_tayar' => [
        'title' => 'No tayar found',
        'body' => 'No tayar accepted order :code in time, so it was cancelled. You can send it again.',
    ],

    'order_cancelled' => [
        'title' => 'Order cancelled',
        'body' => 'Order :code was cancelled. Do not pick it up.',
    ],

    'order_picked_up' => [
        'title' => 'Order picked up',
        'body' => 'The tayar picked up order :code. The receiver gets the delivery code by SMS.',
    ],

    'order_on_the_way' => [
        'title' => 'On the way',
        'body' => 'The receiver confirmed, and order :code is on the way.',
    ],

    'order_delivered' => [
        'title' => 'Order delivered',
        'body' => 'Order :code was delivered.',
    ],

    'order_not_delivered' => [
        'title' => 'Order not delivered',
        'body' => 'Order :code was not delivered. Open it to see why.',
    ],
    'wallet_payout_recorded' => [
        'title' => 'Payout sent',
        'body' => 'Tayari paid you EGP :amount. Open your wallet for the details.',
    ],
    'wallet_cash_received' => [
        'title' => 'Cash received',
        'body' => 'Tayari received EGP :amount in cash from you.',
    ],
    'wallet_adjusted' => [
        'title' => 'Wallet updated',
        'body' => 'Your wallet was adjusted by EGP :amount. Open it to see why.',
    ],
    'wallet_signup_bonus' => [
        'title' => 'Welcome bonus',
        'body' => 'You got a EGP :amount welcome bonus in your wallet.',
    ],

];
