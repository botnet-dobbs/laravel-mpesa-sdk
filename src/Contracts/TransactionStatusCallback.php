<?php

namespace Botnetdobbs\Mpesa\Contracts;

interface TransactionStatusCallback
{
    public function isSuccessful(): bool;

    public function getTransactionStatus(): ?string;

    public function getAmount(): ?float;

    public function getReceiptNumber(): ?string;

    public function getOriginatorConversationId(): string;

    public function getConversationId(): string;

    public function getTransactionId(): string;

    public function getResultCode(): int;

    public function getResultType(): int;

    public function getResultDescription(): string;

    public function getInitiatedTime(): ?string;

    public function getFinalisedTime(): ?string;

    public function getDebitAccountType(): ?string;

    public function getDebitPartyCharges(): ?string;

    /**
     * @return array<int, string>|null
     */
    public function getDebitPartyNames(): ?array;
}
