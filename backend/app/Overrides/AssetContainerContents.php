<?php

namespace App\Overrides;

use Illuminate\Support\Collection;
use League\Flysystem\DirectoryListing;
use Statamic\Assets\AssetContainerContents as BaseAssetContainerContents;
use Statamic\Facades\Stache;
use Statamic\Statamic;

class AssetContainerContents extends BaseAssetContainerContents
{
    /**
     * Get all asset container contents.
     *
     * Override to ensure dirname is always present in file metadata (GCS compatibility).
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        if ($this->files && ! Statamic::isWorker()) {
            return $this->files;
        }

        return $this->files = $this->cacheStore()->remember($this->cacheKey(), $this->cacheTtl(), function () {
            return collect($this->getRawListing())
                ->keyBy('path')
                ->map(fn($file) => $this->normalizeWithDirnameFallback($file))
                ->pipe(fn($files) => $this->ensureMissingDirectoriesExistWithFallback($files))
                ->sortKeys();
        });
    }

    /**
     * Get raw Flysystem directory listing.
     */
    private function getRawListing(): DirectoryListing
    {
        return $this->container->disk()->filesystem()->getDriver()->listContents('/', true);
    }

    /**
     * Normalize Flysystem attributes with GCS dirname fallback.
     *
     * Ensures dirname is always present, even when the GCS Flysystem adapter doesn't provide it.
     */
    private function normalizeWithDirnameFallback($attributes): array
    {
        $path = $attributes->path();
        $normalized = array_merge([
            'type' => $attributes->type(),
            'path' => $path,
            'timestamp' => $attributes->lastModified(),
        ], pathinfo($path));

        if (! isset($normalized['dirname'])) {
            $normalized['dirname'] = pathinfo($path, PATHINFO_DIRNAME);
        }

        if (isset($normalized['dirname']) && $normalized['dirname'] === '.') {
            $normalized['dirname'] = '';
        }

        if ($normalized['type'] === 'file') {
            $normalized['size'] = $attributes->fileSize();
        }

        return $normalized;
    }

    /**
     * Ensure missing directories exist with dirname fallback.
     */
    private function ensureMissingDirectoriesExistWithFallback(Collection $files): Collection
    {
        $files
            ->filter(fn($item) => $item['type'] === 'file')
            ->each(function ($file) use ($files) {
                $dirname = $file['dirname'] ?? pathinfo($file['path'] ?? '', PATHINFO_DIRNAME);
                $dirname = $dirname === '.' ? '' : $dirname;

                while ($dirname !== '') {
                    $parentDir = pathinfo($dirname, PATHINFO_DIRNAME);
                    $parentDir = $parentDir === '.' ? '' : $parentDir;

                    $files->put($dirname, [
                        'type' => 'dir',
                        'path' => $dirname,
                        'basename' => $basename = pathinfo($dirname, PATHINFO_BASENAME),
                        'filename' => $basename,
                        'timestamp' => null,
                        'dirname' => $parentDir,
                    ]);

                    $dirname = $parentDir;
                }
            });

        return $files;
    }

    /**
     * Get cache key.
     */
    private function cacheKey(): string
    {
        return 'asset-list-contents-' . $this->container->handle();
    }

    /**
     * Get cache TTL.
     */
    private function cacheTtl(): ?int
    {
        return Stache::isWatcherEnabled() ? 0 : null;
    }
}
