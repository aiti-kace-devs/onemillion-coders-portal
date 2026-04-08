<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'arkesel' => [
        'key' => env('ARKESEL_SMS_API_KEY'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'api_key' => env('RECAPTCHA_API_KEY'),
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'max_risk_analysis_score' => env('RECAPTCHA_MAX_RISK_ANALYSIS_SCORE', 0.5),
        'skip_recaptcha' => env('RECAPTCHA_SKIP', false),
    ],
    'google' => [
        'storage_bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
        'hmac_access_id' => env('GOOGLE_CLOUD_HMAC_ACCESS_ID'),
        'hmac_secret' => env('GOOGLE_CLOUD_HMAC_SECRET'),
        'basset_cloud_url' => env('BASSET_CLOUD_URL'),
        'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI'),
        'use_gcs_fuse' => env('USE_GCS_FUSE', false),
        'gcs_fuse_path' => env('GCS_FUSE_PATH', storage_path('app/public/basset')),
    ],

    /*
    | Partner progress behaviour not stored on PartnerIntegration rows (bulk program slug, UI, reminders, etc.).
    | Partner codes come from registered drivers + DB (mappings, integrations), not from a single global "startocode" flag.
    */
    'partner_progress' => [
        /** Bundled Startocode driver: must match programme provider + Partner Integration partner_code (see App Config). */
        'startocode_partner_code' => env('PARTNER_PROGRESS_STARTOCODE_PARTNER_CODE', 'startocode'),
        'program_slug' => env('PARTNER_PROGRESS_PROGRAM_SLUG', 'gh-program'),
        // Optional per-partner program slugs for scheduled bulk sync, e.g. {"acme":"acme-101","other":"p-2"}
        'program_slugs_by_partner' => json_decode((string) env('PARTNER_PROGRESS_PROGRAM_SLUGS_JSON', '{}'), true) ?: [],
        'bulk_per_page' => (int) env('PARTNER_PROGRESS_BULK_PER_PAGE', 100),
        'stale_after_days' => (int) env('PARTNER_PROGRESS_STALE_AFTER_DAYS', 3),
        'preview_refresh_minutes' => (int) env('PARTNER_PROGRESS_PREVIEW_REFRESH_MINUTES', 30),
        'history_min_gap_hours' => (int) env('PARTNER_PROGRESS_HISTORY_MIN_GAP_HOURS', 12),
        'enable_student_progress_menu' => filter_var(env('PARTNER_PROGRESS_ENABLE_STUDENT_MENU', true), FILTER_VALIDATE_BOOLEAN),
        'send_stale_reminders' => filter_var(env('PARTNER_PROGRESS_SEND_STALE_REMINDERS', true), FILTER_VALIDATE_BOOLEAN),
        'reminder_cooldown_hours' => (int) env('PARTNER_PROGRESS_REMINDER_COOLDOWN_HOURS', 24),
        'reminder_batch_size' => (int) env('PARTNER_PROGRESS_REMINDER_BATCH_SIZE', 200),
        /*
        | Partner codes (comma-separated, normalized slugs) for which a header whose name contains "Signature"
        | may carry the HMAC secret (e.g. ps_...) — OMCP derives per-request signing without storing hmac_secret
        | in signature_config_json. Change anytime as new partners onboard; do not hardcode only one vendor in app code.
        */
        'signature_secret_header_derived_partner_codes' => array_values(array_filter(array_map(
            static fn (string $s): string => strtolower(trim($s)),
            explode(',', (string) env('PARTNER_PROGRESS_SIGNATURE_SECRET_HEADER_PARTNER_CODES', 'startocode,telecel'))
        ))),
        /** Allow partner:probe-http and in-memory integration override (never enable in production unless needed). */
        'allow_probe_integration_override' => filter_var(env('PARTNER_PROGRESS_ALLOW_PROBE_OVERRIDE', false), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    | OMCP-default progress mapping (dot paths are relative to documented roots).
    | Partners override via partner_integrations.response_mapping_json (single_student / bulk_item).
    */
    'partner_progress_mapping_defaults' => [
        'single_student' => [
            'data_root' => 'data',
            'external_student_ref_path' => 'partner_student_ref',
            'progress_root' => 'progress',
            'learning_paths_key' => 'learning_paths',
            'courses_key' => 'courses',
            'raw_snapshot_path' => 'data',
        ],
        'bulk_item' => [
            'internal_learner_key_paths' => ['omcp_id', 'external_student_id'],
            'external_student_ref_path' => 'partner_student_ref',
            'single_unit_path' => 'learning_path',
            'progress_root' => 'progress',
            'learning_paths_key' => 'learning_paths',
            'courses_key' => 'courses',
        ],
    ],

    // Endpoint presets displayed in Partner Integration admin wizard.
    // Optional JSON map:
    // {
    //   "startocode-default": {...},
    //   "generic-v1": {...}
    // }
    'partner_endpoint_presets' => json_decode((string) env('PARTNER_ENDPOINT_PRESETS_JSON', '{}'), true) ?: [],

    // Allow-list for admin path-param DB lookup binding helper.
    // Keep short and explicit for safety.
    'partner_binding_allowed_tables' => json_decode((string) env('PARTNER_BINDING_ALLOWED_TABLES', '["users"]'), true) ?: ['users'],
    // Optional column allow-list per table:
    // PARTNER_BINDING_ALLOWED_COLUMNS='{"users":["id","userId","email","registered_course"]}'
    'partner_binding_allowed_columns' => json_decode((string) env('PARTNER_BINDING_ALLOWED_COLUMNS', '{"users":["id","userId","email","registered_course"]}'), true) ?: [],

    'partner_monitoring' => [
        'enabled' => env('PARTNER_SYNC_MONITORING_ENABLED', true),
        'failure_window_minutes' => env('PARTNER_SYNC_FAILURE_WINDOW_MINUTES', 60),
        'min_attempts_for_rate_alert' => env('PARTNER_SYNC_MIN_ATTEMPTS_FOR_ALERT', 20),
        'failure_rate_threshold' => env('PARTNER_SYNC_FAILURE_RATE_THRESHOLD', 0.35),
        'default_sla_hours' => env('PARTNER_SYNC_DEFAULT_SLA_HOURS', 6),
        // Optional per-partner override map:
        // PARTNER_SYNC_PARTNER_SLA_HOURS='{\"startocode\":6,\"google\":4}'
        'partner_sla_hours' => json_decode((string) env('PARTNER_SYNC_PARTNER_SLA_HOURS', '{}'), true) ?: [],
    ],

    // Raw history rows older than hot_days are rolled into daily rollups, then deleted (partner:prune-history).
    'partner_history_retention' => [
        'enabled' => env('PARTNER_HISTORY_RETENTION_ENABLED', true),
        'hot_days' => env('PARTNER_HISTORY_HOT_DAYS', 90),
        'prune_batch_size' => env('PARTNER_HISTORY_PRUNE_BATCH', 1000),
        'visualization_point_limit' => env('PARTNER_HISTORY_VIZ_POINT_LIMIT', 180),
    ],
];
