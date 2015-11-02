##Harvester Package

###install via composer
```json
{
    "require": {
        "imamuseum/harvester": "dev-master@dev"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://bitbucket.org/imalab/harvester.git"
        }
    ]
}
```

### Service Provider
In `config/app.php` add to the autoloaded providers -
```php
Imamuseum\Harvester\HarvesterServiceProvider::class,
```

Add ExampleHarvester to `app/Providers/AppServiceProvider.php` to implement the HarvesterInterface.
```php
    public function register()
    {
        $this->app->bind('Imamuseum\Harvester\Contracts\HarvesterInterface',
            'Imamuseum\Harvester\ExampleHarvester');
    }
```

Now you can publish the package -
```sh
php artisan vendor:publish

```

Run Migrations -
```sh 
php artisan migrate
```

Run an initial sync with fake data -
```sh
php artisan harvest:collection --initial
```

Push items off the queue -
```sh
php artisan queue:listen
```