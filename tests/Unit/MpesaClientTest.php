<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Contracts\Client;
use Botnetdobbs\Mpesa\Exceptions\MpesaException;
use Botnetdobbs\Mpesa\Http\MpesaAuthToken;
use Botnetdobbs\Mpesa\Http\MpesaClient;
use Botnetdobbs\Mpesa\Services\InitiatorCredentialGenerator;
use Botnetdobbs\Mpesa\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Mockery;

class MpesaClientTest extends TestCase
{
    private Client $mpesaClient;
    private static array $defaultRequestData = [
        'stkPush' => [
            'BusinessShortCode' => '174379',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
            'CallBackURL' => 'https://example.com/callback',
        ],
        'stkQuery' => [
            'BusinessShortCode' => '174379',
            'CheckoutRequestID' => 'test_checkout_id',
        ],
        'b2c' => [
            'OriginatorConversationID' => 'test_conversation_id',
            'InitiatorName' => 'test_initiator',
            'SecurityCredential' => 'test_credential',
            'CommandID' => 'BusinessPayment',
            'Amount' => 1,
            'PartyA' => '174379',
            'PartyB' => '254722188188',
            'Remarks' => 'test_remark',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'ResultURL' => 'https://example.com/result',
            'Occasion' => 'test_occasion',
        ],
        'b2b' => [
            'primaryShortCode' => '000001',
            'receiverShortCode' => '000002',
            'amount' => '100',
            'paymentRef' => 'paymentRef',
            'callbackUrl' => 'https://example.com/callback',
            'partnerName' => 'Vendor',
            'RequestRefID' => 'test_request_id',
        ],
        'c2bRegister' => [
            'ShortCode' => '174379',
            'ResponseType' => 'Completed',
            'ConfirmationURL' => 'https://example.com/confirm',
            'ValidationURL' => 'https://example.com/validate',
        ],
        'c2bSimulate' => [
            'ShortCode' => '174379',
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => 1,
            'Msisdn' => '254722188188',
            'BillRefNumber' => 'test_bill_ref',
        ],
        'accountBalance' => [
            'Initiator' => 'test_initiator',
            'SecurityCredential' => 'test_credential',
            'CommandID' => 'AccountBalance',
            'PartyA' => '174379',
            'IdentifierType' => 4,
            'Remarks' => 'test_remark',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'ResultURL' => 'https://example.com/result',
        ],
        'transactionStatus' => [
            'Initiator' => 'test_initiator',
            'SecurityCredential' => 'test_credential',
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => 'test_transaction_id',
            'PartyA' => '174379',
            'IdentifierType' => 4,
            'ResultURL' => 'https://example.com/result',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'Remarks' => 'test_remark',
        ],
        'reversal' => [
            'Initiator' => 'test_initiator',
            'SecurityCredential' => 'test_credential',
            'CommandID' => 'TransactionReversal',
            'TransactionID' => 'test_transaction_id',
            'Amount' => 1,
            'ReceiverParty' => '174379',
            'ReceiverIdentifierType' => 4,
            'ResultURL' => 'https://example.com/result',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'Remarks' => 'test_remark',
            'Occasion' => 'test_occasion',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $mockGenerator = Mockery::mock(InitiatorCredentialGenerator::class);
        $mockGenerator->shouldReceive('generate')
            ->andReturn('test_security_credential');
        $this->app->instance(InitiatorCredentialGenerator::class, $mockGenerator);

        $this->mpesaClient = $this->app->make(Client::class);
    }

    public static function successfulApiCallsProvider(): array
    {
        return [
            'STK Push' => [
                'method' => 'stkPush',
                'endpoint' => '/mpesa/stkpush/v1/processrequest',
                'requestData' => self::$defaultRequestData['stkPush'],
                'responseData' => [
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'Success. Request accepted for processing',
                ]
            ],
            'STK Query' => [
                'method' => 'stkQuery',
                'endpoint' => '/mpesa/stkpushquery/v1/query',
                'requestData' => self::$defaultRequestData['stkQuery'],
                'responseData' => [
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'The service request has been accepted successfully',
                    'MerchantRequestID' => 'test_merchant_id',
                    'CheckoutRequestID' => 'test_checkout_id',
                    'ResultCode' => '0',
                    'ResultDesc' => 'The service request is processed successfully.',
                ]
            ],
            'B2C Payment' => [
                'method' => 'b2c',
                'endpoint' => '/mpesa/b2c/v3/paymentrequest',
                'requestData' => self::$defaultRequestData['b2c'],
                'responseData' => [
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'Accept the service request successfully.',
                ]
            ],
            'B2B Payment' => [
                'method' => 'b2b',
                'endpoint' => '/mpesa/b2b/v1/paymentrequest',
                'requestData' => self::$defaultRequestData['b2b'],
                'responseData' => [
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'USSD Initiated Successfully',
                ]
            ],
            'C2B Register URL' => [
                'method' => 'c2bRegister',
                'endpoint' => '/mpesa/c2b/v1/registerurl',
                'requestData' => self::$defaultRequestData['c2bRegister'],
                'responseData' => [
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'Success',
                ]
            ],
            'C2B Simulate' => [
                'method' => 'c2bSimulate',
                'endpoint' => '/mpesa/c2b/v1/simulate',
                'requestData' => self::$defaultRequestData['c2bSimulate'],
                'responseData' => [
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'Success',
                ]
            ],
            'Account Balance' => [
                'method' => 'accountBalance',
                'endpoint' => '/mpesa/accountbalance/v1/query',
                'requestData' => self::$defaultRequestData['accountBalance'],
                'responseData' => [
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'Success',
                ]
            ],
            'Transaction Status' => [
                'method' => 'transactionStatus',
                'endpoint' => '/mpesa/transactionstatus/v1/query',
                'requestData' => self::$defaultRequestData['transactionStatus'],
                'responseData' => [
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'Accept the service request successfully.',
                ]
            ],
            'Reversal' => [
                'method' => 'reversal',
                'endpoint' => '/mpesa/reversal/v1/request',
                'requestData' => self::$defaultRequestData['reversal'],
                'responseData' => [
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'Accept the service request successfully.',
                ]
            ],
        ];
    }

    /**
     * @dataProvider successfulApiCallsProvider
     */
    public function testSuccessfullApiCalls(
        string $method,
        string $endpoint,
        array $requestData,
        array $responseData
    ): void {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            "*$endpoint*" => Http::response($responseData, 200)
        ]);

