<?php

namespace App\Listeners;

use App\Helpers\MediaHelper;
use App\Models\Media;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;

class FileStorageEventSubscriber extends MediaHelper
{


    public function handleFileUploaded($event)
    {
        /**
         * $event->disk()
         * $event->path(),
         * $event->files(),
         * $event->overwrite(),
         *
         */

        foreach ($event->files() as $file) {
            $mediaTypeAndMime = $this->resolveMediaTypeAndMime($file['extension']);

            $disk = $event->disk();
            // get disk path from filesystem config
            $diskpath = config('filesystems.disks.' . $disk . '.path_prefix');
            Media::create([
                'title' => $file['name'],
                'description' => $file['name'],
                'file' => $diskpath . '/' . $file['name'],
                'media_type' => $mediaTypeAndMime[0],
                'storage_backend' => Str::upper(config('filesystems.cloud_disk', 'gcs')),
                'mime_type' => $mediaTypeAndMime[1],
                'thumbnails_generated' => false,
                'is_reusable' => true,
                'uploaded_by_id' => backpack_user()->id,
            ]);
        }
    }

    public function handleFileDeleted($event)
    {
        $diskPath = config('filesystems.disks.' . $event->disk() . '.path_prefix');

        foreach ($event->items() as $item) {
            $name = $diskPath . '/' . $item['path'];
            try {
                Media::where('file', $name)->delete();
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    }

    public function handleFileUpdated() {}

    /**
     * Create the event listener.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            \Alexusmai\LaravelFileManager\Events\FilesUploaded::class => 'handleFileUploaded',
            \Alexusmai\LaravelFileManager\Events\Deleting::class => 'handleFileDeleted',
            \Alexusmai\LaravelFileManager\Events\Rename::class => 'handleFileUpdated',
            \Alexusmai\LaravelFileManager\Events\FileUpdate::class => 'handleFileUpdated',

        ];
    }
    /**
     * Handle the event.
     */
}
