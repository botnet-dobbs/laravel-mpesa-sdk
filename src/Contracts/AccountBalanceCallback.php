<?php

namespace Botnetdobbs\Mpesa\Contracts;

interface AccountBalanceCallback
{
    public function isSuccessful(): bool;

    public function getOriginatorConversationId(): string;

    public function getConversationId(): string;

    public function getTransactionId(): string;

    public function getResultCode(): int;

    public function getResultType(): int;

    public function getResultDescription(): string;

    public function getCompletedTime(): ?string;


    /**
     * Get all account balances
     *
     * @return array<string, array{currency: string, amount: float}>
     */
    public function getAccountBalances(): array;

    /**
     * Get balance for a specific account type
     *
     * @return array{currency: string, amount: float}|null
     */
    public function getBalanceForAccount(string $accountName): ?array;
}
