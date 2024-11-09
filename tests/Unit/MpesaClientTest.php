<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Exceptions\MpesaException;
use Botnetdobbs\Mpesa\Facades\Mpesa;
use Botnetdobbs\Mpesa\Http\MpesaAuthToken;
use Botnetdobbs\Mpesa\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MpesaClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testItThrowsExceptionIfAccessTokenGenerationFails(): void
    {
        $this->mockFailedTokenGeneration();

        $this->expectException(MpesaException::class);
        $this->expectExceptionMessage('Failed to get access token: {"errorMessage":"Invalid credentials"}');

        Mpesa::stkPush([
            'BusinessShortCode' => '174379',
            'Passkey' => 'test_passkey',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
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

        $response = Mpesa::stkPush([
            'BusinessShortCode' => '174379',
            'Passkey' => 'test_passkey',
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
        ]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_token');
        });

        $this->assertEquals('0', $response->ResponseCode);
        $this->assertEquals('Success. Request accepted for processing', $response->ResponseDescription);
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

        $response = Mpesa::stkPush([
            'BusinessShortCode' => '174379',
            'Passkey' => 'test_passkey',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
        ]);

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

        $response = Mpesa::stkQuery([
            'BusinessShortCode' => '174379',
            'Passkey' => 'test_passkey',
            'CheckoutRequestID' => 'test_checkout_id',
        ]);

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
            '*/mpesa/b2c/v1/paymentrequest*' => Http::response([
                'ResponseCode' => '0',
                'ResponseDescription' => 'Accept the service request successfully.',
            ], 200)
        ]);

        $response = Mpesa::b2c([
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
        ]);

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

        $response = Mpesa::b2b([
            'primaryShortCode' => '000001',
            'receiverShortCode' => '000002',
            'amount' => '100',
            'paymentRef' => 'paymentRef',
            'callbackUrl' => 'https://example.com/callback',
            'partnerName' => 'Vendor',
            'RequestRefID' => 'test_request_id',
        ]);

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

        $response = Mpesa::c2bRegister([
            'ShortCode' => '174379',
            'ResponseType' => 'Completed',
            'ConfirmationURL' => 'https://example.com/confirm',
            'ValidationURL' => 'https://example.com/validate',
        ]);

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

        $response = Mpesa::c2bSimulate([
            'ShortCode' => '174379',
            'CommandID' => 'CustomerPayBillOnline',
            'Amount' => 1,
            'Msisdn' => '254722188188',
            'BillRefNumber' => 'test_bill_ref',
        ]);

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

        $response = Mpesa::accountBalance([
            'Initiator' => 'test_initiator',
            'SecurityCredential' => 'test_credential',
            'CommandID' => 'AccountBalance',
            'PartyA' => '174379',
            'IdentifierType' => 4,
            'Remarks' => 'test_remark',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'ResultURL' => 'https://example.com/result',
        ]);

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

        $response = Mpesa::transactionStatus([
            'Initiator' => 'test_initiator',
            'SecurityCredential' => 'test_credential',
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => 'test_transaction_id',
            'PartyA' => '174379',
            'IdentifierType' => 4,
            'ResultURL' => 'https://example.com/result',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'Remarks' => 'test_remark',
        ]);

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

        $response = Mpesa::reversal([
            'Initiator' => 'test_initiator',
            'SecurityCredential' => 'test_credential',
            'CommandID' => 'TransactionReversal',
            'TransactionID' => 'test_transaction_id',
            'Amount' => 1,
            'ReceiverParty' => '174379',
            'RecieverIdentifierType' => 4,
            'ResultURL' => 'https://example.com/result',
            'QueueTimeOutURL' => 'https://example.com/timeout',
            'Remarks' => 'test_remark',
            'Occasion' => 'test_occasion',
        ]);

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

        Mpesa::stkPush([
            'BusinessShortCode' => '174379',
            'Passkey' => 'test_passkey',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
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

        Mpesa::stkPush([
            'BusinessShortCode' => '174379',
            'Passkey' => 'test_passkey',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
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

        Mpesa::stkPush([
            'BusinessShortCode' => '174379',
            'Passkey' => 'test_passkey',
            'Amount' => 1,
            'PhoneNumber' => '254722188188',
        ]);
    }
}
