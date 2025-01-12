<?php

namespace BluedotBd\LaravelBulksms;

use Illuminate\Support\ServiceProvider;

class LaravelBulksmsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton('laravel-bulksms', function () {
            return new LaravelBulksms();
        });
    }
}
