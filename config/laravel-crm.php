<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CRM Owner
    |--------------------------------------------------------------------------
    |
    | This value relates to the primary owner for the crm. It must be set as
    | the email address for the user registered in the users table so that you
    | can access the crm initially. You will need to register this user after
    | the crm is installed if not already.
    |
    */
    
    'crm_owner' => env('LARAVEL_CRM_OWNER', ''),

    /*
    |--------------------------------------------------------------------------
    | Teams Support
    |--------------------------------------------------------------------------
    |
    | This value relates to the "teams" feature in Laravel Jetstream or Spark.
    | Only set this to true if you are using this feature as it will break
    | your installation if not. It basically allows you to run a multi-tenant
    | crm, and the teams can be different "accounts". You can switch between
    | different teams/accounts and have different users, contacts, leads, etc
    | in each account.
    |
    | PLEASE NOTE! This has nothing to do with the user teams feature within
    | the crm itself, which is simply a way of grouping users within the crm.
    |
    | For Jetstream see https://jetstream.laravel.com/2.x/features/teams.html
    |
    | For Spark Classic see https://spark-classic.laravel.com/docs/11.0/teams
    |
    | IMPORTANT! This package uses the Spatie Permissions package which as of
    | version 5 supports teams. PLease check you have version 5 or higher
    | installed and follow this additional step when installing the package:
    | https://spatie.be/docs/laravel-permission/v5/basic-usage/teams-permissions
    |
    */

    'teams' => env('LARAVEL_CRM_TEAMS', false),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | These are some default settings. They are also used each time a user
    | creates a new team when team support is enabled above.
    |
    */
    
    'currency' => env('LARAVEL_CRM_CURRENCY', 'USD'),
    
    'country' => env('LARAVEL_CRM_COUNTRY', 'United States'),
    
    'language' => env('LARAVEL_CRM_LANGUAGE', 'english'),

    /*
    |--------------------------------------------------------------------------
    | Route Subdomain
    |--------------------------------------------------------------------------
    |
    | This value is used to define whether you wish to use the crm on a subdomain
    |
    | eg. https://subdomain.yourdomain.com
    |
    |
    */

    'route_subdomain' => env('LARAVEL_CRM_ROUTE_SUBDOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | This value is used to define whether you wish to use the crm in a
    | subfolder on your domain or just in the main directory. You can change
    | this value to anything you wish or simply set to blank.
    |
    | eg. https://yourdomain.com/crm or https://yourdomain.com
    |
    | Tip: You would use a subfolder if you are using this crm within a
    | current Laravel project that might be an entire application with routes
    | controllers, models, views, etc.
    |
    */
    
    'route_prefix' => env('LARAVEL_CRM_ROUTE_PREFIX', 'crm'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | For any custom middleware you have developed to be added to the crm routes
    |
    */
    
    'route_middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Database Table Prefix
    |--------------------------------------------------------------------------
    |
    | The crm tables will be prefixed with this value. It is optional but if you
    | are installing the crm into a current Laravel project it is best to leave
    | as the default "crm" to avoid any possible table name conflicts.
    |
    */
    
    'db_table_prefix' => env('LARAVEL_CRM_DB_TABLE_PREFIX', 'crm_'),

    /*
    |--------------------------------------------------------------------------
    | Database Table Field Encryption
    |--------------------------------------------------------------------------
    |
    | This is a security feature that will encrypt personal information in
    | certain database table fields as an added layer of privacy protection.
    |
    */
    
    'encrypt_db_fields' => env('LARAVEL_CRM_ENCRYPT_DB_FIELDS', false),
    
];
