# Laravel CRM

[![Latest Version on Packagist](https://img.shields.io/packagist/v/venturedrake/laravel-crm.svg?style=flat-square)](https://packagist.org/packages/venturedrake/laravel-crm)
[![Build Status](https://travis-ci.com/venturedrake/laravel-crm.svg?branch=master)](https://travis-ci.com/venturedrake/laravel-crm)
[![StyleCI](https://github.styleci.io/repos/291847143/shield?branch=master)](https://github.styleci.io/repos/291847143?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/1946e83f51de4a0eb430a8e0a1aab3cf)](https://app.codacy.com/gh/venturedrake/laravel-crm?utm_source=github.com&utm_medium=referral&utm_content=venturedrake/laravel-crm&utm_campaign=Badge_Grade_Settings)
[![Total Downloads](https://img.shields.io/packagist/dt/venturedrake/laravel-crm.svg?style=flat-square)](https://packagist.org/packages/venturedrake/laravel-crm)

This package will add CRM functionality to your laravel projects.

## Use Cases

- Use as a free CRM for your business or your clients
- Build a custom CRM for your business or your clients
- Use as an integrated CRM for your Laravel powered business (Saas, E-commerce, etc)
- Use as a CRM for your Laravel development business

## Features

 - Sales leads management
 - Deal management
 - Contact database management
 - Users & Teams
 - Secure registration & login
 - Reset forgotten password

## Installation

Step 1: Install a Laravel project if you don't have one already

https://laravel.com/docs/6.x#installation

Step 2: Make sure you have set up Laravel auth in your project

https://laravel.com/docs/6.x/authentication

Step 3: Require the package using composer:

```bash
composer require venturedrake/laravel-crm
```

Step 4: Publish the migrations & config:

```bash
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="migrations,config"
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="config"
```

Step 5: Run migrations:

```bash
php artisan migrate
```

Step 6: Run database seeder:

```bash
php artisan db:seed --class="VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmTablesSeeder"

```

Step 7: Add an email address for the user who will be the crm owner in the config file:

After publishing the package assets a configuration file will be located at <code>config/laravel-crm.php</code>

```php

return [
    
    'crm_owner' => 'email@domain.com',
    
    'route_prefix' => 'crm',
    
    'route_middleware' => ['web'],
    
    'db_table_prefix' => 'crm_',
    
    'encrypt_db_fields' => true,
    
];

```

## Usage

Access the crm at http://your-project-url/crm

## Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Roadmap

 - Products
 - Notes
 - Tasks
 - Files / Documents
 - Calendar (Calls, Meetings, Reminders)
 - Roles / Permissions
 - Dashboard
 - Custom Fields
 - Activity Feed / Timelines
 - CSV Import / Export

## Feedback

Participate in the [discord community](https://discord.gg/rygVyyGSHj)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email andrew@venturedrake.com instead of using the issue tracker.

## Credits

- [Andrew Drake](https://github.com/venturedrake)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.