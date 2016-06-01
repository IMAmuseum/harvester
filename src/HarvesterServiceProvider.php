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
        $this->publishes([
            __DIR__.'/../config/harvester.php' => config_path('harvester.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('/migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__.'/stubs/Models/Actor.php' => app_path('Actor.php'),
            __DIR__.'/stubs/Models/Asset.php' => app_path('Asset.php'),
            __DIR__.'/stubs/Models/Date.php' => app_path('Date.php'),
            __DIR__.'/stubs/Models/Deaccession.php' => app_path('Deaccession.php'),
            __DIR__.'/stubs/Models/Location.php' => app_path('Location.php'),
            __DIR__.'/stubs/Models/Object.php' => app_path('Object.php'),
            __DIR__.'/stubs/Models/Source.php' => app_path('Source.php'),
            __DIR__.'/stubs/Models/Term.php' => app_path('Term.php'),
            __DIR__.'/stubs/Models/Text.php' => app_path('Text.php'),
        ], 'models');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            'Imamuseum\Harvester\Console\Commands\HarvestCollectionCommand',
            'Imamuseum\Harvester\Console\Commands\HarvestObjectCommand',
            'Imamuseum\Harvester\Console\Commands\HarvestMaintainCommand'
        ]);
    }
}
