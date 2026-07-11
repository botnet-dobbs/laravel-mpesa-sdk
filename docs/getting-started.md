# Getting Started

---

## Requirements

- PHP 8.2, 8.3, or 8.4
- Laravel 11, 12, or 13
- A Daraja account with an app created on the [Safaricom Developer Portal](https://developer.safaricom.co.ke/)

## Install

```bash
composer require botnetdobbs/laravel-mpesa-sdk
php artisan vendor:publish --tag=mpesa-config
```

The service provider is auto-discovered. Publishing the config creates `config/mpesa.php`.

## Set your credentials

Add these to your `.env` file. Get the values from the My Apps page on the Daraja portal.

```env
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
```

See [Configuration](configuration.md) for the full list of options.

## Make your first call

Inject the `Client` contract. Do not construct `MpesaClient` yourself.

```php
use Botnetdobbs\Mpesa\Contracts\Client;

class PaymentController extends Controller
{
    public function __construct(private Client $mpesaClient)
    {
    }

    public function pay()
    {
        $response = $this->mpesaClient->stkPush([
            'BusinessShortCode' => '174379',   // Safaricom sandbox test shortcode
            'Amount' => 1,
            'PhoneNumber' => '254722000000',
            'CallBackURL' => config('mpesa.callbacks.base_url')
                . config('mpesa.callbacks.paths.stk_push.result'),
            'AccountReference' => 'INV-001',
        ]);

        if ($response->isSuccessful()) {
            // Request accepted. The payment result arrives on your callback URL.
        }
    }
}
```

There is no facade. Always resolve the client from the container.

## How authentication works

Every Daraja call needs an OAuth access token. The package handles this for you:

1. On the first call, it requests a token using your consumer key and secret.
2. The token is cached. Daraja tokens last 3600 seconds (1 hour).
3. The cached token is reused until just before it expires, then refreshed.

The cache key includes your environment and a hash of your consumer key.
Sandbox and live tokens never mix.

## Next steps

- [STK Push](stk-push.md) to collect payments from customers.
- [Callbacks](callbacks.md) to process the results M-Pesa sends back.
- [Configuration](configuration.md) before you go live.

---

[← README](../README.md) &nbsp;&nbsp; [Configuration →](configuration.md)
