<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Super Admin Role
    |--------------------------------------------------------------------------
    |
    | The Spatie / Backpack role name that is allowed to access the Utilities
    | screen. You can still additionally grant access by setting `is_super`
    | on the admin model; both checks are performed.
    |
    */

    'super_admin_role' => 'super-admin',

    /*
    |--------------------------------------------------------------------------
    | Utility Actions
    |--------------------------------------------------------------------------
    |
    | High-level maintenance actions exposed as buttons at the top of the
    | Utilities page. Each action maps to an Artisan command.
    |
    */

    'utilities' => [
        'optimize' => [
            'label' => 'Optimize',
            'description' => 'Run Laravel optimize to cache commonly used components.',
            'button_label' => 'Run Optimize',
            'command' => 'optimize',
        ],

        'optimize_clear' => [
            'label' => 'Clear Optimization Caches',
            'description' => 'Clear all optimization caches (config, route, view, events).',
            'button_label' => 'Clear Optimize Cache',
            'command' => 'optimize:clear',
        ],

        'cache_clear' => [
            'label' => 'Application Cache',
            'description' => 'Clear the application cache (cache:clear).',
            'button_label' => 'Clear Cache',
            'command' => 'cache:clear',
        ],

        'config_cache' => [
            'label' => 'Config Cache',
            'description' => 'Rebuild the configuration cache for faster requests.',
            'button_label' => 'Rebuild Config Cache',
            'command' => 'config:cache',
        ],

        'route_cache' => [
            'label' => 'Route Cache',
            'description' => 'Rebuild the route cache.',
            'button_label' => 'Rebuild Route Cache',
            'command' => 'route:cache',
        ],

        'view_clear' => [
            'label' => 'View Cache',
            'description' => 'Clear all compiled Blade views.',
            'button_label' => 'Clear View Cache',
            'command' => 'view:clear',
        ],

        'event_clear' => [
            'label' => 'Event Cache',
            'description' => 'Clear cached events and listeners.',
            'button_label' => 'Clear Event Cache',
            'command' => 'event:clear',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Metadata
    |--------------------------------------------------------------------------
    |
    | Optional configuration for custom Artisan commands. When present, the
    | Utilities page can render structured forms for these commands and build
    | argument/option arrays for Artisan::call().
    |
    | Example structure:
    |
    | 'commands' => [
    |     'app:content-to-files' => [
    |         'label' => 'Sync Statamic Content',
    |         'description' => 'Export Statamic content to flat files.',
    |         'fixed_options' => ['--force' => true],
    |         'fields' => [
    |             [
    |                 'name' => 'site',
    |                 'type' => 'text',
    |                 'label' => 'Site Handle',
    |                 'placeholder' => 'default',
    |                 'mode' => 'option', // or 'argument'
    |                 'option' => 'site', // becomes --site=
    |             ],
    |         ],
    |     ],
    | ],
    |
    */

    'commands' => [
        // You can add entries here for commands that need structured options.
        // 'app:content-to-files' => [
        //     'label' => 'Sync Statamic Content',
        //     'description' => 'Export Statamic content to flat files.',
        //     'fixed_options' => ['--force' => true],
        //     'fields' => [
        //         [
        //             'name' => 'site',
        //             'type' => 'text',
        //             'label' => 'Site Handle',
        //             'placeholder' => 'default',
        //             'mode' => 'option',
        //             'option' => 'site',
        //         ],
        //     ],
        // ],
    ],
];
