<?php

use App\Helpers\MediaHelper;

$gcs = [
    'driver' => 'gcs',
    'key_file_path' => storage_path(env('GOOGLE_CLOUD_KEY_FILE', null)), // optional: /path/to/service-account.json
    'key_file' => [], // optional: Array of data that substitutes the .json file (see below)
    'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'your-project-id'), // optional: is included in key file
    'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'your-bucket'),
    'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', ''), // optional: /default/path/to/apply/in/bucket
    'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', null), // see: Public URLs below
    'api_endpoint' => env('GOOGLE_CLOUD_STORAGE_API_ENDPOINT', null), // set storageClient apiEndpoint
    'visibility' => 'public', // optional: public|private
    // 'visibility_handler' => null, // optional: set to \League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility::class to enable uniform bucket level access
    'metadata' => ['cacheControl' => 'public,max-age=86400'], // optional: default metadata
    'url' => env('GOOGLE_CLOUD_STORAGE_API_URI'),
    'visibility_handler' => \League\Flysystem\GoogleCloudStorage\UniformBucketLevelAccessVisibility::class,
];

$defaultDriver = env('FILESYSTEM_DRIVER', 'local');
$defaultDiskConfigs = [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
    ],
    'gcs' => $gcs,
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_REGION', 'gh'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => true,
    ],

];

$baseConfig = $defaultDiskConfigs[$defaultDriver] ?? $defaultDiskConfigs['local'];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'cdn_url' =>  env('CDN_URL'),
    'default_cloud_storage' => env('DEFAULT_CLOUD_STORAGE', 'gcs'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        's3' => array_merge($defaultDiskConfigs['s3'], [
            'url' => env('AWS_URL') . '/' . env('AWS_BUCKET'),
        ]),
        's3_basset' => array_merge($defaultDiskConfigs['s3'], [
            'url' => env('AWS_URL') . '/' . env('BASSET_CLOUD_BUCKET', 'omcp-asset'),
            'bucket' => env('BASSET_CLOUD_BUCKET', 'omcp-asset'),
        ]),
        'gcs' => $gcs,
        'gcs_uploads' => array_merge($gcs, [
            'root' => 'uploads',
            'url' => env('GOOGLE_CLOUD_STORAGE_API_URI') . '/uploads',
        ]),

        'private_cloud' => array_merge($baseConfig, [
            'visibility' => 'private',
            'bucket' => env('PRIVATE_CLOUD_BUCKET', 'omcp-private'),
            'url' => ($baseConfig['url'] ?? '') . '/' . env('PRIVATE_CLOUD_BUCKET', 'omcp-private'),
            'throw' => false,
        ]),

        MediaHelper::DISK_PROGRAMME_IMAGES => array_merge(
            $baseConfig,
            [
                'path_prefix' => env('CLOUD_STORAGE_PATH_PREFIX', 'media') . '/image/course-images',
                'url' => env('GOOGLE_CLOUD_STORAGE_API_URI') . '/course-images',
            ]
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('files') => storage_path('app/public/files'),

    ],

];
