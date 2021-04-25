# Laravel CRM

[![Latest Version on Packagist](https://img.shields.io/packagist/v/venturedrake/laravel-crm.svg?style=flat-square)](https://packagist.org/packages/venturedrake/laravel-crm)
[![Build Status](https://travis-ci.com/venturedrake/laravel-crm.svg?branch=master)](https://travis-ci.com/venturedrake/laravel-crm)
[![StyleCI](https://github.styleci.io/repos/291847143/shield?branch=master)](https://github.styleci.io/repos/291847143?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/1946e83f51de4a0eb430a8e0a1aab3cf)](https://app.codacy.com/gh/venturedrake/laravel-crm?utm_source=github.com&utm_medium=referral&utm_content=venturedrake/laravel-crm&utm_campaign=Badge_Grade_Settings)
[![Total Downloads](https://img.shields.io/packagist/dt/venturedrake/laravel-crm.svg?style=flat-square)](https://packagist.org/packages/venturedrake/laravel-crm)

This package will add CRM functionality to your laravel projects.

> ⚠️ Warning: This is a pre-release version that is not yet ready for production use.

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

## Installation (10-15mins)

Step 1. Install a Laravel project if you don't have one already

https://laravel.com/docs/6.x#installation

Step 2. Make sure you have set up Laravel auth in your project

https://laravel.com/docs/6.x/authentication

Step 3. Install spatie roles & permissions package

https://spatie.be/docs/laravel-permission/v4/installation-laravel

Step 4. Require the current package using composer:

```bash
composer require venturedrake/laravel-crm:^0.2
```

Step 5. Publish the migrations, config & assets

```bash
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="config"
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="assets"
```

Step 6. Update the various config settings in the published config file:

After publishing the package assets a configuration file will be located at <code>config/laravel-crm.php</code>

Please read the comments in this file for each setting. Most can be left as the default, however you will need to update the "CRM Owner" setting to access the CRM initially. 
    
Please note if you set the route_prefix to blank or null you will need to update some of your default Laravel routes in the <code>routes/web.php</code> file as per below.

```php
/* Redirect the main route to the login page  */
Route::get('/', function () {
    return redirect('login');
});

/* Redirect the default home route to the dashboard */
Route::get('/home', function (){
    return redirect('dashboard');
});
```

Step 7. Run migrations:

```bash
php artisan migrate
```

Step 8. Run database seeder:

```bash
php artisan db:seed --class="VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmTablesSeeder"
```

Step 9. Add the HasCrmAccess, HasCrmTeams & HasRoles traits to your User model(s):

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use VentureDrake\LaravelCrm\Traits\HasCrmAccess;
use VentureDrake\LaravelCrm\Traits\HasCrmTeams;

class User extends Authenticatable
{
    use HasRoles;
    use HasCrmAccess;
    use HasCrmTeams;

    // ...
}
```
Step 10. Register at least one user and log in or if you already have a user login with the crm owner you set in step 5

Access the crm to register/login at http://your-project-url/crm

Note if you modified the route_prefix setting from the default the above url will change dependent on that setting.

## Upgrade

### Upgrading from 0.1 to 0.2

Step 1. Run the following to the update package, database and add the default roles/permissions:

```bash
composer require venturedrake/laravel-crm::^0.2
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="migrations"
php artisan migrate
php artisan db:seed --class="VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmTablesSeeder"
```

Step 2. Delete previously published package views located in <code>resources/views/vendor/laravel-crm/*</code>

Step 3. Add HasCrmAccess, HasCrmTeams & HasRoles traits to App\User model, see installation Step 8.

Step 4. Install and set up spatie roles & permissions package, see install step 3.

### Upgrading from 0.1.x to 0.1.2

Step 1. Run the following to update the package & database

```bash
composer require venturedrake/laravel-crm::^0.1
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="migrations"
php artisan migrate
```

Step 2. Delete previously published package views located in folder <code>resources/views/vendor/laravel-crm/*</code>

<!--- ## Usage --->

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