<?php

namespace Botnetdobbs\Mpesa\Contracts;

use Illuminate\Contracts\Support\Responsable;

interface CallbackResponder
{
    public function success(string $message = 'Accepted'): Responsable;

    public function failed(string $message = 'Rejected', int $statusCode = 500): Responsable;
}
