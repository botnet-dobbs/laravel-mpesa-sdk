<?php

namespace Botnetdobbs\Mpesa\Contracts;

interface TransactionResult
{
    /**
     * Get raw transaction result data as received from Mpesa
     *
     * @return object
     */
    public function getData(): object;

    /**
     * Check if transaction was successful
     *
     * @return bool
     */
    public function isSuccessful(): bool;

    /**
     * Get result code
     *
     * @return int
     */
    public function getResultCode(): int;

    /**
     * Get result type
     *
     * @return string
     */
    public function getResultType(): string;

    /**
     * Get result description
     *
     * @return string
     */
    public function getResultDescription(): string;
}
