<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Contracts\TransactionResult;
use Botnetdobbs\Mpesa\Http\Callbacks\MpesaCallback;
use Botnetdobbs\Mpesa\Tests\TestCase;
use Illuminate\Http\Request;

class MpesaCallbackTest extends TestCase
{
    private MpesaCallback $mpesaCallback;
    private static array $defaultCallbackData = [
        'stkCallback' => [
            'success' => [
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
            ],
            'failure' => [
                'Body' => [
                    'stkCallback' => [
                        'MerchantRequestID' => '29115-34620561-1',
                        'CheckoutRequestID' => 'ws_CO_191220191020363925',
                        'ResultCode' => 1032,
                        'ResultDesc' => 'Request cancelled by user',
                    ]
                ]
            ]
        ],
        'b2cCallback' => [
            'success' => [
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
            ],
            'failure' => [
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
            ]
        ],
        'transactionStatusCallback' => [
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
        ],
        'accountBalanceCallback' => [
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
        ],
        'reversalCallback' => [
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
        ]
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->mpesaCallback = new MpesaCallback();
    }

    /**
     * @dataProvider successfulCallbackProvider
     */
    public function testSuccessfulCallbacks(string $type, array $requestData): void
    {
        $request = new Request();
        $request->merge($requestData);

        $result = $this->mpesaCallback->handle($request);
        $data = $result->getData();

        $this->assertInstanceOf(TransactionResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(0, $result->getResultCode());
        $this->assertEquals('The service request is processed successfully.', $result->getResultDescription());

        if ($type === 'stkCallback') {
            $this->assertEquals('29115-34620561-1', $data->Body->stkCallback->MerchantRequestID);
            $this->assertEquals('ws_CO_191220191020363925', $data->Body->stkCallback->CheckoutRequestID);
            $this->assertEquals(1.00, $data->Body->stkCallback->CallbackMetadata->Item[0]->Value);
        } else {
            $this->assertEquals(0, $result->getResultCode());
            $this->assertEquals($requestData['Result']['ResultDesc'], $result->getResultDescription());
            $this->assertEquals(
                $requestData['Result']['OriginatorConversationID'],
                $data->Result->OriginatorConversationID
            );
            $this->assertEquals($requestData['Result']['TransactionID'], $data->Result->TransactionID);
            $this->assertEquals($requestData['Result']['ConversationID'], $data->Result->ConversationID);
        }
    }

    public static function successfulCallbackProvider(): array
    {
        return [
            'STK Push Success' => [
                'type' => 'stkCallback',
                'requestData' => self::$defaultCallbackData['stkCallback']['success']
            ],
            'B2C Success' => [
                'type' => 'b2cCallback',
                'requestData' => self::$defaultCallbackData['b2cCallback']['success']
            ],
            'Transaction Status' => [
                'type' => 'transactionStatusCallback',
                'requestData' => self::$defaultCallbackData['transactionStatusCallback']
            ],
            'Account Balance' => [
                'type' => 'accountBalanceCallback',
                'requestData' => self::$defaultCallbackData['accountBalanceCallback']
            ],
            'Reversal' => [
                'type' => 'reversalCallback',
                'requestData' => self::$defaultCallbackData['reversalCallback']
            ]
        ];
    }

    /**
     * @dataProvider failedCallbackProvider
     */
    public function testFailedCallbacks(string $type, array $requestData, int $expectedCode, string $expectedDesc): void
    {
        $request = new Request();
        $request->merge($requestData);

        $result = $this->mpesaCallback->handle($request);

        $this->assertInstanceOf(TransactionResult::class, $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals($expectedCode, $result->getResultCode());
        $this->assertEquals($expectedDesc, $result->getResultDescription());
    }

    public static function failedCallbackProvider(): array
    {
        return [
            'STK Push Failure' => [
                'type' => 'stkCallback',
                'requestData' => self::$defaultCallbackData['stkCallback']['failure'],
                'expectedCode' => 1032,
                'expectedDesc' => 'Request cancelled by user'
            ],
            'B2C Failure' => [
                'type' => 'b2cCallback',
                'requestData' => self::$defaultCallbackData['b2cCallback']['failure'],
                'expectedCode' => 2001,
                'expectedDesc' => 'The initiator information is invalid.'
            ]
        ];
    }
}
