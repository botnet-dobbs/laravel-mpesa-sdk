<?php

namespace Botnetdobbs\Mpesa;

use Botnetdobbs\Mpesa\Contracts\Client;
use Botnetdobbs\Mpesa\Contracts\CallbackHandler;
use Botnetdobbs\Mpesa\Contracts\ResponseHandler;
use Botnetdobbs\Mpesa\Http\Callbacks\CallbackResponse;
use Botnetdobbs\Mpesa\Http\Callbacks\MpesaCallback;
use Illuminate\Support\ServiceProvider;
use Botnetdobbs\Mpesa\Http\MpesaClient;

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
        ], 'config');
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

        $this->app->bind(Client::class, function ($app) {
            return new MpesaClient(
                config('mpesa.consumer_key'),
                config('mpesa.consumer_secret'),
                config('mpesa.environment'),
            );
        });

        $this->app->bind(CallbackHandler::class, MpesaCallback::class);
        $this->app->bind(ResponseHandler::class, CallbackResponse::class);
    }
}
