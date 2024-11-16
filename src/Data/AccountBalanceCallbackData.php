<?php

namespace Botnetdobbs\Mpesa\Data;

class AccountBalanceCallbackData implements \Botnetdobbs\Mpesa\Contracts\AccountBalanceCallback
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
     * @return array<string, array{currency: string, amount: float}>
     */
    public function getAccountBalances(): array
    {
        $balanceString = $this->ResultParameters['AccountBalance'] ?? null;
        if (!$balanceString) {
            return [];
        }

        $accounts = explode('&', $balanceString);
        $balances = [];

        foreach ($accounts as $account) {
            [$name, $currency, $amount] = explode('|', $account);
            $balances[$name] = [
                'currency' => $currency,
                'amount' => (float) $amount
            ];
        }

        return $balances;
    }

    /**
     * Get balance for a specific account type
     *
     * @return array{currency: string, amount: float}|null
     */
    public function getBalanceForAccount(string $accountName): ?array
    {
        $balances = $this->getAccountBalances();
        return $balances[$accountName] ?? null;
    }
}
