<?php

namespace Botnetdobbs\Mpesa\Validation;

use Botnetdobbs\Mpesa\Enums\MpesaRequestType;
use InvalidArgumentException;

trait RequestValidator
{
    /**
     * @param MpesaRequestType $type
     * @param array<string, mixed> $data Input parameters
     * @return void
     * @throws InvalidArgumentException
     */
    protected function validateRequestType(MpesaRequestType $type, array $data): void
    {
        $this->validateRequest($data, RequiredFields::get($type));
    }

    /**
     * @param array<string, mixed> $data Input parameters
     * @param array<string> $required Required parameter keys
     * @throws \InvalidArgumentException
     */
    private function validateRequest(array $data, array $required): void
    {
        $missing = array_filter($required, fn($param) => !isset($data[$param]));

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                "Missing required parameters: " .
                collect($missing)->join(', ', ' and ')
            );
        }
    }
}
