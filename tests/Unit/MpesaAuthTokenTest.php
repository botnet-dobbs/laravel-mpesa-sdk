<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Http\MpesaAuthToken;
use Botnetdobbs\Mpesa\Tests\TestCase;
use Carbon\Carbon;

class MpesaAuthTokenTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    public function testItCanCreateTokenWithExpiryTime(): void
    {
        $token = 'test-token';
        $expiresIn = 3600;

        $authToken = new MpesaAuthToken($token, $expiresIn);

        $this->assertEquals($token, $authToken->getToken());
    }

    public function testItCanBeActiveWhenTokenIsNotExpired(): void
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        $authToken = new MpesaAuthToken('test-token', 3600); // 1 hour

        $this->assertTrue($authToken->isActive());

        Carbon::setTestNow('2024-01-01 12:59:57'); // 3 seconds before expiry
        $this->assertTrue($authToken->isActive());
    }

    public function testItCanNotBeActiveWhenTokenIsExpired(): void
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        $authToken = new MpesaAuthToken('test-token', 3600); // 1 hour

        Carbon::setTestNow('2024-01-01 12:59:58'); // 2 seconds before expiry
        $this->assertFalse($authToken->isActive());
    }

    public function testItCanSubtractThreeSecondsFromExpiryTime(): void
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        $authToken = new MpesaAuthToken('test-token', 10); // 10 seconds

        Carbon::setTestNow('2024-01-01 12:00:07'); // 3 seconds before expiry
        $this->assertTrue($authToken->isActive());

        Carbon::setTestNow('2024-01-01 12:00:08'); // 2 seconds before expiry
        $this->assertFalse($authToken->isActive());
    }

    public function testItCanHandleVeryShortExpiryTimes(): void
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        $authToken = new MpesaAuthToken('test-token', 4); // 4 seconds

        $this->assertTrue($authToken->isActive());

        Carbon::setTestNow('2024-01-01 12:00:01'); // 3 seconds before expiry
        $this->assertTrue($authToken->isActive());

        Carbon::setTestNow('2024-01-01 12:00:02'); // 2 seconds before expiry
        $this->assertFalse($authToken->isActive());
    }

    public function testItCanHandleLongExpiryTimes(): void
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        $authToken = new MpesaAuthToken('test-token', 86400); // 24 hours
        $this->assertTrue($authToken->isActive());

        Carbon::setTestNow('2024-01-02 11:00:00'); // 1 hour before expiry
        $this->assertTrue($authToken->isActive());

        Carbon::setTestNow('2024-01-02 12:00:00'); // expiry time
        $this->assertFalse($authToken->isActive());
    }
}
