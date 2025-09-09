<?php

namespace App\Providers;

use InfluxDB2\Client;
use InfluxDB2\Service\HealthService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class InfluxDBServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['services.influxdb'];
            
            return new Client([
                'url' => $config['url'],
                'token' => $config['token'],
                'bucket' => $config['bucket'],
                'org' => $config['org'],
                'precision' => \InfluxDB2\Model\WritePrecision::S,
            ]);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\CheckInfluxDBConnection::class,
            ]);
        }
    }

    public function provides(): array
    {
        return [Client::class];
    }
}