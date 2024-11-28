<?php

return [
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'environment' => env('MPESA_ENV', 'sandbox'),

    'defaults' => [
        'timeout' => 30,
        'connect_timeout' => 10,
    ]
];
