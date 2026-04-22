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
    | Session Seat Count Alerts
    |--------------------------------------------------------------------------
    |
    | When the nightly seat count check detects a mismatch, admins get this many
    | minutes to review and run a manual rebuild before auto-repair takes over.
    |
    */

    'occupancy_alert' => [
        'auto_repair_grace_minutes' => (int) env('OCCUPANCY_AUTO_REPAIR_GRACE_MINUTES', 60),
    ],

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

        'occupancy_rebuild' => [
            'label' => 'Repair Availability Slot Count',
            'description' => 'Recalculate displayed availability slot counts from confirmed bookings and refresh cached availability counts.',
            'button_label' => 'Repair Availability Slot Count',
            'command' => 'occupancy:rebuild',
            'options' => [
                '--force' => true,
                '--clear-cache' => true,
            ],
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
        'occupancy:audit' => [
            'label' => 'Check Seat Counts',
            'description' => 'Compare displayed session seat counts with confirmed bookings without changing data.',
            'fields' => [
                [
                    'name' => 'limit',
                    'type' => 'number',
                    'label' => 'Sample Limit',
                    'default' => 20,
                    'option' => 'limit',
                ],
                [
                    'name' => 'fail_on_drift',
                    'type' => 'boolean',
                    'label' => 'Return failure when mismatch is found',
                    'option' => 'fail-on-drift',
                ],
                [
                    'name' => 'repair',
                    'type' => 'boolean',
                    'label' => 'Repair automatically when mismatch is found',
                    'option' => 'repair',
                ],
                [
                    'name' => 'repair_after_minutes',
                    'type' => 'number',
                    'label' => 'Wait this many minutes before auto-repair',
                    'default' => (int) env('OCCUPANCY_AUTO_REPAIR_GRACE_MINUTES', 60),
                    'option' => 'repair-after-minutes',
                ],
            ],
        ],

        'occupancy:rebuild' => [
            'label' => 'Repair Availability Slot Count',
            'description' => 'Rewrite displayed availability slot counts from confirmed bookings. Use only for maintenance repairs.',
            'fields' => [
                [
                    'name' => 'dry_run',
                    'type' => 'boolean',
                    'label' => 'Preview only',
                    'option' => 'dry-run',
                ],
                [
                    'name' => 'force',
                    'type' => 'boolean',
                    'label' => 'Allow in production',
                    'option' => 'force',
                ],
                [
                    'name' => 'clear_cache',
                    'type' => 'boolean',
                    'label' => 'Clear cache after repair',
                    'option' => 'clear-cache',
                    'default' => true,
                ],
            ],
        ],

        'occupancy:repair-due' => [
            'label' => 'Repair Due Seat Count Alert',
            'description' => 'Run the safe repair only when a seat count alert has passed its admin review window.',
            'fields' => [
                [
                    'name' => 'force',
                    'type' => 'boolean',
                    'label' => 'Repair even before due time',
                    'option' => 'force',
                ],
            ],
        ],

        'availability-slots:create-test-drift' => [
            'label' => 'Create Test Slot Count Mismatch',
            'description' => 'Development-only helper for testing alerts and repairs. It changes only the derived availability-count table.',
            'hide_in_production' => true,
            'fields' => [
                [
                    'name' => 'dry_run',
                    'type' => 'boolean',
                    'label' => 'Preview only',
                    'option' => 'dry-run',
                    'default' => true,
                ],
                [
                    'name' => 'write',
                    'type' => 'boolean',
                    'label' => 'Actually create test mismatch',
                    'option' => 'write',
                ],
                [
                    'name' => 'force',
                    'type' => 'boolean',
                    'label' => 'Allow in safe non-production environment',
                    'option' => 'force',
                ],
                [
                    'name' => 'centre_id',
                    'type' => 'number',
                    'label' => 'Centre ID',
                    'option' => 'centre-id',
                ],
                [
                    'name' => 'master_session_id',
                    'type' => 'number',
                    'label' => 'Master Session ID',
                    'option' => 'master-session-id',
                ],
                [
                    'name' => 'date',
                    'type' => 'text',
                    'label' => 'Date',
                    'placeholder' => 'YYYY-MM-DD',
                    'option' => 'date',
                ],
                [
                    'name' => 'delta',
                    'type' => 'number',
                    'label' => 'Test Increase',
                    'default' => 1,
                    'option' => 'delta',
                ],
                [
                    'name' => 'audit_after',
                    'type' => 'boolean',
                    'label' => 'Run check and create alert after mismatch',
                    'option' => 'audit-after',
                    'default' => true,
                ],
                [
                    'name' => 'audit_repair_after_minutes',
                    'type' => 'number',
                    'label' => 'Admin review window in minutes',
                    'default' => 60,
                    'option' => 'audit-repair-after-minutes',
                ],
                [
                    'name' => 'clear_cache',
                    'type' => 'boolean',
                    'label' => 'Clear cache after creating mismatch',
                    'option' => 'clear-cache',
                    'default' => true,
                ],
            ],
        ],

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
