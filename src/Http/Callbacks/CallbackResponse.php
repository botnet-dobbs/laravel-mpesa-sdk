<?php

namespace Botnetdobbs\Mpesa\Http\Callbacks;

use Botnetdobbs\Mpesa\Contracts\CallbackResponder;
use Illuminate\Contracts\Support\Responsable;

class CallbackResponse implements CallbackResponder
{
    public function success(string $message = 'Success'): Responsable
    {
        return new SuccessResponse($message);
    }

    public function failed(string $message = 'Failed', int $statusCode = 500): Responsable
    {
        return new FailedResponse($message, $statusCode);
    }
}
