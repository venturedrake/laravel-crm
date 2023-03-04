# Installation

[[toc]]

## Requirements

- PHP ^7.3
- MySQL 5.7+ / MariaDB 10.2.7+
- Laravel 6.0+

## Install Laravel CRM

### Require the current package using composer

```bash
composer require venturedrake/laravel-crm
```

### Publish the migrations, config & assets

```bash
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="migrations"
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="config"
php artisan vendor:publish --provider="VentureDrake\LaravelCrm\LaravelCrmServiceProvider" --tag="assets" --force
```

### Update the various config settings in the published config file

After publishing the package assets a configuration file will be located at <code>config/laravel-crm.php</code>

Please read the comments in this file for each setting. Most can be left as the default, however you will need to update the "CRM Owner" setting to access the CRM initially.

Please note if you set the route_prefix to blank or null you will need to update the default <code>routes/web.php</code> file. All the crm routes are managed by the package, so it should look just as per below after removing the default welcome route and redirecting the default /home route to the dashboard.

#### Laravel 7 and below

```php
<?php

use Illuminate\Support\Facades\Route;

Auth::routes();

Route::get('/home', function (){
    return redirect('/');
});
```

#### Laravel 8+

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return redirect('/');
})->name('dashboard');
```

### Run migrations

```bash
php artisan migrate
```

### Run database seeder

```bash
php artisan db:seed --class="VentureDrake\LaravelCrm\Database\Seeders\LaravelCrmTablesSeeder"
```

### Update User Model

- Add the HasCrmAccess, HasCrmTeams, HasRoles traits.
- Add the Lab404\AuthChecker\Models\HasLoginsAndDevices trait and the Lab404\AuthChecker\Interfaces\HasLoginsAndDevicesInterface interface.

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use VentureDrake\LaravelCrm\Traits\HasCrmAccess;
use VentureDrake\LaravelCrm\Traits\HasCrmTeams;
use Lab404\AuthChecker\Models\HasLoginsAndDevices;
use Lab404\AuthChecker\Interfaces\HasLoginsAndDevicesInterface;

class User extends Authenticatable implements HasLoginsAndDevicesInterface
{
    use HasRoles;
    use HasCrmAccess;
    use HasCrmTeams;
    use HasLoginsAndDevices;

    // ...
}
```
### Register at least one user and log in or if you already have a user login with the crm owner you set in step 5

Access the crm to register/login at http://your-project-url/crm

Note if you modified the route_prefix setting from the default the above url will change dependent on that setting.
