<?php

namespace Botnetdobbs\Mpesa\Data;

use Botnetdobbs\Mpesa\Contracts\ReversalCallback;

class ReversalCallbackData extends BaseMpesaCallbackData implements ReversalCallback
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

    public function getAmount(): ?float
    {
        return $this->ResultParameters['Amount'] ?? null;
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

    public function getOriginalTransactionID(): ?string
    {
        return $this->ResultParameters['OriginalTransactionID'] ?? null;
    }

    public function getTransactionCompletedTime(): ?string
    {
        return $this->ResultParameters['TransCompletedTime'] ?? null;
    }

    public function getCharge(): ?float
    {
        return $this->ResultParameters['Charge'] ?? null;
    }

    public function getCreditPartyPublicName(): ?string
    {
        return $this->ResultParameters['CreditPartyPublicName'] ?? null;
    }

    public function getDebitPartyPublicName(): ?string
    {
        return $this->ResultParameters['DebitPartyPublicName'] ?? null;
    }

    /**
     * @return array<int, array{Account: string, Currency: string, Amount: float}>
     */
    public function getDebitAccountBalances(): array
    {
        return $this->parseBalanceString(
            $this->ResultParameters['DebitAccountBalance'] ?? ''
        );
    }

    /**
     * @param string $account
     *
     * @return array{Account: string, Currency: string, Amount: float}|null
     */
    public function getDebitAccountBalance(string $account): ?array
    {
        return $this->getBalanceForAccountName(
            $this->getDebitAccountBalances(),
            $account
        );
    }
}
