<?php

namespace Botnetdobbs\Mpesa\Http\Callbacks\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class FailedResponse implements Responsable
{
    public function __construct(
        private readonly string $message = 'Failed',
        private readonly int $statusCode = 500
    ) {
    }

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'ResultCode' => 1,
            'ResultDesc' => $this->message
        ], $this->statusCode);
    }
}
