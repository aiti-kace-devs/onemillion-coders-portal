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
];
