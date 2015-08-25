<?php

namespace Imamuseum\Harvester;

use Illuminate\Support\ServiceProvider;

class HarvesterServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/Http/routes.php';

        $this->publishes([
            __DIR__.'/../config/harvester.php' => config_path('harvester.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('/migrations'),
        ], 'migrations');

        $this->commands('Imamuseum\Harvester\Console\Commands\HarvesterCommand');
    }
}