        $response = $this->mpesaClient->$method($requestData);
        $data = $response->getData();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_token');
        });

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('0', $response->getResponseCode());
        $this->assertEquals($responseData['ResponseDescription'], $response->getResponseDescription());
        $this->assertNotEmpty($data);

        if ($method === 'stkQuery') {
            $this->assertEquals($responseData['ResultCode'], $response->getResultCode());
            $this->assertEquals($responseData['ResultDesc'], $response->getResultDescription());
        }

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $data->$key);
        }
    }

    public function testItThrowsExceptionWhenRequiredArgumentsAreMissing()
    {
        $this->mockSuccessfulTokenGeneration();

        $this->expectException(InvalidArgumentException::class);

        $this->mpesaClient->stkPush([
            'Amount' => 1,
            'CallBackURL' => 'https://example.com/callback',
        ]);
    }

    public function testItCanHandleFailedSTKPushRequest(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '1',
                'ResponseDescription' => 'Failed',
            ], 200)
        ]);

        $response = $this->mpesaClient->stkPush([
            'BusinessShortCode' => '174379',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
            'CallBackURL' => 'https://example.com/callback',
        ])
        ->getData();

        $this->assertEquals('1', $response->ResponseCode);
        $this->assertEquals('Failed', $response->ResponseDescription);
    }

    public function testItUsesCachedTokenWhenActive(): void
    {
        $cachedToken = new MpesaAuthToken('active_test_token', 3600);
        Cache::put('mpesa_access_token', $cachedToken);

        Http::fake([
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200)
        ]);

        $this->mpesaClient->stkPush([
            'BusinessShortCode' => '174379',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
            'CallBackURL' => 'https://example.com/callback',
        ]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer active_test_token');
        });
    }

    public function testItRefreshesCachedTokenOnExpiration(): void
    {
        $expiredToken = new MpesaAuthToken('test_token', -3600);
        Cache::put('mpesa_access_token', $expiredToken);

        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'new_test_token',
                'expires_in' => '3599'
            ], 200)
        ]);

        HTTP::fake([
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200)
        ]);

        $this->mpesaClient->stkPush([
            'BusinessShortCode' => '174379',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
            'CallBackURL' => 'https://example.com/callback',
        ]);

        $cachedToken = Cache::get('mpesa_access_token');
        $this->assertEquals('new_test_token', $cachedToken->getToken());
    }

    public function testItHandlesTimeoutException(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([], 408)
        ]);

        $this->expectException(MpesaException::class);
        $this->expectExceptionMessage('Mpesa API request failed: []');

        $this->mpesaClient->stkPush([
            'BusinessShortCode' => '174379',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
            'CallBackURL' => 'https://example.com/callback',
        ]);
    }

    public function testItThrowsExceptionIfAccessTokenGenerationFails(): void
    {
        $this->mockFailedTokenGeneration();

        $this->expectException(MpesaException::class);
        $this->expectExceptionMessage('Failed to get access token: {"errorMessage":"Invalid credentials"}');

        $this->mpesaClient->stkPush([
            'BusinessShortCode' => '174379',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
            'CallBackURL' => 'https://example.com/callback',
        ]);
    }

    public function testThrowsExceptionForEmptyConsumerKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mpesa consumer key not configured');

        new MpesaClient(
            '',
            'test_secret',
            'sandbox',
            app('cache'),
            app(InitiatorCredentialGenerator::class)
        );
    }

    public function testThrowsExceptionForEmptyConsumerSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mpesa consumer secret not configured');

        new MpesaClient(
            'test_key',
            '',
            'sandbox',
            app('cache'),
            app(InitiatorCredentialGenerator::class)
        );
    }

    public function testThrowsExceptionForEmptyEnvironment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mpesa environment not configured');

        new MpesaClient(
            'test_key',
            'test_secret',
            '',
            app('cache'),
            app(InitiatorCredentialGenerator::class)
        );
    }

    public function testThrowsExceptionForInvalidEnvironment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Mpesa environment. Must be either sandbox or live');

        new MpesaClient(
            'test_key',
            'test_secret',
            'invalid',
            app('cache'),
            app(InitiatorCredentialGenerator::class)
        );
    }
}
