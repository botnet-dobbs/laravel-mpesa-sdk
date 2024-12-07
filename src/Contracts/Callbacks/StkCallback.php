<?php

namespace Botnetdobbs\Mpesa\Contracts\Callbacks;

interface StkCallback
{
    public function isSuccessful(): bool;

    public function getAmount(): ?float;

    public function getReceiptNumber(): ?string;

    public function getTransactionDate(): ?string;

    public function getPhoneNumber(): ?string;

    public function getMerchantRequestId(): string;

    public function getCheckoutRequestId(): string;

    public function getResultCode(): int;

    public function getResultDescription(): string;
}
