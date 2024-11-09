<?php

return [
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'environment' => env('MPESA_ENV', 'sandbox'),

    'endpoints' => [
        'live' => [
            'base_url' => 'https://api.safaricom.co.ke',
            'oauth_token' => '/oauth/v1/generate?grant_type=client_credentials',
            'b2c_payment' => '/mpesa/b2c/v3/paymentrequest',
            'b2b_payment' => '/mpesa/b2b/v1/paymentrequest',
            'c2b_register' => '/mpesa/c2b/v1/registerurl',
            'c2b_simulate' => '/mpesa/c2b/v1/simulate',
            'account_balance' => '/mpesa/accountbalance/v1/query',
            'transaction_status' => '/mpesa/transactionstatus/v1/query',
            'reversal' => '/mpesa/reversal/v1/request',
            'stk_push' => '/mpesa/stkpush/v1/processrequest',
            'stk_query' => '/mpesa/stkpushquery/v1/query'
        ],
        'sandbox' => [
            'base_url' => 'https://sandbox.safaricom.co.ke',
            'oauth_token' => '/oauth/v1/generate?grant_type=client_credentials',
            'b2c_payment' => '/mpesa/b2c/v3/paymentrequest',
            'b2b_payment' => '/mpesa/b2b/v1/paymentrequest',
            'c2b_register' => '/mpesa/c2b/v1/registerurl',
            'c2b_simulate' => '/mpesa/c2b/v1/simulate',
            'account_balance' => '/mpesa/accountbalance/v1/query',
            'transaction_status' => '/mpesa/transactionstatus/v1/query',
            'reversal' => '/mpesa/reversal/v1/request',
            'stk_push' => '/mpesa/stkpush/v1/processrequest',
            'stk_query' => '/mpesa/stkpushquery/v1/query'
        ]
    ],

    'defaults' => [
        'timeout' => 30,
        'connect_timeout' => 10,
    ]
];
