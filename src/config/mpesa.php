<?php

return [
    "consumer_key" => env("MPESA_CONSUMER_KEY"),
    "consumer_secret" => env("MPESA_CONSUMER_SECRET"),
    "lipa_na_mpesa_passkey" => env(
        "MPESA_LIPA_NA_MPESA_PASSKEY",
        "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919"
    ),
    "initiator_password" => env("MPESA_INITIATOR_PASSWORD"),
    "certificate_path" => env("MPESA_CERTIFICATE_PATH"),
    "environment" => env("MPESA_ENV", "sandbox"),

    "defaults" => [
        "timeout" => 30,
        "connect_timeout" => 10,
    ]
];
