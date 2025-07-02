<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Files Config
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'backupDirectory' => storage_path('env-editor'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes group config
    |--------------------------------------------------------------------------
    |
    */
    'route' => [
        'enable' => true,
        // Prefix url for route Group
        'prefix' => '/admin/env-editor',
        // Routes base name
        'name' => 'env-editor',
        // Middleware(s) applied on route Group
        'middleware' => ['web', 'theme:dashboard', 'auth:admin', 'role:super-admin,admin'],
        // 'middleware' => ['theme:dashboard', 'auth:admin',  'permission:manage.manager'],
        //
    ],

    /* ------------------------------------------------------------------------------------------------
    |  Time Format for Views and parsed backups
    | ------------------------------------------------------------------------------------------------
    */
    'timeFormat' => 'd/m/Y H:i:s',

    /* ------------------------------------------------------------------------------------------------
     | Set Views options
     | ------------------------------------------------------------------------------------------------
     | Here you can set The "extends" blade of index.blade.php
    */
    // 'layout' => 'layouts.app',
];
