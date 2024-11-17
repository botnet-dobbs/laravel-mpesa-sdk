<?php

namespace Botnetdobbs\Mpesa\Http\Callbacks;

use Botnetdobbs\Mpesa\Contracts\{
    CallbackHandler,
    StkCallback,
    B2CCallback,
    TransactionStatusCallback,
    AccountBalanceCallback,
    ReversalCallback
};
use Botnetdobbs\Mpesa\Data\{
    B2CCallbackData,
    ReversalCallbackData,
    StkCallbackData,
    TransactionStatusCallbackData,
    AccountBalanceCallbackData
};
use Illuminate\Http\Request;

class MpesaCallback implements CallbackHandler
{
    public function handleStkCallback(Request $request): StkCallback
    {
        /**
         * @var array{
         *  MerchantRequestID: string,
         *  CheckoutRequestID: string,
         *  ResultCode: int,
         *  ResultDesc: string,
         *  CallbackMetadata?: array{Item: array{ Name: string, Value: string }[]}
         * } $result
         */
        $result = $request->input('Body.stkCallback');

        return new StkCallbackData(
            MerchantRequestID: $result['MerchantRequestID'],
            CheckoutRequestID: $result['CheckoutRequestID'],
            ResultCode: $result['ResultCode'],
            ResultDesc: $result['ResultDesc'],
            CallbackMetadata: $this->formatKeyValueArray(
                $result['CallbackMetadata']['Item'] ?? [], 
                'Name', 
                'Value'
            )
        );
    }

    public function handleB2CCallback(Request $request): B2CCallback
    {
        /**
         * @var array{
         *  ResultType: int,
         *  ResultCode: int,
         *  ResultDesc: string,
         *  OriginatorConversationID: string,
         *  ConversationID: string,
         *  TransactionID: string,
         *  ResultParameters?: array{ResultParameter: array{Key: string, Value: string}[]},
         *  ReferenceData?: array{ReferenceItem: array{Key: string, Value: string}[]}
         * } $result
         */
        $result = $request->input('Result');

        return new B2CCallbackData(
            ResultType: $result['ResultType'],
            ResultCode: $result['ResultCode'],
            ResultDesc: $result['ResultDesc'],
            OriginatorConversationID: $result['OriginatorConversationID'],
            ConversationID: $result['ConversationID'],
            TransactionID: $result['TransactionID'],
            ResultParameters: $this->formatKeyValueArray(
                $result['ResultParameters']['ResultParameter'] ?? [], 
                'Key', 
                'Value'
            ),
            ReferenceData: $this->formatKeyValueArray(
                $result['ReferenceData']['ReferenceItem'] ?? [], 
                'Key', 
                'Value'
            )
        );
    }

    public function handleTransactionStatusCallback(Request $request): TransactionStatusCallback
    {
        /**
         * @var array{
         *  ResultType: int,
         *  ResultCode: int,
         *  ResultDesc: string,
         *  OriginatorConversationID: string,
         *  ConversationID: string,
         *  TransactionID: string,
         *  ResultParameters?: array{ResultParameter: array{Key: string, Value: string}[]},
         *  ReferenceData?: array{ReferenceItem: array{Key: string, Value: string}[]}
         * } $result
         */
        $result = $request->input('Result');

        return new TransactionStatusCallbackData(
            ResultType: $result['ResultType'],
            ResultCode: $result['ResultCode'],
            ResultDesc: $result['ResultDesc'],
            OriginatorConversationID: $result['OriginatorConversationID'],
            ConversationID: $result['ConversationID'],
            TransactionID: $result['TransactionID'],
            ResultParameters: $this->formatKeyValueArray(
                $result['ResultParameters']['ResultParameter'] ?? [], 
                'Key', 
                'Value', 
                ['DebitPartyName']
            ),
            ReferenceData: $this->formatKeyValueArray(
                $result['ReferenceData']['ReferenceItem'] ?? [], 
                'Key', 
                'Value'
            )
        );
    }

