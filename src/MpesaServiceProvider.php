<?php

namespace Botnetdobbs\Mpesa;

use Botnetdobbs\Mpesa\Contracts\Client;
use Botnetdobbs\Mpesa\Contracts\CallbackHandler;
use Botnetdobbs\Mpesa\Contracts\ResponseHandler;
use Botnetdobbs\Mpesa\Http\Callbacks\CallbackResponse;
use Botnetdobbs\Mpesa\Http\Callbacks\MpesaCallback;
use Illuminate\Support\ServiceProvider;
use Botnetdobbs\Mpesa\Http\MpesaClient;
use Botnetdobbs\Mpesa\Services\InitiatorCredentialGenerator;

class MpesaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/mpesa.php' => config_path('mpesa.php'),
        ], 'mpesa-config');

        $endpointsConfig = require __DIR__ . '/config/mpesa-endpoints.php';
        config()->set('mpesa.endpoints', $endpointsConfig);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/mpesa.php',
            'mpesa'
        );

        $this->app->singleton(InitiatorCredentialGenerator::class, function ($app) {
            return new InitiatorCredentialGenerator($app['cache']);
        });

        $this->app->bind(Client::class, function ($app) {
            return new MpesaClient(
                (string) config('mpesa.consumer_key'), // @phpstan-ignore-line
                (string) config('mpesa.consumer_secret'), // @phpstan-ignore-line
                (string) config('mpesa.environment'), // @phpstan-ignore-line
                $app['cache'],
                $app->make(InitiatorCredentialGenerator::class)
            );
        });

        $this->app->bind(CallbackHandler::class, MpesaCallback::class);
        $this->app->bind(ResponseHandler::class, CallbackResponse::class);
    }
}
