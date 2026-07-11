# Responses and Errors

## Reading a response

Every client method returns a response object:

```php
$response = $mpesaClient->stkPush([...]);

$response->isSuccessful();            // true when ResponseCode is 0
$response->getResponseCode();         // int
$response->getResponseDescription();  // string
$response->getResultCode();           // int, STK Query only
$response->getResultDescription();    // string, STK Query only
$response->getData();                 // the full body as an object
```

`getData()` always returns an object, even for empty bodies. Use it to reach
fields the helpers do not cover, such as `CheckoutRequestID` or
`ConversationID`.

### Missing fields

Daraja sometimes returns HTTP 200 with a body that has no `ResponseCode`, for
example the B2B Express Checkout acknowledgement. When the code field is
missing, `getResponseCode()` returns `1` and `isSuccessful()` returns `false`.
The description helpers return an empty string. Nothing throws. When in doubt,
inspect `getData()`.

## Exceptions

Two exception types can be thrown:

| Exception | When |
|---|---|
| `InvalidArgumentException` | Before the HTTP call. A required field is missing or blank, or the package is misconfigured. |
| `Botnetdobbs\Mpesa\Exceptions\MpesaException` | The HTTP call failed. Daraja returned a 4xx or 5xx status, or token generation failed. |

`MpesaException` carries context:

```php
try {
    $response = $mpesaClient->stkPush([...]);
} catch (MpesaException $e) {
    $e->getMessage();  // includes the raw response body
    $e->status;        // HTTP status code, for example 500
    $e->body;          // decoded response body when available
}
```

Log the details on the server. Return a generic message to your users. Raw
Daraja errors can leak internal information.

## Common Daraja error codes

| Code | Message | What to do |
|---|---|---|
| `400.002.02` | Bad Request, invalid field | Check the request payload against the API's field list. |
| `404.001.03` | Invalid Access Token | The package refreshes tokens automatically. If this persists, check your consumer key and secret. |
| `404.001.01` | Resource not found | Wrong endpoint path. Check your endpoint overrides. |
| `500.001.1001` | Merchant does not exist | Use the shortcode from your Go Live as `BusinessShortCode`. |
| `500.001.1001` | Wrong credentials | The STK password did not match. Check your passkey and shortcode. |
| `500.001.1001` | Unable to lock subscriber | An STK prompt is still open for that number. Wait at least a minute. |
| `500.002.1001` | Duplicate OriginatorConversationID | Generate a new unique ID for every B2C request. |
| `500.003.02` | Spike Arrest Violation | You exceeded the requests-per-second limit. Slow down or queue. |
| `500.003.03` | Quota Violation | You exceeded the request quota. Reduce call volume. |

## Validation errors

The package checks required fields before calling Daraja. Blank strings count
as missing. On failure it throws `InvalidArgumentException` with a message that
lists the missing fields:

```text
Missing required parameters: PhoneNumber and AccountReference
```

Catch it separately from `MpesaException` if you want to return a 400 to your
own users.
