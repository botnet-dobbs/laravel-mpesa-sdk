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

    protected function setUp(): void
    {
        parent::setUp();

        $mockGenerator = Mockery::mock(InitiatorCredentialGenerator::class);
        $mockGenerator->shouldReceive('generate')
            ->andReturn('test_security_credential');
        $this->app->instance(InitiatorCredentialGenerator::class, $mockGenerator);

        $this->mpesaClient = $this->app->make(Client::class);
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

    public function testItCanGenerateAccessTokenAndSendStkPush(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/stkpush/v1/processrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
            ], 200)
        ]);

        $response = $this->mpesaClient->stkPush([
            'BusinessShortCode' => '174379',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
            'CallBackURL' => 'https://example.com/callback',
        ])
        ->getData();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_token');
        });

        $this->assertEquals('0', $response->ResponseCode);
        $this->assertEquals('Success. Request accepted for processing', $response->ResponseDescription);
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

        $this->assertEquals('Failed', $response->ResponseDescription);
    }

    public function testItCanHandleStkQueryRequest(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/stkpushquery/v1/query*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'The service request has been accepted successfully',
                'MerchantRequestID' => 'test_merchant_id',
                'CheckoutRequestID' => 'test_checkout_id',
                'ResultCode' => '0',
                'ResultDesc' => 'The service request is processed successfully.',
            ], 200)
        ]);

        $response = $this->mpesaClient->stkQuery([
            'BusinessShortCode' => '174379',
            'CheckoutRequestID' => 'test_checkout_id',
        ])
        ->getData();

        $this->assertEquals('The service request has been accepted successfully', $response->ResponseDescription);
        $this->assertEquals('The service request is processed successfully.', $response->ResultDesc);
        $this->assertEquals('0', $response->ResultCode);
        $this->assertEquals('test_merchant_id', $response->MerchantRequestID);
        $this->assertEquals('test_checkout_id', $response->CheckoutRequestID);
    }

    public function testItCanHandleB2cPaymentRequest(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/b2c/v3/paymentrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Accept the service request successfully.',
            ], 200)
        ]);

        $response = $this->mpesaClient->b2c([
            "OriginatorConversationID" => "test_conversation_id",
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
        ])
        ->getData();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_token');
        });

        $this->assertEquals('0', $response->ResponseCode);
        $this->assertEquals('Accept the service request successfully.', $response->ResponseDescription);
    }

    public function testItCanHandleB2BPaymentRequest(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/b2b/v1/paymentrequest*' => Http::response([
                'code' => '0',
                'status' => 'USSD Initiated Successfully',
            ], 200)
        ]);

        $response = $this->mpesaClient->b2b([
            'primaryShortCode' => '000001',
            'receiverShortCode' => '000002',
            'amount' => '100',
            'paymentRef' => 'paymentRef',
            'callbackUrl' => 'https://example.com/callback',
            'partnerName' => 'Vendor',
            'RequestRefID' => 'test_request_id',
        ])
        ->getData();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_token');
        });

        $this->assertEquals('0', $response->code);
        $this->assertEquals('USSD Initiated Successfully', $response->status);
    }

    public function testItCanHandleC2BRegisterUrlRequest(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/c2b/v1/registerurl*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200)
        ]);

        $response = $this->mpesaClient->c2bRegister([
            'ShortCode' => '174379',
            'ResponseType' => 'Completed',
            'ConfirmationURL' => 'https://example.com/confirm',
            'ValidationURL' => 'https://example.com/validate',
        ])
        ->getData();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_token');
        });

        $this->assertEquals('Success', $response->ResponseDescription);
    }

    public function testItCanHandleC2BSimulateRequest(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/c2b/v1/simulate*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200)
        ]);

        $response = $this->mpesaClient->c2bSimulate([
            'ShortCode' => '174379',
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => 1,
            'Msisdn' => '254722188188',
            'BillRefNumber' => 'test_bill_ref',
        ])
        ->getData();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_token');
        });

        $this->assertEquals('0', $response->ResponseCode);
        $this->assertEquals('Success', $response->ResponseDescription);
    }

    public function testItCanHandleAccountBalanceRequest(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/accountbalance/v1/query*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success',
            ], 200)
        ]);

        $response = $this->mpesaClient->accountBalance([
            'Initiator' => 'test_initiator',
            'SecurityCredential' => 'test_credential',
            'CommandID' => 'AccountBalance',
            'PartyA' => '174379',
            'IdentifierType' => 4,
            'Remarks' => 'test_remark',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'ResultURL' => 'https://example.com/result',
        ])
        ->getData();

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_token');
        });

        $this->assertEquals('0', $response->ResponseCode);
        $this->assertEquals('Success', $response->ResponseDescription);
    }

    public function testItCanCheckTransactionStatus(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/transactionstatus/v1/query*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Accept the service request successfully.',
            ], 200)
        ]);

        $response = $this->mpesaClient->transactionStatus([
            'Initiator' => 'test_initiator',
            'SecurityCredential' => 'test_credential',
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => 'test_transaction_id',
            'PartyA' => '174379',
            'IdentifierType' => 4,
            'ResultURL' => 'https://example.com/result',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'Remarks' => 'test_remark',
        ])
        ->getData();

        $this->assertEquals('0', $response->ResponseCode);
        $this->assertEquals('Accept the service request successfully.', $response->ResponseDescription);
    }

    public function testItCanHandleReversalRequest(): void
    {
        $this->mockSuccessfulTokenGeneration();

        Http::fake([
            '*/mpesa/reversal/v1/request*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Accept the service request successfully.',
            ], 200)
        ]);

        $response = $this->mpesaClient->reversal([
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
        ])
        ->getData();

        $this->assertEquals('0', $response->ResponseCode);
        $this->assertEquals('Accept the service request successfully.', $response->ResponseDescription);
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
        $this->expectExceptionMessage('Invalid Mpesa environment. Must be either sandbox or production');

        new MpesaClient(
            'test_key',
            'test_secret',
            'invalid',
            app('cache'),
            app(InitiatorCredentialGenerator::class)
        );
    }
}
