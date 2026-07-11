# Transaction Status

Looks up the state of a past transaction. Works for C2B, B2B, B2C, reversals,
and IMT. Use it when a callback never arrived or you need to confirm whether a
payment completed.

The result arrives on your `ResultURL`, not in the direct response.

Needs the initiator credentials. The package adds `SecurityCredential` for you.
See [Configuration](configuration.md).

## Query a transaction

```php
$baseUrl = config('mpesa.callbacks.base_url');

$response = $mpesaClient->transactionStatus([
    'Initiator' => config('mpesa.initiator.name'),
    'CommandID' => 'TransactionStatusQuery',
    'TransactionID' => 'NEF61H8J60',
    'PartyA' => config('mpesa.business.short_codes.default'),
    'IdentifierType' => 4,
    'Remarks' => 'Checking delayed payment',
    'ResultURL' => $baseUrl . '/api/mpesa/status/result',
    'QueueTimeOutURL' => $baseUrl . '/api/mpesa/status/timeout',
]);
```

The initiator name and shortcode come from `config/mpesa.php`. See
[Configuration](configuration.md#shortcodes-and-callback-urls).

### Fields

| Field | Required | Description |
|---|---|---|
| `Initiator` | Yes | API operator username. |
| `CommandID` | Yes | Always `TransactionStatusQuery`. |
| `TransactionID` | Yes | The M-PESA receipt number, for example `NEF61H8J60`. |
| `PartyA` | Yes | Your shortcode. |
| `IdentifierType` | Yes | `4` for an organization shortcode. |
| `ResultURL` | Yes | Receives the status result. |
| `QueueTimeOutURL` | Yes | Receives a notice if the request times out in the queue. |
| `Remarks` | Yes (per Daraja docs) | Short note. |
| `Occasion` | No | Extra note. |

Daraja also supports querying by `OriginalConversationID` instead of
`TransactionID` when you never received a receipt number. This package requires
`TransactionID`, so keep receipt numbers from your callbacks.

## The result callback

The result includes `ResultParameters` with keys such as:

| Key | Meaning |
|---|---|
| `TransactionStatus` | For example `Completed`. |
| `Amount` | Transaction amount. |
| `ReceiptNo` | M-PESA receipt number. |
| `InitiatedTime` / `FinalisedTime` | Timestamps in `YYYYMMDDHHmmss` format. |
| `DebitPartyName` / `CreditPartyName` | The parties involved. |
| `DebitAccountType` | Which account was debited. |
| `ReasonType` | What kind of transaction it was. |

`ResultCode` 0 on the callback means the query itself worked. Check
`TransactionStatus` inside `ResultParameters` for the state of the original
transaction.
