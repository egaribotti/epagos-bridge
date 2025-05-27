<?php

namespace EpagosBridge;

use Illuminate\Support\ServiceProvider;

class EpagosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $basePath = dirname(__DIR__).DIRECTORY_SEPARATOR;

        if ($this->app->runningInConsole()) {
            require_once $basePath . 'routes/console.php';
        }

        $this->loadRoutesFrom($basePath . 'routes/web.php');
        $this->loadMigrationsFrom($basePath . 'database/migrations');
    }

    public function register(): void
    {
    }
}
