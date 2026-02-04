<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Facade;
use Statamic\Assets\AssetContainerContents;
use Statamic\Assets\AssetRepository;
use Statamic\Contracts\Assets\AssetContainer as AssetContainerContract;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Eloquent\Assets\Asset as EloquentAsset;
use Statamic\Eloquent\Assets\AssetContainer;
use Statamic\Facades\Asset as AssetFacade;
use Statamic\Facades\AssetContainer as AssetContainerFacade;
use Statamic\Stache\Repositories\AssetContainerRepository;
use Statamic\Statamic;
use Statamic\Eloquent\Commands\ImportAssets as BaseImportAssets;

class ImportAssetsCommand extends BaseImportAssets
{
    /**
     * Execute the console command.
     *
     * Override to use our GCS-compatible AssetContainerContents binding.
     */
    public function handle(): int
    {
        $this->useDefaultRepositoriesWithGcsFix();

        if ($this->shouldImportAssetContainers()) {
            $this->withProgressBar(AssetContainerFacade::all(), function ($container) {
                AssetContainer::makeModelFromContract($container)?->save();
            });
            $this->components->info('Assets containers imported sucessfully');
        }

        if ($this->shouldImportAssets()) {
            $this->withProgressBar(AssetFacade::all(), function ($asset) {
                EloquentAsset::makeModelFromContract($asset)?->save();
            });
            $this->components->info('Assets imported sucessfully');
        }

        return 0;
    }

    private function shouldImportAssetContainers(): bool
    {
        return $this->option('only-asset-containers')
            || ! $this->option('only-assets')
            && ($this->option('force') || $this->confirm('Do you want to import asset containers?'));
    }

    private function shouldImportAssets(): bool
    {
        return $this->option('only-assets')
            || ! $this->option('only-asset-containers')
            && ($this->option('force') || $this->confirm('Do you want to import assets?'));
    }

    /**
     * Use default repositories with GCS-compatible AssetContainerContents.
     */
    private function useDefaultRepositoriesWithGcsFix(): void
    {
        Facade::clearResolvedInstance(AssetContainerRepositoryContract::class);
        Facade::clearResolvedInstance(AssetRepositoryContract::class);

        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);
        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);

        app()->bind(AssetContainerContract::class, AssetContainer::class);
        // Use our GCS-compatible AssetContainerContents instead of core Statamic class
        app()->bind(AssetContainerContents::class, fn($app) => new \App\Overrides\AssetContainerContents);
    }
}
