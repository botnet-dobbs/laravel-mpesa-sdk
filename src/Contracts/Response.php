<?php

namespace Botnetdobbs\Mpesa\Contracts;

interface Response
{
    /**
     * Get complete response data
     * @return object
     */
    public function getData(): object;

    /**
     * Check if response is successful
     * @return bool
     */
    public function isSuccessful(): bool;

    /**
     * Get response code
     * @return int
     */
    public function getResponseCode(): int;

    /**
     * Get response description
     * @return string
     */
    public function getResponseDescription(): string;

    /**
     * Get result code
     * @return int
     */
    public function getResultCode(): int;

    /**
     * Get result description
     * @return string
     */
    public function getResultDescription(): string;
}
