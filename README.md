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

## Laravel Specific

### Service Provider
In `config\app.php` add to the autoloaded providers -
```php
Imamuseum\Harvester\HarvesterServiceProvider::class,
```

```sh
php artisan vendor:publish