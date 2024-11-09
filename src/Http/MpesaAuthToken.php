<?php

namespace Botnetdobbs\Mpesa\Http;

use Carbon\Carbon;

class MpesaAuthToken
{
    private Carbon $expiresAt;
    private const SAFETY_MARGIN = 3;

    /**
     * @param string $token
     * @param int $expiresIn
     */
    public function __construct(private string $token, int $expiresIn)
    {
        $this->expiresAt = Carbon::now()->addSeconds($expiresIn - self::SAFETY_MARGIN);
    }

    /**
     * Get the token string
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Check if the token is still active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return ! $this->expiresAt->isPast();
    }
}
