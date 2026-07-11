# B2C Payments (Business to Customer)

B2C sends money from your business account to a customer's M-PESA wallet.
Common uses: salaries, refunds, loan disbursements, winnings, and cashback.

The flow is asynchronous:

1. You call `b2c()`. Daraja replies with an acknowledgement.
2. M-Pesa processes the payment.
3. M-Pesa posts the final result to your `ResultURL`.

## Requirements

B2C needs an initiator and a security credential. Set `MPESA_INITIATOR_NAME`,
`MPESA_INITIATOR_PASSWORD`, and `MPESA_CERTIFICATE_PATH`. The package encrypts
the password and adds `SecurityCredential` to the request for you.
See [Configuration](configuration.md).

For production you need a Bulk Disbursement Account or a shortcode that can both
receive and disburse funds.

## Send a payment

```php
$baseUrl = config('mpesa.callbacks.base_url');

$response = $mpesaClient->b2c([
    'OriginatorConversationID' => 'order-8921-payout-1',
    'InitiatorName' => config('mpesa.initiator.name'),
    'CommandID' => 'BusinessPayment',
    'Amount' => 100,
    'PartyA' => config('mpesa.business.short_codes.default'),
    'PartyB' => '254722000000',
    'Remarks' => 'Refund for order 8921',
    'QueueTimeOutURL' => $baseUrl . config('mpesa.callbacks.paths.b2c.timeout'),
    'ResultURL' => $baseUrl . config('mpesa.callbacks.paths.b2c.result'),
]);
```

The initiator name, shortcode, and callback URLs come from `config/mpesa.php`.
See [Configuration](configuration.md#shortcodes-and-callback-urls).

### Fields

| Field | Required | Description |
|---|---|---|
| `OriginatorConversationID` | Yes | Unique ID you generate per request. Daraja uses it to block double disbursement, so never reuse one. |
| `InitiatorName` | Yes | API operator username from the M-PESA portal. |
| `CommandID` | Yes | `BusinessPayment`, `SalaryPayment`, or `PromotionPayment`. |
| `Amount` | Yes | Amount to send. |
| `PartyA` | Yes | Your B2C shortcode. |
| `PartyB` | Yes | Customer number, 12 digits, for example `254722000000`. |
| `ResultURL` | Yes | Receives the final result. |
| `QueueTimeOutURL` | Yes | Receives a notice if the request times out in the queue. |
| `Remarks` | Yes (per Daraja docs) | Note between 2 and 100 characters. |
| `Occasion` | No | Extra note up to 100 characters. Safaricom's own docs spell it `Occassion` in some samples. |

### Command IDs

- `BusinessPayment`: normal payment to a registered M-PESA customer.
- `SalaryPayment`: salary payout.
- `PromotionPayment`: promotional payout. The customer gets a congratulatory message.

## Acknowledgement response

```php
$response->isSuccessful();   // true when ResponseCode is 0
$data = $response->getData();
$data->ConversationID;
$data->OriginatorConversationID;
```

## The result callback

M-Pesa posts the outcome to your `ResultURL`. `ResultCode` 0 means the money was
sent. On success, `ResultParameters` includes useful values:

| Key | Meaning |
|---|---|
| `TransactionAmount` | Amount sent. |
| `TransactionReceipt` | M-PESA receipt number. |
| `ReceiverPartyPublicName` | Customer number and name. |
| `TransactionCompletedDateTime` | When the payment completed. |
| `B2CUtilityAccountAvailableFunds` | Your utility account balance after the payment. |
| `B2CWorkingAccountAvailableFunds` | Your working account balance. |
| `B2CRecipientIsRegisteredCustomer` | `Y` or `N`. |

A failed result has a non-zero `ResultCode` and no `ResultParameters`. A common
one is `2001`, "The initiator information is invalid". That points at your
initiator name, password, or certificate.

## Tips

- B2C payments cannot be reversed through the Reversal API. Reversals for B2C
  are done manually on the M-PESA portal.
- Queue B2C calls in a job when paying many people. The API is rate limited.
- Use `transactionStatus()` with your `OriginatorConversationID` if a result
  callback never arrives. See [Transaction Status](transaction-status.md).
