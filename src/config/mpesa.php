<?php

return [
    "consumer_key" => env("MPESA_CONSUMER_KEY"),
    "consumer_secret" => env("MPESA_CONSUMER_SECRET"),
    // Sandbox default only. Always set MPESA_LIPA_NA_MPESA_PASSKEY for live.
    "lipa_na_mpesa_passkey" => env(
        "MPESA_LIPA_NA_MPESA_PASSKEY",
        "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919"
    ),
    "initiator" => [
        'name' => env("MPESA_INITIATOR_NAME"),
        'password' => env("MPESA_INITIATOR_PASSWORD"),
    ],
    "certificate_path" => env("MPESA_CERTIFICATE_PATH"),
    "environment" => env("MPESA_ENV", "sandbox"),
    "callbacks" => [
        "base_url" => env("MPESA_CALLBACK_BASE_URL", "https://example.com"),
        "paths" => [
            "stk_push" => [
                "result" => "/api/mpesa/callback/stk",
            ],
            "b2c" => [
                "result" => "/api/mpesa/callback/b2c",
                "timeout" => "/api/mpesa/callback/b2c/timeout",
            ],
            // Add more callback paths here
        ]
    ],
    "business" => [
        "short_codes" => [
            "default" => env("MPESA_SHORT_CODE"),
            "till" => env("MPESA_TILL_NUMBER"),
            "paybill" => env("MPESA_PAYBILL_NUMBER"),
        ],
    ],
    "defaults" => [
        "timeout" => 60,
        "connect_timeout" => 15,
    ],
    // Safaricom publishes callback source IPs in their Getting Started guide.
    // Use this list in your own middleware to restrict callback routes.
    // The package does not enforce it.
    "allowed_callback_ips" => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MPESA_ALLOWED_CALLBACK_IPS', ''))
    ))),
];
