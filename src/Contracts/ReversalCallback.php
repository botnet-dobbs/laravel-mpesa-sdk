<?php

namespace Botnetdobbs\Mpesa\Contracts;

interface ReversalCallback
{
    public function isSuccessful(): bool;

    public function getAmount(): ?float;

    public function getOriginatorConversationId(): string;

    public function getConversationId(): string;

    public function getTransactionId(): string;

    public function getResultCode(): int;

    public function getResultType(): int;

    public function getResultDescription(): string;

    public function getOriginalTransactionId(): ?string;

    public function getTransactionCompletedTime(): ?string;

    public function getCharge(): ?float;

    public function getCreditPartyPublicName(): ?string;

    public function getDebitPartyPublicName(): ?string;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getDebitAccountBalances(): array;

    /**
     * @param string $account
     *
     * @return array{currency: string, amount: float}|null
     */
    public function getDebitAccountBalance(string $account): array;
}
