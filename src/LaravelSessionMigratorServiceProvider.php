<?php

namespace Krisell\LaravelSessionMigrator;

use Illuminate\Support\ServiceProvider;

class LaravelSessionMigratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('session', fn ($app) => new LaravelSessionMigratorManager($app));

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'session');
    }
}
