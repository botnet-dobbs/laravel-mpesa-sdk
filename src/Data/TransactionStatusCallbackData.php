<?php

namespace Botnetdobbs\Mpesa\Data;

class TransactionStatusCallbackData implements \Botnetdobbs\Mpesa\Contracts\TransactionStatusCallback
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

    public function getOriginatorConversationId(): string
    {
        return $this->OriginatorConversationID;
    }

    public function getTransactionStatus(): ?string
    {
        return $this->ResultParameters['TransactionStatus'] ?? null;
    }

    public function getAmount(): ?float
    {
        return $this->ResultParameters['Amount'] ?? null;
    }

    public function getReceiptNumber(): ?string
    {
        return $this->ResultParameters['ReceiptNo'] ?? null;
    }

    public function getInitiatedTime(): ?string
    {
        return $this->ResultParameters['InitiatedTime'] ?? null;
    }

    public function getFinalisedTime(): ?string
    {
        return $this->ResultParameters['FinalisedTime'] ?? null;
    }

    public function getDebitAccountType(): ?string
    {
        return $this->ResultParameters['DebitAccountType'] ?? null;
    }

    public function getDebitPartyCharges(): ?string
    {
        return $this->ResultParameters['DebitPartyCharges'] ?? null;
    }

    /**
     * @return array<int, string>|null
     */
    public function getDebitPartyNames(): ?array
    {
        $debitParty = $this->ResultParameters['DebitPartyName'] ?? null;
        return $debitParty ? (array) $debitParty : null;
    }
}
