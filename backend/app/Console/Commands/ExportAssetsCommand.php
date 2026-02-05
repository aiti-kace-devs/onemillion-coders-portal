<?php

namespace App\Console\Commands;

use Closure;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Statamic\Assets\Asset;
use Statamic\Assets\AssetContainer;
use Statamic\Assets\AssetRepository;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Eloquent\Assets\AssetContainerModel;
use Statamic\Eloquent\Assets\AssetModel;
use Statamic\Facades\Asset as AssetFacade;
use Statamic\Facades\AssetContainer as AssetContainerFacade;
use Statamic\Stache\Repositories\AssetContainerRepository;
use Statamic\Statamic;
use Statamic\Eloquent\Commands\ExportAssets as BaseExportAssets;

class ExportAssetsCommand extends BaseExportAssets
{
    protected $signature = 'statamic:eloquent:export-assets
        {--force : Force the export to run, with all prompts answered "yes"}
        {--delay=100 : Milliseconds to pause between each asset export to prevent GCS rate limits (429)}';

    /**
     * Execute the console command.
     *
     * Override to use formatMetaForExport when writing asset metadata.
     */
    public function handle()
    {
        $this->useDefaultRepositoriesForExport(function () {
            $this->exportAssetContainersWithPrompt();
            $this->exportAssetsWithMetaFix();
        });

        return 0;
    }

    /**
     * Use default repositories for export (replicated from parent - usingDefaultRepositories is private).
     */
    private function useDefaultRepositoriesForExport(Closure $callback): void
    {
        Facade::clearResolvedInstance(AssetContainerRepositoryContract::class);
        Facade::clearResolvedInstance(AssetRepositoryContract::class);

        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);
        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);

        app()->bind(AssetContainerContract::class, AssetContainer::class);
        app()->bind(AssetContract::class, Asset::class);

        $callback();
    }

    /**
     * Export asset containers (replicated from parent - exportAssetContainers is private).
     */
    private function exportAssetContainersWithPrompt(): void
    {
        if (! $this->option('force') && ! $this->confirm('Do you want to export asset containers?')) {
            return;
        }

        $containers = AssetContainerModel::all();

        $this->withProgressBar($containers, function ($model) {
            AssetContainerFacade::make()
                ->title($model->title)
                ->handle($model->handle)
                ->disk($model->disk ?? config('filesystems.default'))
                ->allowUploads($model->settings['allow_uploads'] ?? null)
                ->allowDownloading($model->settings['allow_downloading'] ?? null)
                ->allowMoving($model->settings['allow_moving'] ?? null)
                ->allowRenaming($model->settings['allow_renaming'] ?? null)
                ->createFolders($model->settings['create_folders'] ?? null)
                ->searchIndex($model->settings['search_index'] ?? null)
                ->save();
        });

        $this->newLine();
        $this->info('Asset containers imported');
    }

    /**
     * Export assets with properly formatted meta to avoid writeMeta null error.
     */
    private function exportAssetsWithMetaFix(): void
    {
        if (! $this->option('force') && ! $this->confirm('Do you want to export assets?')) {
            return;
        }

        $assets = AssetModel::all();

        $this->withProgressBar($assets, function ($model) {
            $container = $model->container ?? Str::before($model->handle ?? '', '::');
            $path = $model->path ?? Str::after($model->handle ?? '', '::');

            if (empty($container) || empty($path)) {
                return;
            }

            $containerObj = AssetContainerFacade::find($container);
            if (! $containerObj) {
                return;
            }

            AssetFacade::make()
                ->container($containerObj)
                ->path($path)
                ->writeMeta($this->formatMetaForExport($model));

            $delayMs = (int) $this->option('delay');
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        });

        $this->newLine();
        $this->info('Assets imported');
    }

    /**
     * Format meta for export to ensure writeMeta receives valid structure.
     *
     * writeMeta() expects $meta to have a 'data' key. Assets with no custom
     * metadata have null in the data column, causing "array offset on null" error.
     */
    private function formatMetaForExport(AssetModel $model): array
    {
        return array_merge(
            ['data' => $model->data ?? []],
            (array) ($model->meta ?? [])
        );
    }
}
