# Laravel M-Pesa Integration Package

Laravel package for integrating with Safaricom"s M-Pesa payment gateway. Supports STK Push, B2C, B2B, balance queries, transaction status checks, and payment reversals.

For the most current API documentation and updates, always refer to the [Safaricom Developer Portal](https://developer.safaricom.co.ke/APIs).

## Requirements

- PHP 8.2+
- Laravel 11.x
- Composer 2.x

## Installation

Install the package via Composer:

```bash
composer require botnet-dobbs/mpesa
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

### STK Push (Lipa Na M-Pesa Online)

```php
use Botnetdobbs\Mpesa\Facades\Mpesa;

$response = Mpesa::stkPush([
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
use Botnetdobbs\Mpesa\Facades\Mpesa;

$response = Mpesa::stkQuery([
    "BusinessShortCode" => "174379",
    "Passkey" => "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919",
    "CheckoutRequestID" => "ws_CO_260520211133524545"
]);
```

### B2C Payment (Business to Customer)

```php
use Botnetdobbs\Mpesa\Facades\Mpesa;

$response = Mpesa::b2c([
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
use Botnetdobbs\Mpesa\Facades\Mpesa;

$response = Mpesa::b2b([
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
use Botnetdobbs\Mpesa\Facades\Mpesa;

$response = Mpesa::c2bRegister([
    "ShortCode" => "600000",
    "ResponseType" => "Completed",      // Or "Cancelled"
    "ConfirmationURL" => "https://example.com/confirmation",    // The URL that receives the confirmation request from API upon payment completion.
    "ValidationURL" => "https://example.com/validation",    // The URL that receives the validation request from the API upon payment submission. The validation URL is only called if the external validation on the registered shortcode is enabled. (By default External Validation is disabled).
]);
```

### C2B Simulate Payment (Sandbox Environment Only)

```php
use Botnetdobbs\Mpesa\Facades\Mpesa;

$response = Mpesa::c2bSimulate([
    "ShortCode" => "600000",
    "CommandID" => "CustomerPayBillOnline",  // Or "CustomerBuyGoodsOnline"
    "Amount" => 100,
    "Msisdn" => "254722000000",             // Customer phone number
    "BillRefNumber" => "INV001"             // Optional reference
]);
```

### Account Balance Query

```php
use Botnetdobbs\Mpesa\Facades\Mpesa;

$response = Mpesa::accountBalance([
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
use Botnetdobbs\Mpesa\Facades\Mpesa;

$response = Mpesa::transactionStatus([
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
use Botnetdobbs\Mpesa\Facades\Mpesa;

$response = Mpesa::reversal([
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
$response = Mpesa::stkPush([...]);

// Access response properties
echo $response->MerchantRequestID;
echo $response->CheckoutRequestID;
echo $response->ResponseDescription;
```

## Error Handling

The package throws `MpesaException` for various error scenarios:

```php
use Botnetdobbs\Mpesa\Facades\Mpesa;
use Botnetdobbs\Mpesa\Exceptions\MpesaException;
try {
    $response = Mpesa::stkPush([...]);
} catch (MpesaException $e) {
    // Handle the error
    logger()->error("M-Pesa error: " . $e->getMessage());
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