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
            \Botnetdobbs\Mpesa\Contracts\CallbackProcessor::class,
            app(\Botnetdobbs\Mpesa\Contracts\CallbackProcessor::class)
        );

        $this->assertInstanceOf(
            \Botnetdobbs\Mpesa\Contracts\CallbackResponder::class,
            app(\Botnetdobbs\Mpesa\Contracts\CallbackResponder::class)
        );
    }

    public function testItLoadsConfig(): void
    {
        $this->assertArrayHasKey('consumer_key', config('mpesa'));
    }
}
