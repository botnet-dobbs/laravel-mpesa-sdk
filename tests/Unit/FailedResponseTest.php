<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Http\Callbacks\FailedResponse;
use Botnetdobbs\Mpesa\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FailedResponseTest extends TestCase
{
    public function testItCanCreateJsonResponse(): void
    {
        $response = new FailedResponse('Test message');

        $jsonResponse = $response->toResponse(new Request());

        $this->assertInstanceOf(JsonResponse::class, $jsonResponse);
    }

    public function testItCanCreateCorrectResponseFormat(): void
    {
        $response = (new FailedResponse('Test message'))
            ->toResponse(new Request());

        $this->assertEquals(500, $response->status());
        $this->assertEquals([
            'ResultCode' => 1,
            'ResultDesc' => 'Test message'
        ], json_decode($response->content(), true));
    }

    public function testItCanUseDefaultMessage(): void
    {
        $response = (new FailedResponse())
            ->toResponse(new Request());

        $content = json_decode($response->content(), true);
        $this->assertEquals('Failed', $content['ResultDesc']);
    }
}
