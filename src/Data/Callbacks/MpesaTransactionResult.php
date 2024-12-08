<?php

namespace Botnetdobbs\Mpesa\Data\Callbacks;

use Botnetdobbs\Mpesa\Contracts\TransactionResult;
use Illuminate\Support\Arr;

class MpesaTransactionResult implements TransactionResult
{
    public function __construct(
        private readonly array $data
    ) {
    }

    /**
     * Get raw transaction result data as received from Mpesa
     *
     * @return object
     */
    public function getData(): object
    {
        return json_decode(json_encode($this->data)); // @phpstan-ignore-line
    }

    /**
     * Check if transaction was successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        if (isset($this->data['Body']['stkCallback'])) {
            return (int) ($this->data['Body']['stkCallback']['ResultCode'] ?? 1) === 0;
        }

        if (isset($this->data['Result'])) {
            return (int) ($this->data['Result']['ResultCode'] ?? 1) === 0;
        }

        return false;
    }

    /**
     * Get result code
     *
     * @return int
     */
    public function getResultCode(): int
    {
        if (isset($this->data['Body']['stkCallback'])) {
            return (int) Arr::get($this->data, 'Body.stkCallback.ResultCode', 1);
        }

        if (isset($this->data['Result'])) {
            return (int) Arr::get($this->data, 'Result.ResultCode', 1);
        }

        return 1;
    }

    /**
     * Get result description
     *
     * @return string
     */
    public function getResultDescription(): string
    {
        if (isset($this->data['Body']['stkCallback'])) {
            return $this->data['Body']['stkCallback']['ResultDesc'] ?? '';
        }

        if (isset($this->data['Result'])) {
            return $this->data['Result']['ResultDesc'] ?? '';
        }

        return '';
    }
}
