<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Contracts\AccountBalanceCallback;
use Botnetdobbs\Mpesa\Contracts\B2CCallback;
use Botnetdobbs\Mpesa\Contracts\ReversalCallback;
use Botnetdobbs\Mpesa\Contracts\StkCallback;
use Botnetdobbs\Mpesa\Contracts\TransactionStatusCallback;
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

        $callback = $this->mpesaCallback->handleStkCallback($request);

        $this->assertInstanceOf(StkCallback::class, $callback);

        $this->assertTrue($callback->isSuccessful());
        $this->assertEquals(1.00, $callback->getAmount());
        $this->assertEquals('NLJ7RT61SV', $callback->getReceiptNumber());
        $this->assertEquals(20191219102115, $callback->getTransactionDate());
        $this->assertEquals(254708374149, $callback->getPhoneNumber());
        $this->assertEquals('29115-34620561-1', $callback->getMerchantRequestId());
        $this->assertEquals('ws_CO_191220191020363925', $callback->getCheckoutRequestId());
        $this->assertEquals(0, $callback->getResultCode());
        $this->assertEquals('The service request is processed successfully.', $callback->getResultDescription());
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

        $callback = $this->mpesaCallback->handleStkCallback($request);

        $this->assertInstanceOf(StkCallback::class, $callback);

        $this->assertFalse($callback->isSuccessful());
        $this->assertEquals(1032, $callback->getResultCode());
        $this->assertEquals('Request cancelled by user', $callback->getResultDescription());
        $this->assertNull($callback->getAmount());
        $this->assertNull($callback->getReceiptNumber());
        $this->assertNull($callback->getTransactionDate());
        $this->assertNull($callback->getPhoneNumber());
    }

    public function testItCanHandleB2CCallback(): void
    {
        $request = new Request();
        $request->merge([
            'Result' => [
                'ResultType' => 0,
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'OriginatorConversationID' => '10571-7910404-1',
                'ConversationID' => 'AG_20191219_00004e48cf7e3533f581',
                'TransactionID' => 'NLJ41HAY6Q',
                'ResultParameters' => [
                    'ResultParameter' => [
                        ['Key' => 'TransactionAmount', 'Value' => 10],
                        ['Key' => 'TransactionReceipt', 'Value' => 'NLJ41HAY6Q'],
                        ['Key' => 'B2CRecipientIsRegisteredCustomer', 'Value' => 'Y'],
                        ['Key' => 'B2CChargesPaidAccountAvailableFunds', 'Value' => 0],
                        ['Key' => 'ReceiverPartyPublicName', 'Value' => '254708374149 - John Doe'],
                        ['Key' => 'TransactionCompletedDateTime', 'Value' => '19.12.2019 11:45:50'],
                        ['Key' => 'B2CUtilityAccountAvailableFunds', 'Value' => 10116.00],
                        ['Key' => 'B2CWorkingAccountAvailableFunds', 'Value' => 0],
                    ]
                ],
                'ReferenceData' => [
                    'ReferenceItem' => [
                        'Key' => 'QueueTimeoutURL',
                        'Value' => 'https://example.com/timeout'
                    ]
                ]
            ]
        ]);

        $callback = $this->mpesaCallback->handleB2CCallback($request);

        $this->assertInstanceOf(B2CCallback::class, $callback);

        $this->assertTrue($callback->isSuccessful());
        $this->assertEquals(10, $callback->getTransactionAmount());
        $this->assertEquals(0, $callback->getResultType());
        $this->assertEquals(0, $callback->getResultCode());
        $this->assertEquals('The service request is processed successfully.', $callback->getResultDescription());
        $this->assertEquals('10571-7910404-1', $callback->getOriginatorConversationId());
        $this->assertEquals('AG_20191219_00004e48cf7e3533f581', $callback->getConversationId());
        $this->assertEquals('NLJ41HAY6Q', $callback->getTransactionId());
        $this->assertEquals('Y', $callback->getB2CRecipientIsRegisteredCustomer());
        $this->assertEquals(0, $callback->getB2CChargesPaidAccountAvailableFunds());
        $this->assertEquals(0, $callback->getB2CWorkingAccountAvailableFunds());
        $this->assertEquals('NLJ41HAY6Q', $callback->getTransactionReceipt());
        $this->assertEquals('254708374149 - John Doe', $callback->getReceiverPartyPublicName());
        $this->assertEquals('19.12.2019 11:45:50', $callback->getTransactionCompletedDateTime());
        $this->assertEquals(10116.00, $callback->getB2CUtilityAccountAvailableFunds());
    }

    public function testItCanHandleFailedB2CCallback(): void
    {
        $request = new Request();
        $request->merge([
            'Result' => [
                'ResultType' => 0,
                'ResultCode' => 2001,
                'ResultDesc' => 'The initiator information is invalid.',
                'OriginatorConversationID' => '10571-7910404-1',
                'ConversationID' => 'AG_20191219_00004e48cf7e3533f581',
                'TransactionID' => 'NLJ41HAY6Q',
                'ReferenceData' => [
                    'ReferenceItem' => [
                        'Key' => 'QueueTimeoutURL',
                        'Value' => 'https://example.com/timeout'
                    ]
                ]
            ]
        ]);

        $callback = $this->mpesaCallback->handleB2CCallback($request);

        $this->assertInstanceOf(B2CCallback::class, $callback);

        $this->assertFalse($callback->isSuccessful());
        $this->assertEquals(2001, $callback->getResultCode());
        $this->assertEquals('The initiator information is invalid.', $callback->getResultDescription());
        $this->assertNull($callback->getTransactionAmount());
        $this->assertNull($callback->getTransactionReceipt());
    }

    public function testItCanHandleTransactionStatusCallback(): void
    {
        $request = new Request();
        $request->merge([
            'Result' => [
                'ResultType' => 0,
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'OriginatorConversationID' => '3213-416199-2',
                'ConversationID' => 'AG_20180223_0000493344ae97d86f75',
                'TransactionID' => 'MBN0000000',
                'ResultParameters' => [
                    'ResultParameter' => [
                        ['Key' => 'TransactionStatus', 'Value' => 'Completed'],
                        ['Key' => 'Amount', 'Value' => '300'],
                        ['Key' => 'ReceiptNo', 'Value' => 'MBN31H462N'],
                        ['Key' => 'InitiatedTime', 'Value' => '20180223054112'],
                        ['Key' => 'FinalisedTime', 'Value' => '20180223054112'],
                        ['Key' => 'DebitPartyName', 'Value' => '601315 - Safaricom1338'],
                        ['Key' => 'DebitPartyName', 'Value' => '601315 - Safaricom'],
                        ['Key' => 'DebitAccountType', 'Value' => 'Utility Account'],
                        ['Key' => 'DebitPartyCharges', 'Value' => 'Fee For B2C Payment|KES|22.40'],
                        ['Key' => 'OriginatorConversationID', 'Value' => '3213-416199-2'],
                    ]
                ]
            ]
        ]);

        $callback = $this->mpesaCallback->handleTransactionStatusCallback($request);

        $this->assertInstanceOf(TransactionStatusCallback::class, $callback);

        $this->assertTrue($callback->isSuccessful());
        $this->assertEquals('Completed', $callback->getTransactionStatus());
        $this->assertEquals(0, $callback->getResultCode());
        $this->assertEquals(0, $callback->getResultType());
        $this->assertEquals('The service request is processed successfully.', $callback->getResultDescription());
        $this->assertEquals(300, $callback->getAmount());
        $this->assertEquals('MBN31H462N', $callback->getReceiptNumber());
        $this->assertEquals('20180223054112', $callback->getInitiatedTime());
        $this->assertEquals('20180223054112', $callback->getFinalisedTime());
        $this->assertEquals('3213-416199-2', $callback->getOriginatorConversationId());
        $this->assertEquals('AG_20180223_0000493344ae97d86f75', $callback->getConversationId());
        $this->assertEquals('MBN0000000', $callback->getTransactionId());
        $this->assertEquals('Utility Account', $callback->getDebitAccountType());
        $this->assertEquals(['601315 - Safaricom1338', '601315 - Safaricom'], $callback->getDebitPartyNames());

        $debitPartyCharges = $callback->getDebitPartyCharges();
        $this->assertCount(1, $debitPartyCharges);
        $this->assertEquals('KES', $debitPartyCharges[0]['Currency']);
        $this->assertEquals(22.40, $debitPartyCharges[0]['Amount']);
        $this->assertEquals('Fee For B2C Payment', $debitPartyCharges[0]['Account']);

        $debitPartyCharge = $callback->getDebitPartyCharge('Fee For B2C Payment');
        $this->assertEquals('KES', $debitPartyCharge['Currency']);
        $this->assertEquals(22.40, $debitPartyCharge['Amount']);
    }

    public function testItCanHandleAccountBalanceCallback(): void
    {
        $request = new Request();
        $request->merge([
            'Result' => [
                'ResultType' => 0,
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'OriginatorConversationID' => '16917-22577599-3',
                'ConversationID' => 'AG_20200206_00005e091a8ec6b9eac5',
                'TransactionID' => 'OA90000000',
                'ResultParameters' => [
                    'ResultParameter' => [
                        [
                            'Key' => 'AccountBalance',
                            'Value' => 'Working Account|KES|700000.00|700000.00|0.00|0.00&Float Account|KES|0|0|0|0'
                        ],
                        [
                            'Key' => 'BOCompletedTime',
                            'Value' => '20200109125710'
                        ]
                    ]
                ]
            ]
        ]);

        $callback = $this->mpesaCallback->handleAccountBalanceCallback($request);

        $this->assertInstanceOf(AccountBalanceCallback::class, $callback);
        $this->assertTrue($callback->isSuccessful());

        $balances = $callback->getAccountBalances();
        $this->assertCount(2, $balances);

        $workingAccount = $callback->getBalanceForAccount('Working Account');
        $this->assertEquals('KES', $workingAccount['Currency']);
        $this->assertEquals(700000.00, $workingAccount['Amount']);

        $floatAccount = $callback->getBalanceForAccount('Float Account');
        $this->assertEquals('KES', $floatAccount['Currency']);
        $this->assertEquals(0.00, $floatAccount['Amount']);

        $this->assertEquals('16917-22577599-3', $callback->getOriginatorConversationId());
        $this->assertEquals('AG_20200206_00005e091a8ec6b9eac5', $callback->getConversationId());
        $this->assertEquals('OA90000000', $callback->getTransactionId());
        $this->assertEquals(0, $callback->getResultCode());
        $this->assertEquals(0, $callback->getResultType());
        $this->assertEquals('The service request is processed successfully.', $callback->getResultDescription());
        $this->assertEquals('20200109125710', $callback->getCompletedTime());
    }

    public function testItCanHandleReversalCallback(): void
    {
        $request = new Request();
        $request->merge([
            'Result' => [
                'ResultType' => 0,
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'OriginatorConversationID' => '8521-4298025-1',
                'ConversationID' => 'AG_20181005_00004d7ee675c0c7ee0b',
                'TransactionID' => 'MJ561H6X5O',
                'ResultParameters' => [
                    'ResultParameter' => [
                        ['Key' => 'DebitAccountBalance', 'Value' => 'Working Account|KES|51661.00|51661.00|0.00|0.00'],
                        ['Key' => 'Amount', 'Value' => '100'],
                        ['Key' => 'TransCompletedTime', 'Value' => '20181005153225'],
                        ['Key' => 'OriginalTransactionID', 'Value' => 'MJ551H6X5D'],
                        ['Key' => 'Charge', 'Value' => '0'],
                        ['Key' => 'CreditPartyPublicName', 'Value' => '254708374149 - John Doe'],
                        ['Key' => 'DebitPartyPublicName', 'Value' => '601315 - Safaricom1338']
                    ]
                ]
            ]
        ]);

        $callback = $this->mpesaCallback->handleReversalCallback($request);

        $this->assertInstanceOf(ReversalCallback::class, $callback);
        $this->assertTrue($callback->isSuccessful());

        $balances = $callback->getDebitAccountBalances();
        $this->assertCount(1, $balances);
        $this->assertEquals('KES', $balances[0]['Currency']);
        $this->assertEquals(51661.00, $balances[0]['Amount']);
        $this->assertEquals('Working Account', $balances[0]['Account']);

        $balance = $callback->getDebitAccountBalance('Working Account');
        $this->assertEquals('KES', $balance['Currency']);
        $this->assertEquals(51661.00, $balance['Amount']);

        $this->assertEquals('MJ561H6X5O', $callback->getTransactionId());
        $this->assertEquals('8521-4298025-1', $callback->getOriginatorConversationId());
        $this->assertEquals('AG_20181005_00004d7ee675c0c7ee0b', $callback->getConversationId());
        $this->assertEquals(0, $callback->getResultCode());
        $this->assertEquals(0, $callback->getResultType());
        $this->assertEquals('The service request is processed successfully.', $callback->getResultDescription());
        $this->assertEquals(100, $callback->getAmount());
        $this->assertEquals('MJ551H6X5D', $callback->getOriginalTransactionId());
        $this->assertEquals('20181005153225', $callback->getTransactionCompletedTime());
        $this->assertEquals(0, $callback->getCharge());
        $this->assertEquals('254708374149 - John Doe', $callback->getCreditPartyPublicName());
        $this->assertEquals('601315 - Safaricom1338', $callback->getDebitPartyPublicName());
    }
}
