<?php

namespace EpagosBridge;

use EpagosBridge\Commands\Install;
use EpagosBridge\Commands\LimpiarLogs;
use EpagosBridge\Commands\SincronizarPagos;
use Illuminate\Support\ServiceProvider;

class EpagosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $basePath = dirname(__DIR__).DIRECTORY_SEPARATOR;

        if ($this->app->runningInConsole()) {
            $this->commands([
                Install::class,
                SincronizarPagos::class,
                LimpiarLogs::class,
            ]);
        }

        $this->loadRoutesFrom($basePath . 'routes/webhook.php');
        $this->loadRoutesFrom($basePath . 'routes/api.php');
        $this->loadMigrationsFrom($basePath . 'database/migrations');
    }

    public function register(): void
    {
    }
}
