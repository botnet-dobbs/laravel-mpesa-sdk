<?php

namespace Botnetdobbs\Mpesa\Exceptions;

use Throwable;

class MpesaException extends \Exception
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly mixed $body = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
