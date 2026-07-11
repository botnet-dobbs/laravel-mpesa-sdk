# STK Push (M-Pesa Express)

---

STK Push sends a payment prompt to a customer's phone. The customer enters their
M-PESA PIN to approve. This is the most common way to collect payments.

The flow is asynchronous:

1. You call `stkPush()`. Daraja replies with an acknowledgement.
2. The customer sees the prompt and enters their PIN (or cancels).
3. M-Pesa posts the final result to your `CallBackURL`.

## Initiate a payment

```php
$callbackUrl = config('mpesa.callbacks.base_url') . config('mpesa.callbacks.paths.stk_push.result');

$response = $mpesaClient->stkPush([
    'BusinessShortCode' => config('mpesa.business.short_codes.paybill'),
    'Amount' => 100,
    'PhoneNumber' => '254722000000',
    'CallBackURL' => $callbackUrl,
    'AccountReference' => 'INV-001',
]);
```

The shortcode and callback URL come from `config/mpesa.php`, so the same code
works in sandbox and live. See [Configuration](configuration.md#shortcodes-and-callback-urls).

### Fields

| Field | Required | Description |
|---|---|---|
| `BusinessShortCode` | Yes | Your Paybill or store number (5 to 6 digits). |
| `Amount` | Yes | Whole number amount to charge. |
| `PhoneNumber` | Yes | Customer number in the format `2547XXXXXXXX`. Receives the PIN prompt. |
| `CallBackURL` | Yes | HTTPS URL that receives the final result. |
| `AccountReference` | Yes | Shown to the customer in the prompt. Up to 12 characters. |
| `TransactionType` | No | `CustomerPayBillOnline` (default) for Paybill, `CustomerBuyGoodsOnline` for Till. |
| `TransactionDesc` | No | Extra note, up to 13 characters. Defaults to `Payment`. |
| `PartyB` | No | The receiving shortcode. Defaults to `BusinessShortCode`. For Buy Goods, set it to your till number (`config('mpesa.business.short_codes.till')`) when it differs from your store number. |

### What the package does for you

Daraja requires a `Password` field built as `base64(Shortcode + Passkey + Timestamp)`
and a matching `Timestamp` field. The package generates both from one shared
timestamp so they always match. If you pass `Password` or `Timestamp` yourself,
they are ignored.

### Acknowledgement response

```php
$response->isSuccessful();          // true when ResponseCode is 0
$data = $response->getData();
$data->CheckoutRequestID;           // save this to match the callback later
$data->MerchantRequestID;
$data->CustomerMessage;
```

A successful acknowledgement only means Daraja accepted the request. The payment
itself completes (or fails) later, on your callback URL.

## The callback

M-Pesa posts JSON to your `CallBackURL` when the customer completes or cancels.

Successful payment:

```json
{
  "Body": {
    "stkCallback": {
      "MerchantRequestID": "29115-34620561-1",
      "CheckoutRequestID": "ws_CO_191220191020363925",
      "ResultCode": 0,
      "ResultDesc": "The service request is processed successfully.",
      "CallbackMetadata": {
        "Item": [
          { "Name": "Amount", "Value": 100.0 },
          { "Name": "MpesaReceiptNumber", "Value": "NLJ7RT61SV" },
          { "Name": "TransactionDate", "Value": 20191219102115 },
          { "Name": "PhoneNumber", "Value": 254708374149 }
        ]
      }
    }
  }
}
```

Cancelled or failed payment:

```json
{
  "Body": {
    "stkCallback": {
      "MerchantRequestID": "f1e2-4b95-a71d-b30d3cdbb7a7942864",
      "CheckoutRequestID": "ws_CO_21072024125243250722943992",
      "ResultCode": 1032,
      "ResultDesc": "Request cancelled by user"
    }
  }
}
```

`ResultCode` 0 means the customer paid. Any other code means the payment did not
happen. `CallbackMetadata` only appears on success. See [Callbacks](callbacks.md)
for how to receive and respond to these.

## Query a payment status

Use this when a callback is late or lost.

```php
$response = $mpesaClient->stkQuery([
    'BusinessShortCode' => '174379',
    'CheckoutRequestID' => 'ws_CO_260520211133524545',
]);

$response->getResultCode();         // 0 means the payment completed
$response->getResultDescription();
```

## Common result codes

| Code | Meaning |
|---|---|
| 0 | Success. The customer paid. |
| 1032 | The customer cancelled the prompt. |
| 1037 | Timeout. The customer did not respond. |
| 1 | Insufficient funds. |

## Tips

- Wait at least 1 minute before retrying a push to the same number. Daraja
  rejects a new prompt while one is still open ("unable to lock subscriber").
- A "Wrong credentials" error usually means your passkey, shortcode, or the
  encoded password do not match. Check `MPESA_LIPA_NA_MPESA_PASSKEY`.
- Store `CheckoutRequestID` when you initiate. It is the key that links the
  callback back to your order.

---

[← Configuration](configuration.md) &nbsp;&nbsp; [B2C Payments →](b2c.md)
