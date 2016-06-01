##Harvester Package

###Composer Setup
```json
    "require": {
        "imamuseum/harvester": "^2.0"
    },
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

### Artisan Commands
```sh
php artisan harvest:collection
php artisan harvest:object
php artisan harvest:maintain
```
Use the --help flag after any command to view the available options with a description.

### License

The Laravel framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
