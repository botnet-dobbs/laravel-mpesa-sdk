# Callbacks

---

Most Daraja APIs are asynchronous. The direct response is only an
acknowledgement. The real result posts to a URL you provide. This page covers
how to receive those callbacks, respond to them, and keep the endpoints safe.

## The two contracts

```php
use Botnetdobbs\Mpesa\Contracts\CallbackProcessor;   // parses the request
use Botnetdobbs\Mpesa\Contracts\CallbackResponder;   // builds the reply
```

Both resolve from the container like any Laravel binding.

## The recommended pipeline

Your callback endpoint has one job: confirm receipt and hand off. All actual
processing happens in the background.

```
Safaricom Callback
    |
Log + persist raw request           <- do this before anything else
    |
Return HTTP 200 + ResultCode 0      <- release Safaricom immediately
    |
Dispatch queue job
    |
Idempotency check                   <- skip if already handled
    |
Apply business logic
    |
Mark processed / failed / reconciled
```

## Route setup

Register callback routes in `routes/api.php`. Routes defined there are already
exempt from CSRF verification.

```php
use App\Http\Controllers\MpesaCallbackController;

Route::prefix('mpesa/callback')->group(function () {
    Route::post('stk', [MpesaCallbackController::class, 'handleStkCallback']);
    Route::post('b2c/result', [MpesaCallbackController::class, 'handleB2cResult']);
});
```

## A callback controller

A production-shaped controller. It saves the raw payload, checks the callback
belongs to a payment you initiated, hands the work to a queue, and responds.

```php
namespace App\Http\Controllers;

use App\Jobs\ProcessB2cResult;
use App\Jobs\ProcessStkCallback;
use App\Models\MpesaRawCallback;
use App\Models\Payment;
use Botnetdobbs\Mpesa\Contracts\CallbackProcessor;
use Botnetdobbs\Mpesa\Contracts\CallbackResponder;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    public function __construct(
        private readonly CallbackProcessor $processor,
        private readonly CallbackResponder $responder
    ) {}

    public function handleStkCallback(Request $request): Responsable
    {
        // 1. Save the raw request body before doing anything else.
        //    If something breaks after you respond, this is how you recover.
        $raw = MpesaRawCallback::create([
            'type' => 'stk',
            'payload' => $request->getContent(),
        ]);

        try {
            $result = $this->processor->handle($request);

            $data = $result->getData();
            $checkoutRequestId = $data->Body->stkCallback->CheckoutRequestID ?? null;

            // 2. Only act on transactions you actually initiated.
            $payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();

            if (! $payment) {
                Log::warning('mpesa.stk_callback.unknown', [
                    'checkout_request_id' => $checkoutRequestId,
                ]);

                // Not ours. A failed security check, not a business outcome.
                return $this->responder->failed('Unknown transaction', 404);
            }

            // 3. Hand off to a background job for all database writes and
            //    business logic. Only move to 'queued' if the payment is not
            //    already final. A duplicate callback must never reset a
            //    payment that was already processed.
            if (! in_array($payment->status, ['processed', 'failed'], true)) {
                $payment->update(['status' => 'queued']);
                ProcessStkCallback::dispatch($raw->id);
            }

            if ($result->isSuccessful()) {
                Log::info('mpesa.stk.completed', [
                    'checkout_request_id' => $checkoutRequestId,
                ]);
            } else {
                // The payment did not go through. The customer may have
                // cancelled, entered the wrong PIN, or had insufficient funds.
                // Safaricom still delivered the notification, so we still
                // return success() below. The job marks the payment 'failed'.
                Log::warning('mpesa.stk.business_failure', [
                    'checkout_request_id' => $checkoutRequestId,
                    'result_code'         => $result->getResultCode(),
                    'result_description'  => $result->getResultDescription(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('mpesa.stk_callback.error', ['error' => $e->getMessage()]);

            // Use failed() only when your server could not handle the request
            // at all. For a temporary issue like the database being down,
            // return success() and replay from your saved raw callback later.
            return $this->responder->failed('Internal server error');
        }

        // Respond to Safaricom. All further processing runs in the background.
        return $this->responder->success();
    }

    public function handleB2cResult(Request $request): Responsable
    {
        // B2C, balance, status, and reversal results sit under Result,
        // not Body.stkCallback. The pattern stays the same.
        $raw = MpesaRawCallback::create([
            'type' => 'b2c',
            'payload' => $request->getContent(),
        ]);

        ProcessB2cResult::dispatch($raw->id);

        return $this->responder->success();
    }
}
```

## The background job

The job does the real work: it checks for duplicates, pulls the values out of
`CallbackMetadata`, and moves the payment to its final state.

