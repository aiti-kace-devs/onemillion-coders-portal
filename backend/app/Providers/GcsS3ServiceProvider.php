<?php

namespace App\Providers;

use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as S3Adapter;
use League\Flysystem\AwsS3V3\VisibilityConverter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Visibility;

class GcsS3ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (false === config('app.use_basset_cloud')) {
            return;
        }

        // Check for required environment variables to prevent crash in environments where they are missing (like Docker)
        // convert envs to config calls

        if (!config('services.google.storage_bucket') || !config('services.google.hmac_access_id') || !config('services.google.hmac_secret')) {
            return;
        }

        // Inject the basset_cloud disk configuration dynamically
        config([
            'filesystems.disks.basset_cloud' => [
                'driver' => 's3_gcs',
                'key' => config('services.google.hmac_access_id'),
                'secret' => config('services.google.hmac_secret'),
                'region' => 'auto',
                'bucket' => config('services.google.storage_bucket'),
                'endpoint' =>  'https://storage.googleapis.com',
                'use_path_style_endpoint' => true,
                'url' => config('services.google.basset_cloud_url', config('services.google.storage_api_uri')),
            ],
        ]);

        // set basset disk
        config()->set('backpack.basset.disk', 'basset_cloud');

        Storage::extend('s3_gcs', function ($app, $config) {
            $s3Config = $this->formatS3Config($config);
            $root = (string) ($s3Config['root'] ?? '');
            $streamReads = $s3Config['stream_reads'] ?? false;
            $client = new S3Client($s3Config);

            // Custom Visibility Converter that ignores all ACLs
            // This is required for GCS when Uniform Bucket-Level Access is enabled
            $visibility = new class implements VisibilityConverter {
                public function visibilityToAcl(string $visibility): string
                {
                    return '';
                }

                public function aclToVisibility(array $grants): string
                {
                    return Visibility::PUBLIC;
                }

                public function defaultForDirectories(): string
                {
                    return Visibility::PUBLIC;
                }
            };

            $adapter = new S3Adapter(
                $client,
                $s3Config['bucket'],
                $root,
                $visibility,
                null,
                $config['options'] ?? [],
                $streamReads
            );

            return new AwsS3V3Adapter(
                new Flysystem($adapter, Arr::only($config, ['url', 'temporary_url'])),
                $adapter,
                $s3Config,
                $client
            );
        });
    }

    /**
     * Format the given S3 configuration with the default options.
     */
    protected function formatS3Config(array $config): array
    {
        $config += ['version' => 'latest'];

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);

            if (! empty($config['token'])) {
                $config['credentials']['token'] = $config['token'];
            }
        }

        return Arr::except($config, ['token']);
    }
}
