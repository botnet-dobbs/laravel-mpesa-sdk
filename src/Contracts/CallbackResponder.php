<?php

namespace Botnetdobbs\Mpesa\Contracts;

use Illuminate\Contracts\Support\Responsable;

interface CallbackResponder
{
    public function success(string $message = 'Success'): Responsable;

    public function failed(string $message = 'Failed', int $statusCode = 500): Responsable;
}
