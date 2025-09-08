<?php

namespace Tests\Providers;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Force array cache driver for tests
        config(['cache.default' => 'array']);
    }

    public function boot(): void
    {
        //
    }
}