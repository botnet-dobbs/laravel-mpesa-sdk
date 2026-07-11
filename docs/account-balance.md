# Account Balance

---

Checks the balances of your shortcode's accounts. The result arrives on your
`ResultURL`, not in the direct response.

Needs the initiator credentials. The package adds `SecurityCredential` for you.
See [Configuration](configuration.md).

## Query your balance

```php
$baseUrl = config('mpesa.callbacks.base_url');

$response = $mpesaClient->accountBalance([
    'Initiator' => config('mpesa.initiator.name'),
    'CommandID' => 'AccountBalance',
    'PartyA' => config('mpesa.business.short_codes.default'),
    'IdentifierType' => 4,
    'Remarks' => 'Daily reconciliation',
    'QueueTimeOutURL' => $baseUrl . '/api/mpesa/balance/timeout',
    'ResultURL' => $baseUrl . '/api/mpesa/balance/result',
]);
```

You can add the balance paths under `callbacks.paths` in `config/mpesa.php` and
read them from config like the other examples. See
[Configuration](configuration.md#shortcodes-and-callback-urls).

### Fields

| Field | Required | Description |
|---|---|---|
| `Initiator` | Yes | API operator username. |
| `CommandID` | Yes | Always `AccountBalance`. |
| `PartyA` | Yes | The shortcode to check. |
| `IdentifierType` | Yes | `4` for an organization shortcode. |
| `ResultURL` | Yes | Receives the balance result. |
| `QueueTimeOutURL` | Yes | Receives a notice if the request times out in the queue. |
| `Remarks` | Yes (per Daraja docs) | Short note. |

## The result callback

The balance arrives as one string under `ResultParameters`, with accounts
separated by `&`:

```text
Working Account|KES|700000.00|700000.00|0.00|0.00&
Utility Account|KES|228037.00|228037.00|0.00|0.00&
Charges Paid Account|KES|-1540.00|-1540.00|0.00|0.00
```

Each account has the format:

```text
Name|Currency|Current|Available|Reserved|Uncleared
```

## What the accounts mean

| Account | Purpose |
|---|---|
| Utility | Receives customer payments. In B2C it holds the funds you disburse. |
| Working (MMF) | Holds money waiting for settlement to your bank or M-PESA account. |
| Charges Paid | Tracks the fees charged on your tariff. |
| Organization Settlement | Money passes through here to the working account after charges. |

---

[← C2B (Customer to Business)](c2b.md) &nbsp;&nbsp; [Transaction Status →](transaction-status.md)
