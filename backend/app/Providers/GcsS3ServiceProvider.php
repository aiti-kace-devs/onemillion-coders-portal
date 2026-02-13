<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GcsS3ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Check if we should use basset cloud
        if (!config('app.use_basset_cloud')) {
            return;
        }

        // Check if GCS Fuse is enabled and configured
        $useGcsFuse = config('services.google.use_gcs_fuse', false);
        $gcsFusePath = config('services.google.gcs_fuse_path');
        $bassetCloudUrl = config('services.google.basset_cloud_url');
        $bucket = config('services.google.storage_bucket');

        if ($useGcsFuse && $gcsFusePath && ($bassetCloudUrl || $bucket)) {
            // GCS Fuse mode: local driver with cloud URL
            config([
                'filesystems.disks.basset_cloud' => [
                    'driver' => 'local',
                    'root' => $gcsFusePath,
                    'url' => $bassetCloudUrl ?: "https://storage.googleapis.com/{$bucket}/basset",
                    'visibility' => 'public',
                    'throw' => false,
                ],
            ]);
        } else {
            // Fallback: don't create basset_cloud disk, will use default 'basset'
            return;
        }
    }
}
