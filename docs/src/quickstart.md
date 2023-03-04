# Quick Start

If you want to get up and running quickly to review the Laravel CRM package, this section is for you.

[[toc]]

## Starter Project

Laravel CRM provides a starter project to get you up and running with your CRM.

If you would prefer to install the Laravel CRM package into your own Laravel application, please follow the [installation instructions](/installation).


## Requirements

- PHP ^7.3
- MySQL 5.7+ / MariaDB 10.2.7+

## Installation

### Clone the repo

```bash
git clone --depth=1 https://github.com/venturedrake/laravel-crm-starter.git
```

This will create a shallow clone of the repo, from there you would just need to remove the `.git` folder and reinitialise it to make it your own.

Then install composer dependencies

```bash
composer install
```

### Configure the Laravel app

Copy the `.env.example` file to `.env` and make sure the details match to your install.

```shell
cp .env.example .env
```

All the relevant configuration files should be present in the repo.

### Generate application key and link storage

Generate the application key

```
php artisan key:generate
```

Link the storage directory

```
php artisan storage:link
```

### Run the Laravel CRM package installer

```
php artisan laravelcrm:install
```

## Finished 🚀

You now should have a working project installation of Laravel CRM. 

- You can access the crm at `http://<yoursite>`

You can review the source code at the GitHub Repository: [https://github.com/venturedrake/laravel-crm-starter](https://github.com/venturedrake/laravel-crm-starter)