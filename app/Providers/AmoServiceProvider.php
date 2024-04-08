<?php

namespace App\Providers;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Client\AmoCRMApiClientFactory;
use App\Services\AmoServices;
use Illuminate\Support\ServiceProvider;

class AmoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(AmoServices::class, function ($app) {
            $clientId = config('amocrm.client_id');
            $clientSecret = config('amocrm.client_secret');
            $redirectUrl = config('amocrm.redirect_url');
            return new AmoServices(new AmoCRMApiClient($clientId, $clientSecret, $redirectUrl));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
