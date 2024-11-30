<?php

namespace Botnetdobbs\Mpesa\Data;

use Botnetdobbs\Mpesa\Contracts\AccountBalanceCallback;

class AccountBalanceCallbackData extends BaseMpesaCallbackData implements AccountBalanceCallback
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

    public function getCompletedTime(): ?string
    {
        return $this->ResultParameters['BOCompletedTime'] ?? null;
    }

    /**
     * Get all account balances
     *
     * @return array<int, array{Account: string, Currency: string, Amount: float}>
     */
    public function getAccountBalances(): array
    {
        return $this->parseBalanceString(
            $this->ResultParameters['AccountBalance'] ?? ''
        );
    }

    /**
     * Get balance for a specific account type
     *
     * @return array{Account: string, Currency: string, Amount: float}|null
     */
    public function getBalanceForAccount(string $accountName): ?array
    {
        return $this->getBalanceForAccountName(
            $this->getAccountBalances(),
            $accountName
        );
    }
}
