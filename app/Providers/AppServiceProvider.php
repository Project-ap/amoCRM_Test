<?php

namespace App\Providers;

use AmoCRM\Client\AmoCRMApiClientFactory;
use App\Services\AmoServices;
use App\Services\ProfitCalculationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProfitCalculationService::class, function ($app) {
            return new ProfitCalculationService($app->make(AmoServices::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
