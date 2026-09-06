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
    | Host Team Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model in the host application that represents a team the
    | user can switch to (typically Jetstream or a starter kit's App\Models\Team).
    | When set, the "+ New team" link in the CRM header uses this model to
    | create the team, then switches the user's current team to it.
    | Leave null to auto-detect via the user's ownedTeams() relationship.
    |
    */

    'host_team_model' => env('LARAVEL_CRM_HOST_TEAM_MODEL'),

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

    'timezone' => env('LARAVEL_CRM_TIMEZONE', 'UTC'),

    'date_format' => env('LARAVEL_CRM_DATE_FORMAT', 'Y-m-d'),

    'time_format' => env('LARAVEL_CRM_TIME_FORMAT', 'g:i A'),

    'tax_name' => env('LARAVEL_CRM_TAX_NAME', 'Tax'),

    'tax_rate' => env('LARAVEL_CRM_TAX_RATE', null),

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

    /*
    |--------------------------------------------------------------------------
    | Front-end / User Interface
    |--------------------------------------------------------------------------
    |
    | The crm packages comes with a ready to go user interface. If you want to
    | build your own it is recommended to disable this to avoid any conflicts
    | or users coming across the default views. This works by not allowing
    | anything that hits the "laravel-crm" route prefix to load, which also
    | will override the route_prefix you set above.
    |
    */

    'user_interface' => env('LARAVEL_CRM_USER_INTERFACE', true),

    /*
    |--------------------------------------------------------------------------
    | Optional Modules
    |--------------------------------------------------------------------------
    |
    | Some of the features of the CRM package be disabled by removing them here
    | if they are not necessary for the business. An example of the deliveries
    | model which would not be useful if you sell digital only products or
    | services.
    |
    | Modules: "leads", "deals", "quotes", "orders", "invoices", "deliveries"
    |
    */

    'modules' => [
        'leads',
        'deals',
        'quotes',
        'orders',
        'invoices',
        'deliveries',
        'purchase-orders',
        'teams',
        'chat',
        'email-marketing',
        'sms-marketing',
        'features',
        'monitoring',
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | Defaults for the uptime / SSL monitoring module. These values are used
    | by the MonitorService when creating monitors without explicit values and
    | by the MonitorCheckService / RunMonitorCheck job when scheduling and
    | evaluating checks.
    |
    */

    'monitoring' => [
        'default_frequency_minutes' => env('LARAVEL_CRM_MONITORING_DEFAULT_FREQUENCY_MINUTES', 5),
        'default_ssl_days_before_expiry_alert' => env('LARAVEL_CRM_MONITORING_DEFAULT_SSL_DAYS_BEFORE_EXPIRY_ALERT', 14),
        'request_timeout_seconds' => env('LARAVEL_CRM_MONITORING_REQUEST_TIMEOUT_SECONDS', 15),
        'ssl_recheck_hours' => env('LARAVEL_CRM_MONITORING_SSL_RECHECK_HOURS', 12),
        'max_response_bytes' => env('LARAVEL_CRM_MONITORING_MAX_RESPONSE_BYTES', 5 * 1024 * 1024),

        // Set true to allow monitors to target loopback / private / reserved
        // addresses. Off by default to mitigate SSRF from any admin who can
        // create monitors.
        'allow_private_targets' => env('LARAVEL_CRM_MONITORING_ALLOW_PRIVATE_TARGETS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Portal
    |--------------------------------------------------------------------------
    |
    | Settings for the public-facing portal (feature board, signed quote /
    | invoice / purchase-order links).
    |
    | `team_id` is optional. Under multi-tenant `teams` mode every team has
    | its own public board at /p/features/team/{id}, and the bare /p/features
    | resolves the board from the URL, the session, the signed-in user's
    | current team, or — on an install where only one team has a board — that
    | team. Set `team_id` only to pin the portal to a single team and 404
    | everything outside it. When teams mode is off it is ignored.
    |
    | `allow_registration` opts the host application into letting anonymous
    | visitors create user accounts via /p/register. Off by default: enabling
    | it writes rows to the host app's users table and dispatches Laravel's
    | Registered event for each signup.
    |
    */

    'portal' => [
        'allow_registration' => env('LARAVEL_CRM_PORTAL_ALLOW_REGISTRATION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    |
    | Settings for the REST API V2. `token_attempts_per_account` and
    | `token_attempts_decay_seconds` together throttle the POST /api/crm/v2
    | /auth/token endpoint per email address, on top of the IP-keyed
    | throttle:6,1 limiter. Defaults to 5 failed attempts per 10 minutes.
    |
    */

    'api' => [
        'token_attempts_per_account' => env('LARAVEL_CRM_API_TOKEN_ATTEMPTS_PER_ACCOUNT', 5),
        'token_attempts_decay_seconds' => env('LARAVEL_CRM_API_TOKEN_ATTEMPTS_DECAY_SECONDS', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Settings for the public feature board. `view_dedup_minutes` controls how
    | long a single visitor (matched by authenticated user, or hashed IP for
    | anonymous viewers) is treated as the same view before another GET to the
    | public feature page records an additional FeatureView row. Set to 0 to
    | record every request.
    |
    */

    'features' => [
        'view_dedup_minutes' => env('LARAVEL_CRM_FEATURES_VIEW_DEDUP_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Update Notifications
    |--------------------------------------------------------------------------
    |
    | Update this to false if you don't want to show any users that there are
    | package updates available.
    |
    */

    'update_notifications' => env('LARAVEL_CRM_UPDATE_NOTIFICATIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Documentation URL
    |--------------------------------------------------------------------------
    |
    | Where the system check banner points users for release details. Override
    | this if you host your own documentation.
    |
    */

    'docs_url' => env('LARAVEL_CRM_DOCS_URL', 'https://github.com/venturedrake/laravel-crm'),

    /*
    |--------------------------------------------------------------------------
    | Upgrade guide URL
    |--------------------------------------------------------------------------
    |
    | Where every "upgrade guide" link points — the updates page and the system
    | check banner. Separate from docs_url because that one answers "what is in
    | this release?" and this one answers "how do I install it?".
    |
    */

    'upgrade_guide_url' => env('LARAVEL_CRM_UPGRADE_GUIDE_URL', 'https://laravelcrm.com/docs/2.x/upgrading'),

    /*
    |--------------------------------------------------------------------------
    | Models with Global
    |--------------------------------------------------------------------------
    |
    | With multi-tenant support, you can have model rows that are global, using
    | a "global" column in the table. eg. settings
    |
    |
    */

    'model_with_global' => [
        'settings',
    ],

];
