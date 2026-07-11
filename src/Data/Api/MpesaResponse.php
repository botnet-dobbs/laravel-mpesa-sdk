<?php

namespace Botnetdobbs\Mpesa\Data\Api;

use Botnetdobbs\Mpesa\Contracts\Response as ResponseContract;
use Illuminate\Http\Client\Response;

class MpesaResponse implements ResponseContract
{
    private ?object $decoded = null;

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
     */
    public function getData(): object
    {
        if ($this->decoded !== null) {
            return $this->decoded;
        }

        $data = $this->response->object();

        $this->decoded = is_object($data) ? $data : (object) [];

        return $this->decoded;
    }

    /**
     * Check if response is successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->getResponseCode() === 0;
    }

    /**
     * Get response code
     *
     * @return int
     */
    public function getResponseCode(): int
    {
        $data = $this->getData();

        if (!property_exists($data, 'ResponseCode') || $data->ResponseCode === null || $data->ResponseCode === '') {
            return 1;
        }

        return (int) $data->ResponseCode;
    }

    /**
     * Get response description
     *
     * @return string
     */
    public function getResponseDescription(): string
    {
        $data = $this->getData();

        if (!property_exists($data, 'ResponseDescription') || $data->ResponseDescription === null) {
            return '';
        }

        return (string) $data->ResponseDescription;
    }

    /**
     * Get result code
     *
     * @return int
     */
    public function getResultCode(): int
    {
        $data = $this->getData();

        if (!property_exists($data, 'ResultCode') || $data->ResultCode === null || $data->ResultCode === '') {
            return 1;
        }

        return (int) $data->ResultCode;
    }

    /**
     * Get result description
     *
     * @return string
     */
    public function getResultDescription(): string
    {
        $data = $this->getData();

        if (!property_exists($data, 'ResultDesc') || $data->ResultDesc === null) {
            return '';
        }

        return (string) $data->ResultDesc;
    }
}
