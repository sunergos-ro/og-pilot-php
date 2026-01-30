<?php

declare(strict_types=1);

namespace Sunergos\OgPilot;

use Illuminate\Support\ServiceProvider;

class OgPilotServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/og-pilot.php', 'og-pilot');

        $this->app->singleton(OgPilot::class, function ($app) {
            OgPilot::setConfig($app['config']->get('og-pilot', []));
            return new OgPilot();
        });

        $this->app->singleton(Client::class, function ($app) {
            return OgPilot::client();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/og-pilot.php' => config_path('og-pilot.php'),
            ], 'og-pilot-config');
        }
    }
}
