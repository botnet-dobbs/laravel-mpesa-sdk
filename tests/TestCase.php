<?php

namespace Botnetdobbs\Mpesa\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Botnetdobbs\Mpesa\MpesaServiceProvider;
use Illuminate\Support\Facades\Http;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MpesaServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('mpesa.consumer_key', 'test_consumer_key');
        $app['config']->set('mpesa.consumer_secret', 'test_consumer_secret');
        $app['config']->set('mpesa.environment', 'sandbox');

        $app['config']->set('mpesa.endpoints.sandbox', [
            'base_url' => 'https://sandbox.safaricom.co.ke',
            'oauth_token' => '/oauth/v1/generate',
            'stk_push' => '/mpesa/stkpush/v1/processrequest',
            'stk_query' => '/mpesa/stkpushquery/v1/query',
            'b2c_payment' => '/mpesa/b2c/v1/paymentrequest',
            'b2b_payment' => '/mpesa/b2b/v1/paymentrequest',
            'c2b_register' => '/mpesa/c2b/v1/registerurl',
            'c2b_simulate' => '/mpesa/c2b/v1/simulate',
            'account_balance' => '/mpesa/accountbalance/v1/query',
            'transaction_status' => '/mpesa/transactionstatus/v1/query',
            'reversal' => '/mpesa/reversal/v1/request',
        ]);
    }

    protected function mockSuccessfulTokenGeneration(): void
    {
        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_token',
                'expires_in' => '3599'
            ], 200)
        ]);
    }

    protected function mockFailedTokenGeneration(): void
    {
        Http::fake([
            '*/oauth/v1/generate*' => Http::response([
                'errorMessage' => 'Invalid credentials'
            ], 401)
        ]);
    }

    protected function mockSuccessfulApiResponse(string $endpoint, array $response = []): void
    {
        $defaultResponse = [
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success',
        ];

        Http::fake([
            "*/$endpoint*" => Http::response(array_merge($defaultResponse, $response), 200)
        ]);
    }

    protected function mockFailedApiResponse(string $endpoint, int $statusCode = 400, array $response = []): void
    {
        $defaultResponse = [
            'errorCode' => 'Error',
            'errorMessage' => 'Failed',
        ];

        Http::fake([
            "*/$endpoint*" => Http::response(array_merge($defaultResponse, $response), $statusCode)
        ]);
    }
}
