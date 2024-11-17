# Laravel M-Pesa Integration Package

Laravel package for integrating with Safaricom"s M-Pesa payment gateway. Supports STK Push, B2C, B2B, balance queries, transaction status checks, and payment reversals.

For the most current API documentation and updates, always refer to the [Safaricom Developer Portal](https://developer.safaricom.co.ke/APIs).

## Requirements

- PHP 8.2+

| Laravel Version  |
|------------------|
| Laravel 10.x     |
| Laravel 11.x     |

## Installation

Install the package via Composer:

```bash
composer require botnetdobbs/laravel-mpesa-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=mpesa-config
```

## Configuration

Add the following variables to your `.env` file:

```env
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_ENV=sandbox  # or "live" for production
```

### Configuration Options

The published config file (`config/mpesa.php`) contains the following options:

```php
return [
    "consumer_key" => env("MPESA_CONSUMER_KEY"),
    "consumer_secret" => env("MPESA_CONSUMER_SECRET"),
    "environment" => env("MPESA_ENV", "sandbox"),
    
    "defaults" => [
        "timeout" => 30,
        "connect_timeout" => 10,
    ]
];
```

## Usage

```php
use Botnetdobbs\Mpesa\Contracts\Client;

class PaymentController extends Controller
{
    public function __construct(
        private readonly Client $mpesaClient
    ) {}

    public function initiatePayment()
    {
        $response = $this->mpesaClient->stkPush([...]);
    }
}

```

### STK Push (Lipa Na M-Pesa Online)

```php

$response = $this->mpesaClient->stkPush([
    "BusinessShortCode" => "174379",    // Organization's shortcode  (Paybill or Buygoods - A 5 to 6-digit account number) used to identify an organization and receive the transaction.
    "Passkey" => "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919",
    "TransactionType" => "CustomerPayBillOnline",    // or CustomerBuyGoodsOnline
    "Amount" => 1,
    "PartyA" => "254722000000",  // Customer phone number
    "PhoneNumber" => "254722000000", // The Mobile Number to receive the STK Pin Prompt. PartyA
    "CallBackURL" => "https://example.com/callback",    // Valid secure URL that is used to receive notifications from M-Pesa API.
    "AccountReference" => "Test",
    "TransactionDesc" => "Test Payment"
]);
```

### STK Push (check the status of a Lipa Na M-Pesa Online Payment.)

```php

$response = $this->mpesaClient->stkQuery([
    "BusinessShortCode" => "174379",
    "Passkey" => "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919",
    "CheckoutRequestID" => "ws_CO_260520211133524545"
]);
```

### B2C Payment (Business to Customer)

```php

$response = $this->mpesaClient->b2c([
    "OriginatorConversationID" => "unique-id",
    "InitiatorName" => "testapi",
    "SecurityCredential" => "your-security-credential", // Base64 Encode(OpenSSLEncrypt(Initiator Password + Certificate))
    "CommandID" => "BusinessPayment",  // Or "SalaryPayment", "PromotionPayment"
    "Amount" => 100,
    "PartyA" => "600000",      // Your business shortcode
    "PartyB" => "254722000000", // Customer phone number
    "Remarks" => "Test payment",
    "QueueTimeOutURL" => "https://example.com/queue-timeout",   // The URL to be specified in your request that will be used by API Proxy to send notification incase the payment request is timed out while awaiting processing in the queue.
    "ResultURL" => "https://example.com/result",    // The URL to be specified in your request that will be used by M-PESA to send notification upon processing of the payment request.
    "Occasion" => "Test"
]);
```

### B2B Payment (Business to Business)

B2B parameter naming convention is camelCase instead of PascalCase like the other endpoints on the [Safaricom Developer Portal](https://developer.safaricom.co.ke/APIs/B2BExpressCheckout). Retained as is

```php

$response = $this->mpesaClient->b2b([
    "primaryShortCode" => "000001",    // Sender business shortcode
    "receiverShortCode" => "000002",   // Receiver business shortcode
    "amount" => 100,
    "paymentRef" => "INV001",          // Your reference
    "callbackUrl" => "https://example.com/callback",
    "partnerName" => "Vendor Name",
    "RequestRefID" => "unique-id-123"   // Unique identifier for the request
]);
```

### C2B Register (Customer to Business)
```php

$response = $this->mpesaClient->c2bRegister([
    "ShortCode" => "600000",
    "ResponseType" => "Completed",      // Or "Cancelled"
    "ConfirmationURL" => "https://example.com/confirmation",    // The URL that receives the confirmation request from API upon payment completion.
    "ValidationURL" => "https://example.com/validation",    // The URL that receives the validation request from the API upon payment submission. The validation URL is only called if the external validation on the registered shortcode is enabled. (By default External Validation is disabled).
]);
```

### C2B Simulate Payment (Sandbox Environment Only)

```php

$response = $this->mpesaClient->c2bSimulate([
    "ShortCode" => "600000",
    "CommandID" => "CustomerPayBillOnline",  // Or "CustomerBuyGoodsOnline"
    "Amount" => 100,
    "Msisdn" => "254722000000",             // Customer phone number
    "BillRefNumber" => "INV001"             // Optional reference
]);
```

### Account Balance Query

```php

$response = $this->mpesaClient->accountBalance([
    "Initiator" => "testapi",   // The credential/username used to authenticate the transaction request
    "SecurityCredential" => "your-security-credential", // Base64 encoded string of the M-PESA short code and password, which is encrypted using M-PESA public key and validates the transaction on M-PESA Core system. It indicates the Encrypted credential of the initiator getting the account balance. Its value must match the inputted value of the parameter IdentifierType.
    "CommandID" => "AccountBalance",
    "PartyA" => "600000",              // Your business shortcode
    "IdentifierType" => "4",           // 4 for organization shortcode
    "Remarks" => "Balance query",
    "QueueTimeOutURL" => "https://example.com/timeout", // The end-point that receives a timeout message.
    "ResultURL" => "https://example.com/result",    // It indicates the destination URL which Daraja should send the result message to.
]);
```

### Transaction Status Query

```php

$response = $this->mpesaClient->transactionStatus([
    "Initiator" => "testapi",
    "SecurityCredential" => "your-security-credential",
    "CommandID" => "TransactionStatusQuery",
    "TransactionID" => "OEI2AK4Q16",    // The M-Pesa transaction ID
    "PartyA" => "600000",               // Your business shortcode
    "IdentifierType" => "4",            // 4 for organization shortcode
    "ResultURL" => "https://example.com/result",
    "QueueTimeOutURL" => "https://example.com/timeout",
    "Remarks" => "Status check",
    "Occasion" => "Transaction query",  // Optional parameter
]);
```

### Transaction Reversal

```php

$response = $this->mpesaClient->reversal([
    "Initiator" => "testapi",
    "SecurityCredential" => "your-security-credential",
    "CommandID" => "TransactionReversal",
    "TransactionID" => "OEI2AK4Q16",     // The M-Pesa transaction ID to reverse
    "Amount" => 100,                      // Amount to reverse
    "ReceiverParty" => "600000",         // Organization receiving the reversal
    "RecieverIdentifierType" => "4",      // 4 for organization shortcode
    "ResultURL" => "https://example.com/result",
    "QueueTimeOutURL" => "https://example.com/timeout",
    "Remarks" => "Reversal request",
    "Occasion" => "Transaction reversal"
]);
```

### Response Handling
All methods return a standard object containing the M-Pesa API response. Example success response:

```php
$response = $this->mpesaClient->stkPush([...]);

// Access response properties
echo $response->MerchantRequestID;
echo $response->CheckoutRequestID;
echo $response->ResponseDescription;
```

## Error Handling

The package throws `MpesaException` for various error scenarios:

```php
use Botnetdobbs\Mpesa\Exceptions\MpesaException;
try {
    $response = $this->mpesaClient->stkPush([...]);
} catch (MpesaException $e) {
    // Handle the error
    logger()->error("M-Pesa error: " . $e->getMessage());
}
```

## Callback Handling

The package provides callback handling system for processing M-Pesa payment notifications. 

### Setup Callback Routes

Register the callback routes in your `routes/api.php`:

```php
use App\Http\Controllers\MpesaCallbackController;

Route::prefix('mpesa/callback')->group(function () {
    Route::post('stkpush', [MpesaCallbackController::class, 'handleStkCallback']);
    Route::post('b2c', [MpesaCallbackController::class, 'handleB2cCallback']);
    Route::post('transaction-status', [MpesaCallbackController::class, 'handleTransactionStatusCallback']);
    Route::post('account-balance', [MpesaCallbackController::class, 'handleAccountBalanceCallback']);
    Route::post('reversal', [MpesaCallbackController::class, 'handleReversalCallback']);
});
```

### Create Callback Controller

Create a controller to handle M-Pesa callbacks:

```php
namespace App\Http\Controllers;

use Botnetdobbs\Mpesa\Contracts\CallbackHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    public function __construct(
        private readonly CallbackHandler $handler,
        private readonly ResponseHandler $response
    ) {}

    public function handleStkCallback(Request $request): Response
    {
        try {
            $callback = $this->handler->handleStkCallback($request);

            if ($callback->isSuccessful()) {
                // Process successful payment
                Log::info('STK Push payment successful', [
                    'receipt' => $callback->getReceiptNumber(),
                    'amount' => $callback->getAmount(),
                    'phone' => $callback->getPhoneNumber(),
                    'date' => $callback->getTransactionDate()
                ]);
                
                // Update your database, trigger events, etc.
                return $this->response->success('Payment processed');
            } 
            Log::warning('STK Push payment failed', [
                'code' => $callback->getResultCode(),
                'description' => $callback->getResultDescription()
            ]);

            return $this->response->success('Failed payment');
        } catch (\Exception $e) {
            Log::error('Error processing STK callback', [
                'error' => $e->getMessage()
            ]);
            return $this->response->failed('Internal server error');
        }
    }

    // Implement other callback handlers similarly...
}
```

### Available Callback Methods

Each callback type provides specific methods to access the payment data:

#### Common Methods Available in All Callbacks
```php
$callback->isSuccessful(): bool
$callback->getResultCode(): int
$callback->getResultType(): int         // Except STK Push
$callback->getResultDescription(): string
$callback->getConversationId(): string  // Except STK Push
$callback->getTransactionId(): string   // Except STK Push
$callback->getOriginatorConversationId(): string // Except STK Push
```

#### STK Push Callback
```php
$callback->getAmount(): ?float
$callback->getReceiptNumber(): ?string
$callback->getTransactionDate(): ?string
$callback->getPhoneNumber(): ?string
$callback->getMerchantRequestId(): string
$callback->getCheckoutRequestId(): string
```

#### B2C Callback
```php
$callback->getTransactionAmount(): ?float
$callback->getTransactionReceipt(): ?string
$callback->getB2CRecipientIsRegisteredCustomer(): ?string
$callback->getReceiverPartyPublicName(): ?string
$callback->getTransactionCompletedDateTime(): ?string
$callback->getB2CUtilityAccountAvailableFunds(): ?float
$callback->getB2CWorkingAccountAvailableFunds(): ?float
$callback->getB2CChargesPaidAccountAvailableFunds(): ?float
```

#### Account Balance Callback
```php
$callback->getAccountBalances(): array  // Returns array of all accounts with their balances
$callback->getBalanceForAccount(string $accountName): ?array  // Get balance for specific account
$callback->getCompletedTime(): ?string
```

#### Transaction Status Callback
```php
$callback->getTransactionStatus(): ?string
$callback->getAmount(): ?float
$callback->getReceiptNumber(): ?string
$callback->getDebitPartyNames(): ?array
$callback->getInitiatedTime(): ?string
$callback->getFinalisedTime(): ?string
$callback->getDebitAccountType(): ?string
$callback->getDebitPartyCharges(): array
$callback->getDebitPartyCharge(): ?array
```

#### Reversal Callback
```php
$callback->getAmount(): ?float
$callback->getOriginalTransactionID(): ?string
$callback->getDebitAccountBalances(): array
$callback->getDebitAccountBalance(string $account): array
$callback->getTransactionCompletedTime(): ?string
$callback->getCharge(): ?float
$callback->getCreditPartyPublicName(): ?string
$callback->getDebitPartyPublicName(): ?string
```

The `ResponseHandler` provides two methods:

- `success(string $message = 'Payment processed'): Responsable` - Returns a success response with ResultCode 0
- `failed(string $message = 'Internal server error', int $statusCode = 500): Responsable` - Returns a failure response with ResultCode 1

Response Format:
All responses are returned as JSON with the appropriate Content-Type header.

Success Response:
```json
{
    "ResultCode": 0,
    "ResultDesc": "Payment processed"
}
```

Failed Response:
```json
{
    "ResultCode": 1,
    "ResultDesc": "Internal server error"
}
```

## For Contributors

This package includes comprehensive testing capabilities:

### Running Tests


Run all tests
```bash
composer test
```

### Coverage Reports

Generate HTML coverage report:
```bash
composer test:coverage
```
Then open `coverage/index.html` in your browser.

## Code Quality

```bash
# Check code style
composer check-style

# Fix code style issues
composer fix-style

# Run static analysis
composer analyse
```

## Credits

- [Lazarus Odhiambo](https://github.com/botnetdobbs)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.