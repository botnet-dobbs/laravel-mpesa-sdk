<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Tests\TestCase;

class MpesaServiceProviderTest extends TestCase
{
    public function testItResolvesContainerBindings(): void
    {
        $this->assertInstanceOf(
            \Botnetdobbs\Mpesa\Contracts\Client::class,
            app(\Botnetdobbs\Mpesa\Contracts\Client::class)
        );

        $this->assertInstanceOf(
            \Botnetdobbs\Mpesa\Contracts\CallbackHandler::class,
            app(\Botnetdobbs\Mpesa\Contracts\CallbackHandler::class)
        );

        $this->assertInstanceOf(
            \Botnetdobbs\Mpesa\Contracts\ResponseHandler::class,
            app(\Botnetdobbs\Mpesa\Contracts\ResponseHandler::class)
        );
    }

    public function testItLoadsConfig(): void
    {
        $this->assertArrayHasKey('consumer_key', config('mpesa'));
    }
}
