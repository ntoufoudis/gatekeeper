<?php

namespace Ntoufoudis\Gatekeeper;

use Illuminate\Support\ServiceProvider;

class GatekeeperServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/gatekeeper.php', 'gatekeeper');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'../config/gatekeeper.php' => config_path('gatekeeper.php'),
        ], 'gatekeeper-config');

        $this->publishes([
            __DIR__.'../database/migrations/' => database_path('migrations'),
        ], 'gatekeeper-migrations');
    }
}
