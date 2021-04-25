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
    
    'crm_owner' => '',

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | This value is used to define whether you wish to use the crm in a
    | subfolder on your domain or just in the main directory. You can change
    | this value to anything you wish or simply set to blank.
    |
    | eg. https://yourdomainname.com/crm or https://yourdomainnam.com
    |
    | Tip: You would use a subfolder if you are using this crm within a
    | current Laravel project that might be an entire application with routes
    | controllers, models, views, etc.
    |
    */
    
    'route_prefix' => 'crm',

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
    
    'db_table_prefix' => 'crm_',

    /*
    |--------------------------------------------------------------------------
    | Database Table Field Encryption
    |--------------------------------------------------------------------------
    |
    | This is a security feature that will encrypt personal information in
    | certain database table fields as an added layer of privacy protection.
    |
    */
    
    'encrypt_db_fields' => true,
    
];
