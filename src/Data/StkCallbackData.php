<?php

namespace Botnetdobbs\Mpesa\Data;

use Botnetdobbs\Mpesa\Contracts\StkCallback;

class StkCallbackData extends BaseMpesaCallbackData implements StkCallback
{
    public function __construct(
        public string $MerchantRequestID,
        public string $CheckoutRequestID,
        public int $ResultCode,
        public string $ResultDesc,
        public array $CallbackMetadata = []
    ) {
    }

    public function isSuccessful(): bool
    {
        return (int) $this->ResultCode === 0;
    }

    public function getAmount(): ?float
    {
        return $this->CallbackMetadata['Amount'] ?? null;
    }

    public function getReceiptNumber(): ?string
    {
        return $this->CallbackMetadata['MpesaReceiptNumber'] ?? null;
    }

    public function getTransactionDate(): ?string
    {
        return $this->CallbackMetadata['TransactionDate'] ?? null;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->CallbackMetadata['PhoneNumber'] ?? null;
    }

    public function getMerchantRequestId(): string
    {
        return $this->MerchantRequestID;
    }

    public function getCheckoutRequestId(): string
    {
        return $this->CheckoutRequestID;
    }

    public function getResultCode(): int
    {
        return $this->ResultCode;
    }

    public function getResultDescription(): string
    {
        return $this->ResultDesc;
    }
}
