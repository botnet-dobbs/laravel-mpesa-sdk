<?php

namespace Botnetdobbs\Mpesa\Validation;

use Botnetdobbs\Mpesa\Enums\MpesaRequestType;

class RequiredFields
{
    /**
     * @var array<string, array<string>> $fields
     */
    private static array $fields = [
        MpesaRequestType::STK_PUSH->value => [
            'BusinessShortCode',
            'Amount',
            'PhoneNumber',
            'CallBackURL',
            'AccountReference',
        ],
        MpesaRequestType::STK_QUERY->value => [
            'BusinessShortCode',
            'CheckoutRequestID'
        ],
        MpesaRequestType::B2C->value => [
            'OriginatorConversationID',
            'InitiatorName',
            'CommandID',
            'Amount',
            'PartyA',
            'PartyB',
            'ResultURL',
            'QueueTimeOutURL',
        ],
        MpesaRequestType::B2B->value => [
            'primaryShortCode',
            'receiverShortCode',
            'amount',
            'paymentRef',
            'callbackUrl',
            'partnerName',
            'RequestRefID'
        ],
        MpesaRequestType::C2B_REGISTER->value => [
            'ShortCode',
            'ResponseType',
            'ConfirmationURL',
            'ValidationURL'
        ],
        MpesaRequestType::C2B_SIMULATE->value => [
            'ShortCode',
            'Amount',
            'Msisdn'
        ],
        MpesaRequestType::ACCOUNT_BALANCE->value => [
            'Initiator',
            'PartyA',
            'IdentifierType',
            'ResultURL',
            'QueueTimeOutURL',
        ],
        MpesaRequestType::TRANSACTION_STATUS->value => [
            'Initiator',
            'TransactionID',
            'PartyA',
            'IdentifierType',
            'ResultURL',
            'QueueTimeOutURL',
        ],
        MpesaRequestType::REVERSAL->value => [
            'Initiator',
            'CommandID',
            'TransactionID',
            'Amount',
            'ReceiverParty',
            'RecieverIdentifierType',
            'Remarks',
            'ResultURL',
            'QueueTimeOutURL',
        ],
    ];

    /**
     * @param MpesaRequestType $type
     * @return array<string>
     */
    public static function get(MpesaRequestType $type): array
    {
        return self::$fields[$type->value] ?? [];
    }
}
