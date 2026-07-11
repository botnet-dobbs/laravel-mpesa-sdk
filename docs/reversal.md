# Transaction Reversal

---

Reverses a completed M-PESA transaction, for example a customer payment made in
error. The result arrives on your `ResultURL`, not in the direct response.

Needs the initiator credentials, and the API operator must have the
Org Reversals Initiator role on the M-PESA portal. The package adds
`SecurityCredential` for you. See [Configuration](configuration.md).

## Important: the misspelled field

Safaricom's wire format spells the identifier field `RecieverIdentifierType`
(missing an "i"). Daraja only accepts this exact spelling. Sending the correct
English spelling causes a "Bad Request - Invalid RecieverIdentifierType" error.
Per the Daraja docs, its value should be `"11"`, meaning organization.

## Reverse a transaction

```php
$baseUrl = config('mpesa.callbacks.base_url');

$response = $mpesaClient->reversal([
    'Initiator' => config('mpesa.initiator.name'),
    'CommandID' => 'TransactionReversal',
    'TransactionID' => 'OEI2AK4Q16',
    'Amount' => 100,
    'ReceiverParty' => config('mpesa.business.short_codes.default'),
    'RecieverIdentifierType' => '11',
    'ResultURL' => $baseUrl . '/api/mpesa/reversal/result',
    'QueueTimeOutURL' => $baseUrl . '/api/mpesa/reversal/timeout',
    'Remarks' => 'Customer paid twice',
]);
```

The initiator name and shortcode come from `config/mpesa.php`. See
[Configuration](configuration.md#shortcodes-and-callback-urls).

### Fields

| Field | Required | Description |
|---|---|---|
| `Initiator` | Yes | API operator username with the reversals role. |
| `CommandID` | Yes | Always `TransactionReversal`. |
| `TransactionID` | Yes | The M-PESA receipt number of the transaction to reverse. |
| `Amount` | Yes | The transaction amount. |
| `ReceiverParty` | Yes | Your organization shortcode. |
| `RecieverIdentifierType` | Yes | `"11"`. Note the Safaricom spelling. |
| `ResultURL` | Yes | Receives the reversal result. |
| `QueueTimeOutURL` | Yes | Receives a notice if the request times out in the queue. |
| `Remarks` | Yes | Note between 2 and 100 characters. |
| `Occasion` | No | Extra note. |

## The result callback

The reversal is asynchronous. The acknowledgement only confirms Daraja received
the request. The actual outcome posts to your `ResultURL`. A successful reversal
has `ResultCode` 0.

## Limits and common errors

- B2C payments cannot be reversed through this API. Reverse them manually on
  the M-PESA portal.
- "The initiator information is invalid": check the API user's username,
  password, and that the password was encrypted with the right certificate.
- "The security credential is locked": too many wrong attempts. Unlock the
  operator through your Business Administrator.
- No callback arriving: make sure your `ResultURL` is public and reachable.
  M-Pesa does not retry failed callbacks.

---

[← Transaction Status](transaction-status.md) &nbsp;&nbsp; [Callbacks →](callbacks.md)