```php
namespace App\Jobs;

use App\Models\MpesaRawCallback;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessStkCallback implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $rawCallbackId)
    {
    }

    public function handle(): void
    {
        $raw = MpesaRawCallback::findOrFail($this->rawCallbackId);
        $callback = data_get(json_decode($raw->payload, true), 'Body.stkCallback', []);

        // lockForUpdate() only holds the lock inside a transaction. The lock
        // stops two queue workers from processing the same payment at once.
        DB::transaction(function () use ($callback) {
            $payment = Payment::where('checkout_request_id', $callback['CheckoutRequestID'] ?? null)
                ->lockForUpdate()
                ->first();

            // Idempotency: Safaricom can deliver the same callback more than
            // once. Skip anything already in a final state.
            if (! $payment || in_array($payment->status, ['processed', 'failed'], true)) {
                return;
            }

            if (($callback['ResultCode'] ?? 1) === 0) {
                // CallbackMetadata.Item is a list of {Name, Value} pairs.
                // Flatten it into a simple key-value map.
                $items = collect(data_get($callback, 'CallbackMetadata.Item', []))
                    ->pluck('Value', 'Name');

                $payment->update([
                    'status'        => 'processed',
                    'mpesa_receipt' => $items['MpesaReceiptNumber'] ?? null,
                    'amount_paid'   => $items['Amount'] ?? null,
                    'paid_at'       => now(),
                ]);

                // Fulfill the order here. Prefer dispatching an event or
                // another job so side effects run after this commit.
            } else {
                $payment->update([
                    'status'         => 'failed',
                    'failure_reason' => $callback['ResultDesc'] ?? null,
                ]);
            }
        });
    }
}
```

Give the payments table an index on `checkout_request_id`. Every callback and
every status query looks a payment up by it.

## Reading the result

`CallbackProcessor::handle()` returns a `TransactionResult`:

```php
$result->isSuccessful();           // true when the result code is 0
$result->getResultCode();          // raw M-Pesa ResultCode
$result->getResultDescription();
$result->getData();                // the full payload as an object
```

The payload shape differs per API. For STK the details sit under
`Body.stkCallback`, for B2C and others under `Result`. Use `getData()` to reach
the fields you need. See each API's page for its exact payload.

## Responding to M-Pesa

M-Pesa reads the `ResultCode` in your JSON body, not just the HTTP status.

| Method | `ResultCode` | HTTP Status |
|---|---|---|
| `$responder->success($message)` | `0` | 200 |
| `$responder->failed($message, $statusCode)` | `1` | `$statusCode` (default 500) |

```php
return $this->responder->success();                   // {"ResultCode": 0, "ResultDesc": "Success"}
return $this->responder->success('Received');          // custom message
return $this->responder->failed();                     // {"ResultCode": 1, "ResultDesc": "Failed"}
return $this->responder->failed('Server error', 503);  // custom message and status
```

Respond with `success()` even when the payment itself failed. A wrong PIN or a
cancelled prompt is not your server failing. It is Safaricom reporting the
outcome. Use `failed()` only when your server could not handle the request, for
example a broken payload or a failed security check.

## Payment state lifecycle

Track each payment through clear, explicit states. Always set a status rather
than guessing it from missing records.

| State | Meaning |
|---|---|
| `received` | Callback saved to the database, not yet processed |
| `queued` | Handed off to a background job |
| `processed` | Payment confirmed and order fulfilled |
| `failed` | M-Pesa reported a failure (wrong PIN, insufficient funds, cancelled) |
| `reconciled` | Status confirmed later via `transactionStatus()` after a timeout or mismatch |

## Production rules

1. **Save the raw payload before doing anything else.** If something breaks
   after you respond to Safaricom, the saved copy is how you recover. Store
   `$request->getContent()` or `$request->all()` as-is.
2. **Respond fast.** M-Pesa expects a quick acknowledgement. Validation
   requests time out in about 8 seconds.
3. **Be idempotent.** M-Pesa may deliver the same callback more than once.
   Key your processing on `CheckoutRequestID`, `TransactionID`, or
   `ConversationID` so duplicates are harmless.
4. **No retries.** If M-Pesa cannot reach your URL, it does not retry. Use
   [Transaction Status](transaction-status.md) or `stkQuery()` to recover
   missed results.

## Verify the callback source (IP whitelisting)

Your callback routes are public endpoints. Anyone who finds the URL can send a
fake request to it. Safaricom only sends callbacks from a small, fixed set of
IP addresses, published in the
[Getting Started guide](https://developer.safaricom.co.ke/apis/GettingStarted).

Put the list in `MPESA_ALLOWED_CALLBACK_IPS` (comma-separated) and check it in
middleware:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMpesaIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->ip() || ! in_array($request->ip(), config('mpesa.allowed_callback_ips', []), true)) {
            // Not from Safaricom. This is a failed security check,
            // not a business outcome, so return failed() rather than success().
            return app(\Botnetdobbs\Mpesa\Contracts\CallbackResponder::class)
                ->failed('Forbidden')
                ->toResponse($request);
        }

        return $next($request);
    }
}
```

Apply it only to your callback routes. Outbound calls like `stkPush()` do not
need it. They are your app calling Safaricom, secured by the OAuth token.

The package provides the `allowed_callback_ips` config key but does not enforce
it. The middleware is yours to register.

Also verify against your own records. Only act on callbacks that match a
transaction you actually initiated, and confirm high-value payments with
`stkQuery()` or `transactionStatus()` before releasing goods. Avoid logging
full payloads in production without redaction. They contain phone numbers and
account references.

---

[← Transaction Reversal](reversal.md) &nbsp;&nbsp; [Responses and Errors →](responses-and-errors.md)
