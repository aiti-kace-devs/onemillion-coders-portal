<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload dir
    |--------------------------------------------------------------------------
    |
    | The dir where to store the images (relative from public).
    |
    */
    'dir' => ['uploads'],

    /*
    |--------------------------------------------------------------------------
    | Filesystem disks (Flysytem)
    |--------------------------------------------------------------------------
    |
    | Define an array of Filesystem disks, which use Flysystem.
    | You can set extra options, example:
    |
    | 'my-disk' => [
    |        'URL' => url('to/disk'),
    |        'alias' => 'Local storage',
    |    ]
    */
    'disks' => [
        'public' => [
            'URL' => config('app.url') . '/storage',
            'alias' => 'Public Storage',
        ],
        'gcs' => [
            'URL' => env('GOOGLE_CLOUD_STORAGE_API_URI'),
            'alias' => 'Google Cloud Storage',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes group config
    |--------------------------------------------------------------------------
    |
    | The default group settings for the elFinder routes.
    |
    */

    'route' => [
        'prefix' => config('backpack.base.route_prefix', 'admin') . '/elfinder',
        'middleware' => ['web', config('backpack.base.middleware_key', 'admin')], //Set to null to disable middleware filter
    ],

    /*
    |--------------------------------------------------------------------------
    | Access filter
    |--------------------------------------------------------------------------
    |
    | Filter callback to check the files
    |
    */

    'access' => 'Barryvdh\Elfinder\Elfinder::checkAccess',

    /*
    |--------------------------------------------------------------------------
    | Roots
    |--------------------------------------------------------------------------
    |
    | By default, the roots file is LocalFileSystem, with the above public dir.
    | If you want custom options, you can set your own roots below.
    |
    */

    'roots' => null,

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | These options are merged, together with 'roots' and passed to the Connector.
    | See https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1
    |
    */

    'options' => [
        'bind' => [
            'upload.presave' => [
                'Plugin.AutoResize.onUpLoadPreSave'
            ]
        ],
        'plugin' => [
            'AutoResize' => [
                'enable'         => true,
                'maxWidth'       => 1920,
                'maxHeight'      => 1920,
                'quality'        => 95
            ]
        ],
        'requestType' => 'POST',
        'rememberLastDir' => false,
        'rememberLastOpened' => false,
        'rememberOpenDir' => false,
        'sync' => 0,
        'autoLoad' => false,
        'autoConnect' => false,
        'syncMinMs' => false,
        'syncChkAsTs' => false,
        'syncChkAs2' => false,
        'checkUpdate' => false,
        'autoReload' => false,
        'reload' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Root Options
    |--------------------------------------------------------------------------
    |
    | These options are merged, together with every root by default.
    | See https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1#root-options
    |
    */
    'root_options' => [
        'uploadDeny'    => ['all'],
        'uploadAllow'   => ['image', 'text/plain', 'application/pdf', 'video'],
        'uploadOrder'   => ['deny', 'allow'],
        'acceptedName'  => '/^[^\.].*$/',
    ],

];
