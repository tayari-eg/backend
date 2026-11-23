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

];
