# Laravel M-Pesa Integration Package

[![build](https://github.com/botnet-dobbs/laravel-mpesa-sdk/actions/workflows/main.yml/badge.svg)](https://github.com/botnet-dobbs/laravel-mpesa-sdk/actions/workflows/main.yml) ![Packagist Downloads](https://img.shields.io/packagist/dt/botnetdobbs/laravel-mpesa-sdk)

A thin, dependency-injection friendly SDK for Safaricom's M-Pesa Daraja API.
STK Push, B2C, B2B Express Checkout, C2B, account balance, transaction status,
and reversals.

No models, migrations, or routes are forced on you. The package handles the
integration layer: OAuth tokens and caching, the STK password and timestamp,
initiator credential encryption, request validation, and callback parsing.
Your app owns the payment records, queues, and business logic.

For upstream API changes, always refer to the
[Safaricom Developer Portal](https://developer.safaricom.co.ke/APIs).

## Requirements

- PHP 8.2+

| Laravel Version  |
|------------------|
| Laravel 11.x     |
| Laravel 12.x     |
| Laravel 13.x     |

---

## Installation

```bash
composer require botnetdobbs/laravel-mpesa-sdk
```

Publish the config file:

```bash
php artisan vendor:publish --tag=mpesa-config
```

Set your credentials in `.env`. Get them from the My Apps page on the Daraja portal:

```env
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
```

That is enough for STK Push in sandbox. B2C, balance, status, and reversal also
need initiator credentials and a certificate. Details:

- [Getting Started](docs/getting-started.md): first call, how authentication works
- [Configuration](docs/configuration.md): every option and the go-live checklist

---

## Quick Look

### Initiate a payment

Inject the `Client` contract. There is no facade.

```php
use Botnetdobbs\Mpesa\Contracts\Client;
use Botnetdobbs\Mpesa\Exceptions\MpesaException;

class PaymentController extends Controller
{
    public function __construct(private Client $mpesa)
    {
    }

    public function checkout(Order $order)
    {
        try {
            $response = $this->mpesa->stkPush([
                'BusinessShortCode' => config('mpesa.business.short_codes.paybill'),
                'Amount' => $order->total,
                'PhoneNumber' => $order->customer_phone,    // 2547XXXXXXXX
                'CallBackURL' => route('mpesa.stk.callback'),
                'AccountReference' => $order->reference,    // shown on the customer's prompt
            ]);
        } catch (MpesaException $e) {
            logger()->error('mpesa.stk_push.error', ['error' => $e->getMessage(), 'status' => $e->status]);

            return response()->json(['message' => 'Payment could not be started. Please try again.'], 502);
        }

        if (! $response->isSuccessful()) {
            // Daraja rejected the request. Log it, return a safe message.
            logger()->warning('mpesa.stk_push.rejected', ['body' => (array) $response->getData()]);

            return response()->json(['message' => 'Payment could not be started. Please try again.'], 422);
        }

        // Save this ID. It links the callback back to the order.
        $order->update([
            'checkout_request_id' => $response->getData()->CheckoutRequestID,
            'status' => 'awaiting_payment',
        ]);

        return response()->json([
            'message' => 'Check your phone and enter your M-PESA PIN.',
            'checkout_request_id' => $order->checkout_request_id,
        ], 202);
    }
}
```

The package generates the Daraja `Password` and `Timestamp` fields for you and
refreshes OAuth tokens behind the scenes.

### Receive the result

M-Pesa posts the payment outcome to your callback URL. Save first, respond
fast, process in a job:

```php
use Botnetdobbs\Mpesa\Contracts\CallbackProcessor;
use Botnetdobbs\Mpesa\Contracts\CallbackResponder;

class MpesaCallbackController extends Controller
{
    public function __construct(
        private CallbackProcessor $processor,
        private CallbackResponder $responder,
    ) {
    }

    public function stk(Request $request)
    {
        MpesaRawCallback::create(['payload' => $request->getContent()]); // save first

        ProcessStkCallback::dispatch($request->all());                   // work in a job

        return $this->responder->success();                              // release Safaricom
    }
}
```

That is the whole loop. The guides below cover every field, payload, and
production rule.

---

## Documentation

- [Getting Started](docs/getting-started.md)
- [Configuration](docs/configuration.md)
- [STK Push (M-Pesa Express)](docs/stk-push.md)
- [B2C Payments](docs/b2c.md)
- [B2B Express Checkout](docs/b2b-express-checkout.md)
- [C2B (Customer to Business)](docs/c2b.md)
- [Account Balance](docs/account-balance.md)
- [Transaction Status](docs/transaction-status.md)
- [Transaction Reversal](docs/reversal.md)
- [Callbacks](docs/callbacks.md)
- [Responses and Errors](docs/responses-and-errors.md)

---

## Client Method Reference

All methods live on `Botnetdobbs\Mpesa\Contracts\Client` and return a response
object. See [Responses and Errors](docs/responses-and-errors.md) for the
response helpers.

| Method | Daraja API | What it does |
|--------|-----------|--------------|
| `stkPush($data)` | M-Pesa Express | Sends a payment prompt to a customer's phone ([guide](docs/stk-push.md)) |
| `stkQuery($data)` | M-Pesa Express Query | Checks the status of a prompt ([guide](docs/stk-push.md#query-a-payment-status)) |
| `b2c($data)` | B2C | Pays a customer from your business account ([guide](docs/b2c.md)) |
| `b2b($data)` | B2B Express Checkout | Prompts another merchant to pay you from their till ([guide](docs/b2b-express-checkout.md)) |
| `c2bRegister($data)` | C2B | Registers your confirmation and validation URLs ([guide](docs/c2b.md)) |
| `c2bSimulate($data)` | C2B | Simulates a customer payment, sandbox only ([guide](docs/c2b.md#simulate-a-payment-sandbox-only)) |
| `accountBalance($data)` | Account Balance | Queries your shortcode account balances ([guide](docs/account-balance.md)) |
| `transactionStatus($data)` | Transaction Status | Looks up the state of any transaction ([guide](docs/transaction-status.md)) |
| `reversal($data)` | Reversal | Reverses a completed transaction ([guide](docs/reversal.md)) |

For inbound callbacks, use `CallbackProcessor` and `CallbackResponder`. See
[Callbacks](docs/callbacks.md).

---

## Common Questions

**Is there a facade?**

No. Inject `Botnetdobbs\Mpesa\Contracts\Client` through the container. This
keeps your code testable and swappable.

**My callback never arrived. Now what?**

M-Pesa does not retry failed callbacks. Recover with `stkQuery()` for STK
payments or `transactionStatus()` for everything else. See
[Callbacks](docs/callbacks.md) for the full recovery pattern.

**Why does `isSuccessful()` return false for a B2B request that worked?**

The B2B Express Checkout acknowledgement has a different shape (`code` instead
of `ResponseCode`). Read it with `getData()`. See
[B2B Express Checkout](docs/b2b-express-checkout.md).

**Do I need to protect my callback URLs?**

Yes. They are public endpoints. Restrict them to Safaricom's published IPs with
middleware and match every callback against a transaction you actually
initiated. See [Callbacks](docs/callbacks.md#verify-the-callback-source-ip-whitelisting).

**How do I test without touching real money?**

Use `MPESA_ENV=sandbox` with the test credentials from the Daraja portal. In
your own test suite, fake the HTTP layer with Laravel's `Http::fake()`.

**Can one app use multiple shortcodes or both environments at once?**

Not out of the box. The client reads one set of credentials from config. If you
need more, rebind the `Client` contract with different config per tenant or
context.

---

## For Contributors

Run the tests:

```bash
composer test
```

Generate an HTML coverage report, then open `coverage/index.html`:

```bash
composer test:coverage
```

Code quality:

```bash
composer check-style   # check code style
composer fix-style     # fix code style issues
composer analyse       # static analysis
```

---

## Credits

- [Lazarus Odhiambo](https://github.com/botnetdobbs)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
