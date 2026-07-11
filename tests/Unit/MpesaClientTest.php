<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Contracts\Client;
use Botnetdobbs\Mpesa\Exceptions\MpesaException;
use Botnetdobbs\Mpesa\Http\MpesaAuthToken;
use Botnetdobbs\Mpesa\Http\MpesaClient;
use Botnetdobbs\Mpesa\Services\InitiatorCredentialGenerator;
use Botnetdobbs\Mpesa\Tests\TestCase;
use Carbon\Carbon;
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
            'AccountReference' => 'test_ref',
        ],
        'stkQuery' => [
            'BusinessShortCode' => '174379',
            'CheckoutRequestID' => 'test_checkout_id',
        ],
        'b2c' => [
            'OriginatorConversationID' => 'test_conversation_id',
            'InitiatorName' => 'test_initiator',
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
            'CommandID' => 'AccountBalance',
            'PartyA' => '174379',
            'IdentifierType' => 4,
            'Remarks' => 'test_remark',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'ResultURL' => 'https://example.com/result',
        ],
        'transactionStatus' => [
            'Initiator' => 'test_initiator',
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
            'CommandID' => 'TransactionReversal',
            'TransactionID' => 'test_transaction_id',
            'Amount' => 1,
            'ReceiverParty' => '174379',
            'RecieverIdentifierType' => '11',
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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
                'endpoint' => '/v1/ussdpush/get-msisdn',
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

    #[\PHPUnit\Framework\Attributes\DataProvider('successfulApiCallsProvider')]
    public function testSuccessfullApiCalls(
        string $method,
        string $endpoint,
        array $requestData,
        array $responseData
    ): void {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599'
            ], 200),
            "*$endpoint*" => Http::response($responseData, 200)
        ]);

        $response = $this->mpesaClient->$method($requestData);
        $data = $response->getData();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_token');
        });

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(0, $response->getResponseCode());
        $this->assertEquals($responseData['ResponseDescription'], $response->getResponseDescription());
        $this->assertNotEmpty((array) $data);

        if ($method === 'stkQuery') {
            $this->assertEquals(0, $response->getResultCode());
            $this->assertEquals($responseData['ResultDesc'], $response->getResultDescription());
        }
    }

    public function testStkPushSendsConsistentPasswordAndTimestamp(): void
    {
        Carbon::setTestNow('2024-06-15 10:20:30');

        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599'
            ], 200),
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200),
        ]);

        $this->mpesaClient->stkPush(self::$defaultRequestData['stkPush']);

        $timestamp = '20240615102030';
        $expectedPassword = base64_encode(
            '174379' . config('mpesa.lipa_na_mpesa_passkey') . $timestamp
        );

        Http::assertSent(function ($request) use ($timestamp, $expectedPassword) {
            if (!str_contains($request->url(), 'stkpush')) {
                return false;
            }

            $data = $request->data();

            return $data['Timestamp'] === $timestamp
                && $data['Password'] === $expectedPassword
                && $data['PartyA'] === '254722188188'
                && $data['PartyB'] === '174379'
                && $data['PhoneNumber'] === '254722188188'
                && $data['TransactionType'] === 'CustomerPayBillOnline'
                && !array_key_exists('Password', self::$defaultRequestData['stkPush']);
        });
    }

    public function testStkPushIgnoresCallerSuppliedPasswordAndTimestamp(): void
    {
        Carbon::setTestNow('2024-06-15 10:20:30');

        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599'
            ], 200),
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200),
        ]);

        $payload = array_merge(self::$defaultRequestData['stkPush'], [
            'Password' => 'attacker-password',
            'Timestamp' => '19990101000000',
        ]);

        $this->mpesaClient->stkPush($payload);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'stkpush')) {
                return false;
            }

            $data = $request->data();

            return $data['Timestamp'] === '20240615102030'
                && $data['Password'] !== 'attacker-password';
        });
    }

    public function testB2cInjectsSecurityCredentialLast(): void
    {
        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599'
            ], 200),
            '*/mpesa/b2c/v3/paymentrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200),
        ]);

        $payload = array_merge(self::$defaultRequestData['b2c'], [
            'SecurityCredential' => 'caller-supplied-credential',
        ]);

        $this->mpesaClient->b2c($payload);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'b2c')) {
                return false;
            }

            return $request->data()['SecurityCredential'] === 'test_security_credential';
        });
    }

    public function testB2bDoesNotInjectSecurityCredential(): void
    {
        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599'
            ], 200),
            '*/v1/ussdpush/get-msisdn*' => Http::response([
                'code' => '0',
                'status' => 'USSD Initiated Successfully',
            ], 200),
        ]);

        $this->mpesaClient->b2b(self::$defaultRequestData['b2b']);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'ussdpush')) {
                return false;
            }

            return !array_key_exists('SecurityCredential', $request->data());
        });
    }

    public function testItThrowsExceptionWhenRequiredArgumentsAreMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->mpesaClient->stkPush([
            'Amount' => 1,
            'CallBackURL' => 'https://example.com/callback',
        ]);
    }

    public function testItThrowsExceptionWhenRequiredArgumentsAreBlank(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PhoneNumber');

        $this->mpesaClient->stkPush([
            'BusinessShortCode' => '174379',
            'Amount' => 1,
            'PhoneNumber' => '',
            'CallBackURL' => 'https://example.com/callback',
            'AccountReference' => 'test_ref',
        ]);
    }

    public function testItCanHandleFailedSTKPushRequest(): void
    {
        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599'
            ], 200),
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '1',
                'ResponseDescription' => 'Failed',
            ], 200)
        ]);

        $response = $this->mpesaClient->stkPush(self::$defaultRequestData['stkPush']);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(1, $response->getResponseCode());
        $this->assertEquals('Failed', $response->getResponseDescription());
    }

    public function testMalformedApiResponseDoesNotThrowTypeError(): void
    {
        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599'
            ], 200),
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'error' => 'bad',
            ], 200)
        ]);

        $response = $this->mpesaClient->stkPush(self::$defaultRequestData['stkPush']);

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(1, $response->getResponseCode());
        $this->assertEquals('', $response->getResponseDescription());
        $this->assertEquals(1, $response->getResultCode());
        $this->assertEquals('', $response->getResultDescription());
        $this->assertEquals('bad', $response->getData()->error);
    }

    public function testItUsesCachedTokenWhenActive(): void
    {
        $cachedToken = new MpesaAuthToken('active_test_token', 3600);
        Cache::put($this->accessTokenCacheKey(), $cachedToken);

        Http::fake([
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200)
        ]);

        $this->mpesaClient->stkPush(self::$defaultRequestData['stkPush']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer active_test_token');
        });
    }

    public function testItRefreshesCachedTokenOnExpiration(): void
    {
        $expiredToken = new MpesaAuthToken('test_token', -3600);
        Cache::put($this->accessTokenCacheKey(), $expiredToken);

        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'new_test_token',
                'expires_in' => '3599'
            ], 200),
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200)
        ]);

        $this->mpesaClient->stkPush(self::$defaultRequestData['stkPush']);

        $cachedToken = Cache::get($this->accessTokenCacheKey());
        $this->assertInstanceOf(MpesaAuthToken::class, $cachedToken);
        $this->assertEquals('new_test_token', $cachedToken->getToken());
    }

    public function testItHandlesTimeoutException(): void
    {
        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599'
            ], 200),
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([], 408)
        ]);

        try {
            $this->mpesaClient->stkPush(self::$defaultRequestData['stkPush']);
            $this->fail('Expected MpesaException was not thrown');
        } catch (MpesaException $e) {
            $this->assertStringContainsString('Mpesa API request failed', $e->getMessage());
            $this->assertEquals(408, $e->status);
        }
    }

    public function testItThrowsExceptionIfAccessTokenGenerationFails(): void
    {
        $this->mockFailedTokenGeneration();

        try {
            $this->mpesaClient->stkPush(self::$defaultRequestData['stkPush']);
            $this->fail('Expected MpesaException was not thrown');
        } catch (MpesaException $e) {
            $this->assertStringContainsString('Failed to get access token', $e->getMessage());
            $this->assertEquals(401, $e->status);
        }
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
