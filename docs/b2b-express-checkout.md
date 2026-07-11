# B2B Express Checkout (USSD Push to Till)

---

B2B Express Checkout lets you (the vendor) send a payment prompt to another
merchant. The merchant approves on their phone and pays from their till into
your Paybill.

The flow is asynchronous:

1. You call `b2b()`. Daraja replies with an acknowledgement.
2. The merchant's operator gets a USSD prompt, enters their operator ID and PIN,
   and confirms the payment.
3. M-Pesa posts the final result to your `callbackUrl`.

## Field names and credentials

This API is different from the other Daraja endpoints in two ways:

- Field names are camelCase, not PascalCase.
- It does not use `SecurityCredential`. The package does not add one.

## Send a payment prompt

```php
$response = $mpesaClient->b2b([
    'primaryShortCode' => '000001',   // the paying merchant's till, from your records
    'receiverShortCode' => config('mpesa.business.short_codes.paybill'),
    'amount' => 100,
    'paymentRef' => 'INV-001',
    'callbackUrl' => config('mpesa.callbacks.base_url') . '/api/mpesa/b2b/result',
    'partnerName' => 'Vendor Name',
    'RequestRefID' => (string) Str::uuid(),
]);
```

Your Paybill and the callback base URL come from `config/mpesa.php`. The paying
merchant's till is data, not config. See
[Configuration](configuration.md#shortcodes-and-callback-urls).

### Fields

| Field | Required | Description |
|---|---|---|
| `primaryShortCode` | Yes | The paying merchant's till number (the debit side). |
| `receiverShortCode` | Yes | Your Paybill that receives the money (the credit side). |
| `amount` | Yes | Amount to request. |
| `paymentRef` | Yes | Reference shown to the merchant in the prompt. |
| `callbackUrl` | Yes | Receives the final result. |
| `partnerName` | Yes | Your business name as the merchant knows it. Shown in the prompt. |
| `RequestRefID` | Yes | Unique ID you generate per request, for example a UUID. |

The merchant sees a prompt like: "You are about to send Ksh {amount} to
{partnerName} for payment reference: {paymentRef}."

## The acknowledgement is not a normal response

The first response looks like this:

```json
{ "code": "0", "status": "USSD Initiated Successfully" }
```

It has no `ResponseCode` field, so `$response->isSuccessful()` returns `false`
even when the push was sent. Read it directly instead:

```php
$data = $response->getData();

if (($data->code ?? null) === '0') {
    // Prompt sent. Wait for the callback.
}
```

## The result callback

M-Pesa posts the outcome to your `callbackUrl`. Note these fields are camelCase too.

Successful payment:

```json
{
  "resultCode": "0",
  "resultDesc": "The service request is processed successfully.",
  "amount": "100.0",
  "requestId": "404e1aec-19e0-4ce3-973d-bd92e94c8021",
  "conversationID": "AG_20230426_2010434680d9f5a73766",
  "transactionId": "RDQ01NFT1Q",
  "status": "SUCCESS"
}
```

Cancelled:

```json
{
  "resultCode": "4001",
  "resultDesc": "User cancelled transaction",
  "requestId": "c2a9ba32-9e11-4b90-892c-7bc54944609a",
  "amount": "100.0",
  "paymentReference": "INV-001"
}
```

`resultCode` `"0"` means the merchant paid. `transactionId` is the M-PESA
receipt number. Match callbacks to your requests using `requestId`, which is
the `RequestRefID` you sent.

---

[← B2C Payments](b2c.md) &nbsp;&nbsp; [C2B (Customer to Business) →](c2b.md)
