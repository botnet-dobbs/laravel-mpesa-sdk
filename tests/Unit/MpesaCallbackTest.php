<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Contracts\Callbacks\AccountBalanceCallback;
use Botnetdobbs\Mpesa\Contracts\Callbacks\B2CCallback;
use Botnetdobbs\Mpesa\Contracts\Callbacks\ReversalCallback;
use Botnetdobbs\Mpesa\Contracts\Callbacks\StkCallback;
use Botnetdobbs\Mpesa\Contracts\Callbacks\TransactionStatusCallback;
use Botnetdobbs\Mpesa\Contracts\TransactionResult;
use Botnetdobbs\Mpesa\Http\Callbacks\MpesaCallback;
use Botnetdobbs\Mpesa\Tests\TestCase;
use Illuminate\Http\Request;

class MpesaCallbackTest extends TestCase
{
    private MpesaCallback $mpesaCallback;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mpesaCallback = new MpesaCallback();
    }

    public function testItCanHandleStkCallback(): void
    {
        $request = new Request();
        $request->merge([
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => '29115-34620561-1',
                    'CheckoutRequestID' => 'ws_CO_191220191020363925',
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 1.00],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'NLJ7RT61SV'],
                            ['Name' => 'TransactionDate', 'Value' => 20191219102115],
                            ['Name' => 'PhoneNumber', 'Value' => 254708374149]
                        ]
                    ]
                ]
            ]
        ]);

        $result = $this->mpesaCallback->handle($request);

        $this->assertInstanceOf(TransactionResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(0, $result->getResultCode());
        $this->assertEquals('The service request is processed successfully.', $result->getResultDescription());

        $data = $result->getData();
        $this->assertEquals('29115-34620561-1', $data->Body->stkCallback->MerchantRequestID);
        $this->assertEquals('ws_CO_191220191020363925', $data->Body->stkCallback->CheckoutRequestID);
        $this->assertEquals(1.00, $data->Body->stkCallback->CallbackMetadata->Item[0]->Value);
    }

    public function testHandleFailedStkCallback(): void
    {
        $request = new Request();
        $request->merge([
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => '29115-34620561-1',
                    'CheckoutRequestID' => 'ws_CO_191220191020363925',
                    'ResultCode' => 1032,
                    'ResultDesc' => 'Request cancelled by user',
                ]
            ]
        ]);

        $result = $this->mpesaCallback->handle($request);

        $this->assertInstanceOf(TransactionResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(1032, $result->getResultCode());
        $this->assertEquals('Request cancelled by user', $result->getResultDescription());
    }
}
