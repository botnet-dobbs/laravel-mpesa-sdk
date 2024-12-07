<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Http\Callbacks\SuccessResponse;
use Botnetdobbs\Mpesa\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuccessResponseTest extends TestCase
{
    public function testItCanCreateJsonResponse(): void
    {
        $response = new SuccessResponse('Test message');

        $jsonResponse = $response->toResponse(new Request());

        $this->assertInstanceOf(JsonResponse::class, $jsonResponse);
    }

    public function testItCanCreateCorrectResponseFormat(): void
    {
        $response = (new SuccessResponse('Test message'))
            ->toResponse(new Request());

        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            'ResultCode' => 0,
            'ResultDesc' => 'Test message'
        ], json_decode($response->content(), true));
    }

    public function testItCanUseDefaultMessage(): void
    {
        $response = (new SuccessResponse())
            ->toResponse(new Request());

        $content = json_decode($response->content(), true);
        $this->assertEquals('Success', $content['ResultDesc']);
    }
}
