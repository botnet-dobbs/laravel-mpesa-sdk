<?php

namespace Botnetdobbs\Mpesa\Data;

use Botnetdobbs\Mpesa\Contracts\B2CCallback;

class B2CCallbackData extends BaseMpesaCallbackData implements B2CCallback
{
    public function __construct(
        public int $ResultType,
        public int $ResultCode,
        public string $ResultDesc,
        public string $OriginatorConversationID,
        public string $ConversationID,
        public string $TransactionID,
        public array $ResultParameters = [],
        public array $ReferenceData = []
    ) {
    }

    public function isSuccessful(): bool
    {
        return (int) $this->ResultCode === 0;
    }

    public function getOriginatorConversationId(): string
    {
        return $this->OriginatorConversationID;
    }

    public function getConversationId(): string
    {
        return $this->ConversationID;
    }

    public function getTransactionId(): string
    {
        return $this->TransactionID;
    }

    public function getResultCode(): int
    {
        return $this->ResultCode;
    }

    public function getResultType(): int
    {
        return $this->ResultType;
    }

    public function getResultDescription(): string
    {
        return $this->ResultDesc;
    }

    public function getTransactionAmount(): ?float
    {
        return $this->ResultParameters['TransactionAmount'] ?? null;
    }

    public function getTransactionReceipt(): ?string
    {
        return $this->ResultParameters['TransactionReceipt'] ?? null;
    }

    public function getB2CRecipientIsRegisteredCustomer(): ?string
    {
        return $this->ResultParameters['B2CRecipientIsRegisteredCustomer'] ?? null;
    }

    public function getReceiverPartyPublicName(): ?string
    {
        return $this->ResultParameters['ReceiverPartyPublicName'] ?? null;
    }

    public function getTransactionCompletedDateTime(): ?string
    {
        return $this->ResultParameters['TransactionCompletedDateTime'] ?? null;
    }

    public function getB2CUtilityAccountAvailableFunds(): ?float
    {
        return $this->ResultParameters['B2CUtilityAccountAvailableFunds'] ?? null;
    }

    public function getB2CWorkingAccountAvailableFunds(): ?float
    {
        return $this->ResultParameters['B2CWorkingAccountAvailableFunds'] ?? null;
    }

    public function getB2CChargesPaidAccountAvailableFunds(): ?float
    {
        return $this->ResultParameters['B2CChargesPaidAccountAvailableFunds'] ?? null;
    }
}
