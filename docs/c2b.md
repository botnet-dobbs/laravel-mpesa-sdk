# C2B (Customer to Business)

---

C2B notifies your app when customers pay your Paybill or Till number directly,
for example through the M-PESA app, USSD, or SIM Toolkit. You register two URLs
once, and M-Pesa posts payment details to them.

- **Confirmation URL**: receives every completed payment.
- **Validation URL**: lets you accept or reject a payment before it completes,
  for example to check an account number. Optional. Safaricom must enable
  external validation for your shortcode first (email apisupport@safaricom.co.ke).

## Register your URLs

```php
$baseUrl = config('mpesa.callbacks.base_url');

$response = $mpesaClient->c2bRegister([
    'ShortCode' => config('mpesa.business.short_codes.paybill'),
    'ResponseType' => 'Completed',
    'ConfirmationURL' => $baseUrl . '/api/mpesa/c2b/confirmation',
    'ValidationURL' => $baseUrl . '/api/mpesa/c2b/validation',
]);
```

The shortcode and base URL come from `config/mpesa.php`. See
[Configuration](configuration.md#shortcodes-and-callback-urls).

### Fields

| Field | Required | Description |
|---|---|---|
| `ShortCode` | Yes | Your Paybill or Till number. |
| `ResponseType` | Yes | `Completed` or `Cancelled`. What M-Pesa does when your validation URL is unreachable. `Completed` accepts the payment, `Cancelled` rejects it. Must be spelled exactly like this. |
| `ConfirmationURL` | Yes | Receives completed payment notifications. |
| `ValidationURL` | Yes | Receives validation requests when external validation is enabled. |

Rules for the URLs:

- Production URLs must be HTTPS and publicly reachable.
- In production, registration is a one-time call. To change URLs, delete them
  under URL Management on the M-PESA portal, then register again.
- In sandbox you can register as many times as you want.
- Do not use tunneling tools like ngrok in production.
- Avoid words like `mpesa`, `safaricom`, `exe`, `cmd`, or `sql` in the URLs.

## Simulate a payment (sandbox only)

```php
$response = $mpesaClient->c2bSimulate([
    'ShortCode' => config('mpesa.business.short_codes.paybill'),
    'CommandID' => 'CustomerPayBillOnline',
    'Amount' => 100,
    'Msisdn' => '254708374149',   // Safaricom sandbox test number
    'BillRefNumber' => 'INV-001',
]);
```

Use `CustomerPayBillOnline` for Paybill and `CustomerBuyGoodsOnline` for Till.
`BillRefNumber` is the account number for Paybill payments. Simulation does not
work in production. Real customers pay through the M-PESA app or USSD.

## The confirmation payload

M-Pesa posts payment details to your confirmation URL:

```json
{
  "TransactionType": "Pay Bill",
  "TransID": "RKL51ZDR4F",
  "TransTime": "20231121121325",
  "TransAmount": "5.00",
  "BusinessShortCode": "600966",
  "BillRefNumber": "INV-001",
  "OrgAccountBalance": "25.00",
  "MSISDN": "2547 ***** 126",
  "FirstName": "NICHOLAS"
}
```

`TransID` is the M-PESA receipt number. `MSISDN` is masked. `BillRefNumber` is
the account number the customer entered (Paybill only).

## Responding to a validation request

If you use external validation, reply within about 8 seconds.

Accept the payment:

```json
{ "ResultCode": "0", "ResultDesc": "Accepted" }
```

Reject the payment:

```json
{ "ResultCode": "C2B00011", "ResultDesc": "Rejected" }
```

Common rejection codes: `C2B00011` (invalid MSISDN), `C2B00012` (invalid account
number), `C2B00013` (invalid amount), `C2B00016` (other error).

## Endpoint version note

This package ships with the `/mpesa/c2b/v1/` endpoints. The current Daraja docs
list `/mpesa/c2b/v2/` for register and simulate. Both are in service. If you
need v2, override the endpoints in your config. See
[Configuration](configuration.md).

---

[← B2B Express Checkout](b2b-express-checkout.md) &nbsp;&nbsp; [Account Balance →](account-balance.md)
