<?php

namespace App\Providers;

use App\Services\Contracts\CurrencyConverterInterface;
use App\Services\SwopCurrencyConverter;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CurrencyServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(CurrencyConverterInterface::class, function ($app) {
            return new SwopCurrencyConverter(
                apiKey: config('services.swop.api_key'),
                baseUrl: config('services.swop.base_url'),
                cacheTtl: config('services.swop.cache_ttl', 3600)
            );
        }); 
    }

    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [CurrencyConverterInterface::class];
    }
}