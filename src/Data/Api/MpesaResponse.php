<?php

namespace Botnetdobbs\Mpesa\Data\Api;

use Botnetdobbs\Mpesa\Contracts\Response as ResponseContract;
use Illuminate\Http\Client\Response;

class MpesaResponse implements ResponseContract
{
    /**
     * Constructor
     * @param Response $response
     */
    public function __construct(protected Response $response)
    {
        //
    }

    /**
     * Get complete response data
     * @return object
     *
     */
    public function getData(): object
    {
        return $this->response->object(); // @phpstan-ignore-line
    }

    /**
     * Check if response is successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return (int) $this->getResponseCode() === 0;
    }

    /**
     * Get response code
     *
     * @return string
     */
    public function getResponseCode(): string
    {
        return $this->getData()->ResponseCode; // @phpstan-ignore-line
    }

    /**
     * Get response description
     *
     * @return string
     */
    public function getResponseDescription(): string
    {
        return $this->getData()->ResponseDescription; // @phpstan-ignore-line
    }
}
