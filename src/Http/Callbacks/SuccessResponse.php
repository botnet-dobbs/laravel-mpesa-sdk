<?php

namespace Botnetdobbs\Mpesa\Http\Callbacks;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class SuccessResponse implements Responsable
{
    public function __construct(
        private readonly string $message = 'Success'
    ) {
    }

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => $this->message
        ]);
    }
}