    public function handleAccountBalanceCallback(Request $request): AccountBalanceCallback
    {
        /**
         * @var array{
         *  ResultType: int,
         *  ResultCode: int,
         *  ResultDesc: string,
         *  OriginatorConversationID: string,
         *  ConversationID: string,
         *  TransactionID: string,
         *  ResultParameters?: array{ResultParameter: array{Key: string, Value: string}[]},
         *  ReferenceData?: array{ReferenceItem: array{Key: string, Value: string}[]}
         * } $result
         */
        $result = $request->input('Result');

        return new AccountBalanceCallbackData(
            ResultType: $result['ResultType'],
            ResultCode: $result['ResultCode'],
            ResultDesc: $result['ResultDesc'],
            OriginatorConversationID: $result['OriginatorConversationID'],
            ConversationID: $result['ConversationID'],
            TransactionID: $result['TransactionID'],
            ResultParameters: $this->formatKeyValueArray(
                $result['ResultParameters']['ResultParameter'] ?? [], 
                'Key', 
                'Value'
            ),
            ReferenceData: $this->formatKeyValueArray(
                $result['ReferenceData']['ReferenceItem'] ?? [], 
                'Key', 
                'Value'
            )
        );
    }

    public function handleReversalCallback(Request $request): ReversalCallback
    {
        /**
         * @var array{
         *  ResultType: int,
         *  ResultCode: int,
         *  ResultDesc: string,
         *  OriginatorConversationID: string,
         *  ConversationID: string,
         *  TransactionID: string,
         *  ResultParameters?: array{ResultParameter: array{Key: string, Value: string}[]},
         *  ReferenceData?: array{ReferenceItem: array{Key: string, Value: string}[]}
         * } $result
         */
        $result = $request->input('Result');

        return new ReversalCallbackData(
            ResultType: $result['ResultType'],
            ResultCode: $result['ResultCode'],
            ResultDesc: $result['ResultDesc'],
            OriginatorConversationID: $result['OriginatorConversationID'],
            ConversationID: $result['ConversationID'],
            TransactionID: $result['TransactionID'],
            ResultParameters: $this->formatKeyValueArray(
                $result['ResultParameters']['ResultParameter'] ?? [], 
                'Key', 
                'Value'
            ),
            ReferenceData: $this->formatKeyValueArray(
                $result['ReferenceData']['ReferenceItem'] ?? [], 
                'Key', 
                'Value'
            )
        );
    }

    /**
     * Formats a multi-dimensional array into a key-value associative array
     * Example input:
     * [
     *    ['Name' => 'Amount', 'Value' => 100],
     *    ['Name' => 'Receipt', 'Value' => 'ABC123'],
     *    [ 'Name' => 'Other', 'Value' => 'XYZ' ],
     *    [ 'Name' => 'Other', 'Value' => 'XYZ2' ]
     * ]
     *
     * Example output:
     * [
     *    'Amount' => 100,
     *    'Receipt' => 'ABC123',
     *    'Other' => 'XYZ2'
     * ]
     * 
     * Example output with whereMultiple = ['Other']:
     * [
     *    'Amount' => 100,
     *    'Receipt' => 'ABC123',
     *    'Other' => [ 'XYZ', 'XYZ2' ]
     * ]
     *
     * @param array<int|string, array<string, mixed>|mixed> $array
     * @param string $keyField
     * @param string $valueField
     * @param array<int, string> $whereMultiple
     * 
     * @return array<string, mixed>
     */
    private function formatKeyValueArray(array $array, string $keyField, string $valueField, array $whereMultiple = []): array
    {
        if (empty($array)) {
            return [];
        }

        // If the array is already a key-value pair based associative array, return the first item
        if (isset($array[$keyField], $array[$valueField])) {
            return [
                (string) $array[$keyField] => $array[$valueField]
            ];
        }

        $result = [];
        foreach ($array as $item) {
            if (!is_array($item) || !isset($item[$keyField], $item[$valueField])) {
                continue;
            }

            $key = (string) $item[$keyField];
            $value = $item[$valueField];

            if (in_array($key, $whereMultiple, true) && isset($result[$key])) {
                if (! is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
