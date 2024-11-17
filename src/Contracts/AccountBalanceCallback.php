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
     * @return array<int, array{Account: string, Currency: string, Amount: float}>
     */
    public function getAccountBalances(): array;

    /**
     * @return array{Account: string, Currency: string, Amount: float}|null
     */
    public function getBalanceForAccount(string $accountName): ?array;
}
