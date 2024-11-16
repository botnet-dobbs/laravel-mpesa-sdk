<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Contracts\ResponseHandler;
use Botnetdobbs\Mpesa\Http\Callbacks\Responses\FailedResponse;
use Botnetdobbs\Mpesa\Http\Callbacks\Responses\SuccessResponse;
use Botnetdobbs\Mpesa\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallbackResponseTest extends TestCase
{
    private ResponseHandler $responseHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responseHandler = $this->app->make(ResponseHandler::class);
    }

    public function testItCanReturnCorrectInstanceOnSuccessResponse(): void
    {
        $response = $this->responseHandler->success("Payment Successfull");
        $this->assertInstanceOf(SuccessResponse::class, $response);
    }

    public function testItCanReturnCorrectInstanceOnFailedResponse(): void
    {
        $response = $this->responseHandler->failed("Payment Failed", 500);
        $this->assertInstanceOf(FailedResponse::class, $response);
    }

    public function testItCanReturnCorrectResponseOnSuccess(): void
    {
        $response = $this->responseHandler->success("Payment Successfull")->toResponse(new Request());
        $this->assertSuccessResponse($response, "Payment Successfull");
    }

    public function testItCanReturnCorrectResponseOnSuccessWithDefaultMessage(): void
    {
        $response = $this->responseHandler->success()->toResponse(new Request());
        $this->assertSuccessResponse($response, "Success");
    }

    public function testItCanReturnCorrectResponseOnFailedWithDefaultMessage(): void
    {
        $response = $this->responseHandler->failed()->toResponse(new Request());
        $this->assertFailedResponse($response, "Failed", 500);
    }

    public function testItCanReturnCorrectResponseOnFailed(): void
    {
        $response = $this->responseHandler->failed("Payment Failed", 500)->toResponse(new Request());
        $this->assertFailedResponse($response, "Payment Failed", 500);
    }

    private function assertSuccessResponse(JsonResponse $response, string $expectedMessage): void
    {
        $this->assertEquals(200, $response->status());
        $this->assertEquals([
            'ResultCode' => 0,
            'ResultDesc' => $expectedMessage
        ], json_decode($response->content(), true));
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    private function assertFailedResponse(JsonResponse $response, string $expectedMessage, int $expectedStatus): void
    {
        $this->assertEquals($expectedStatus, $response->status());
        $this->assertEquals([
            'ResultCode' => 1,
            'ResultDesc' => $expectedMessage
        ], json_decode($response->content(), true));
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }
}
