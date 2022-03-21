<?php

namespace Krisell\LaravelSessionMigrator;

use Illuminate\Support\ServiceProvider;

class LaravelSessionMigratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'laravel-session-migrator');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-session-migrator');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // if ($this->app->runningInConsole()) {
        //     $this->publishes([
        //         __DIR__.'/../config/config.php' => config_path('laravel-session-migrator.php'),
        //     ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-session-migrator'),
            ], 'views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/laravel-session-migrator'),
            ], 'assets');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/laravel-session-migrator'),
            ], 'lang');*/

            // Registering package commands.
            // $this->commands([]);
        // }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->singleton('session', fn ($app) => new LaravelSessionMigratorManager($app));

        // Automatically apply the package configuration
        // $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-session-migrator');

        // // Register the main class to use with the facade
        // $this->app->singleton('laravel-session-migrator', function () {
        //     return new LaravelSessionMigrator;
        // });
    }
}
