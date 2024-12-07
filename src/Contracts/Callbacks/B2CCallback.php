<?php

namespace Botnetdobbs\Mpesa\Contracts\Callbacks;

interface B2CCallback
{
    public function isSuccessful(): bool;

    public function getTransactionAmount(): ?float;

    public function getTransactionReceipt(): ?string;

    public function getOriginatorConversationId(): string;

    public function getB2CRecipientIsRegisteredCustomer(): ?string;

    public function getConversationId(): string;

    public function getTransactionId(): string;

    public function getResultCode(): int;

    public function getResultType(): int;

    public function getResultDescription(): string;

    public function getReceiverPartyPublicName(): ?string;

    public function getTransactionCompletedDateTime(): ?string;

    public function getB2CUtilityAccountAvailableFunds(): ?float;

    public function getB2CWorkingAccountAvailableFunds(): ?float;

    public function getB2CChargesPaidAccountAvailableFunds(): ?float;
}
