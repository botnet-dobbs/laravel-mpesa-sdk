# Configuration

---

Publish the config file first:

```bash
php artisan vendor:publish --tag=mpesa-config
```

## Environment variables

| Variable | Required | Description |
|---|---|---|
| `MPESA_ENV` | Yes | `sandbox` or `live`. |
| `MPESA_CONSUMER_KEY` | Yes | From your Daraja app. |
| `MPESA_CONSUMER_SECRET` | Yes | From your Daraja app. |
| `MPESA_LIPA_NA_MPESA_PASSKEY` | For STK Push | The config ships with the public sandbox passkey. You must set your own passkey for live. |
| `MPESA_INITIATOR_NAME` | For B2C, balance, status, reversal | API operator username created on the M-PESA portal. |
| `MPESA_INITIATOR_PASSWORD` | For B2C, balance, status, reversal | API operator password. The package encrypts it for you. |
| `MPESA_CERTIFICATE_PATH` | For B2C, balance, status, reversal | Path to the Safaricom public certificate used to encrypt the initiator password. |
| `MPESA_CALLBACK_BASE_URL` | Recommended | Base URL for your callback endpoints. |
| `MPESA_ALLOWED_CALLBACK_IPS` | Recommended for live | Comma-separated list of Safaricom callback IPs for your own middleware. |

## The security credential

B2C, account balance, transaction status, and reversal need a `SecurityCredential`.
This is your initiator password encrypted with the Safaricom public certificate.

The package builds it for you and adds it to each request. You never pass it yourself.
Download the certificate from the Daraja portal and point `MPESA_CERTIFICATE_PATH` at it.
Sandbox and production use different certificates.

The encrypted result is cached for 24 hours. The cache key contains a hash of the
password, never the password itself.

### Initiator password characters

M-Pesa only accepts certain special characters in the initiator password:
`#`, `&`, `%`, and `$`. Other characters can make the password fail. For
example, `(` and `)` are not accepted, and `@` is treated as a normal
character, not a special one. Keep this in mind when the Business
Administrator sets the API operator's password.

### API operator roles

The API operator (initiator) needs the right role on the M-PESA portal for
each API. A missing role is the most common cause of the
"The initiator information is invalid" error.

| API | Required role |
|---|---|
| B2C | ORG B2C API Initiator |
| Transaction Status | Transaction Status query ORG API |
| Reversal | Org Reversals Initiator |

A pending API operator must also be activated by setting its password. That is
done by a portal user with the Set Restricted ORG API PASSWORD role.

## The Lipa Na M-Pesa passkey

Only STK Push and STK Query use the passkey. The default value in the config is the
shared Safaricom sandbox passkey. It works for testing only.

For live, get your organization's passkey from Safaricom and set
`MPESA_LIPA_NA_MPESA_PASSKEY`. If you forget, live STK requests will fail with
credential errors.

## Shortcodes and callback URLs

`config/mpesa.php` has a place for your shortcodes and callback URLs. Fill
these in once and read them in your code instead of repeating literal values:

```php
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
```

Build a callback URL by joining the base with a path:

```php
$callbackUrl = config('mpesa.callbacks.base_url') . config('mpesa.callbacks.paths.stk_push.result');
```

The config ships with paths for STK Push and B2C. Add your own entries under
`callbacks.paths` for balance, status, reversal, or C2B, and read them the
same way. Keep `base_url` free of a trailing slash so the joined URL stays
clean.

Which shortcode key to use where:

| Key | Use for |
|---|---|
| `business.short_codes.paybill` | STK Push `BusinessShortCode`, C2B `ShortCode`, B2B `receiverShortCode` |
| `business.short_codes.till` | STK Push `PartyB` for Buy Goods payments |
| `business.short_codes.default` | B2C `PartyA`, balance and status `PartyA`, reversal `ReceiverParty` |

These keys exist so your app code has one source of truth. The client does not
read them itself. You pass the values in each request.

## Timeouts

```php
'defaults' => [
    'timeout' => 60,          // total request time in seconds
    'connect_timeout' => 15,  // connection time in seconds
],
```

## Endpoints

The package ships with the current Daraja endpoint paths for sandbox and live.
You normally never touch these. If Safaricom changes a path before the package
updates, you can override any endpoint in your app config:

```php
// config/mpesa.php in your app
'endpoints' => [
    'live' => [
        'c2b_register' => '/mpesa/c2b/v2/registerurl',
    ],
],
```

Your values win. The package fills in everything you do not override.

## Go-live checklist

Before switching `MPESA_ENV` to `live`:

1. Set real consumer key and secret from your production Daraja app.
2. Set your live Lipa Na M-Pesa passkey. Do not rely on the sandbox default.
3. Create the API operator on the M-PESA portal and set its initiator name and password.
4. Download the production certificate and update `MPESA_CERTIFICATE_PATH`.
5. Make sure all callback URLs use HTTPS and are reachable from the internet.
6. Restrict your callback routes to Safaricom IPs. Set `MPESA_ALLOWED_CALLBACK_IPS`
   and enforce it with your own middleware. See [Callbacks](callbacks.md).
7. Avoid words like `mpesa`, `safaricom`, `exe`, `cmd`, or `sql` in callback URLs.
   Daraja rejects them.

---

[← Getting Started](getting-started.md) &nbsp;&nbsp; [STK Push (M-Pesa Express) →](stk-push.md)
