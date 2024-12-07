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
     * @return string
     */
    public function getResponseCode(): string;

    /**
     * Get response description
     * @return string
     */
    public function getResponseDescription(): string;
}
