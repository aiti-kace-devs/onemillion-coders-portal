<?php


namespace App\Helpers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class MediaHelper
{
    /**
     * Converts a GCS path to a media ID and updates the request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $columnName
     */

    const DISK_PROGRAMME_IMAGES = 'course-images';
    const DISK_CENTRE_IMAGES = 'centre-images';
    const DISK_CERTIFICATE_FILES = 'certificates';

    public static function resolveMediaIdFromPath(Request &$request, string $columnName, $getId = false)
    {
        $mediaPath = $request->input($columnName);

        if (empty($mediaPath)) {
            return;
        }

        if (is_array($mediaPath)) {
            $mediaIds = [];
            foreach ($mediaPath as $path) {
                if (empty($path)) continue;

                $media = self::processSingleMediaPath($path, $request);
                if ($media) {
                    $mediaIds[] = $getId ? $media->id : $media;
                }
            }
            return $mediaIds;
        }

        // Handle single string path
        $media = self::processSingleMediaPath($mediaPath, $request);
        if ($getId) {
            return $media ? $media->id : null;
        }

        $request->merge([$columnName => $media ? $media->id : null]);
    }




    protected static function normalizeMediaPath(string $mediaPath): string
    {
        $mediaPath = trim($mediaPath);

        if (empty($mediaPath)) {
            return $mediaPath;
        }

        if (str_starts_with($mediaPath, 'Google Cloud Storage/')) {
            $mediaPath = substr($mediaPath, strlen('Google Cloud Storage/'));
        }

        // If it's a full URL, strip the CDN or GCS API URI prefix
        $cdmUrl = rtrim(env('GOOGLE_CLOUD_STORAGE_API_URI', ''), '/');
        $cdnUrl = rtrim(config('filesystems.cdn_url', ''), '/');

        foreach (array_filter([$cdmUrl, $cdnUrl]) as $base) {
            if (!empty($base) && str_starts_with($mediaPath, $base)) {
                $mediaPath = substr($mediaPath, strlen($base));
                $mediaPath = ltrim($mediaPath, '/');
                break;
            }
        }

        return ltrim($mediaPath, '/');
    }

    protected static function processSingleMediaPath(string $mediaPath, Request $request)
    {
        $mediaPath = self::normalizeMediaPath($mediaPath);
        $media = Media::where('file', $mediaPath)->first();

        if (!$media) {
            $title = $request->input('title') ?? $request->input('site_name') ?? $request->input('name', 'Untitled');
            $description = $request->input('description') ?? $title;
            $userId = auth()->id();

            $extension = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));

            [$mediaType, $mimeType] = self::resolveMediaTypeAndMime($extension);
            try {
                $media = Media::create([
                    'file' => $mediaPath,
                    'title' => $title,
                    'description' => $description,
                    'media_type' => $mediaType,
                    'storage_backend' => 'GCP',
                    'mime_type' => $mimeType,
                    'thumbnails_generated' => false,
                    'is_reusable' => true,
                    'uploaded_by_id' => $userId,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $media = Media::where('file', $mediaPath)->first();
            }
        }

        return $media;
    }

    protected static function resolveMediaTypeAndMime(string $extension): array
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'avif'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
        $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a'];

        if (in_array($extension, $imageExtensions)) {
            return ['image', 'application/image'];
        }

        if (in_array($extension, $documentExtensions)) {
            return ['document', 'application/pdf'];
        }

        if (in_array($extension, $videoExtensions)) {
            return ['video', 'application/video'];
        }

        if (in_array($extension, $audioExtensions)) {
            return ['audio', 'application/audio'];
        }

        return ['unknown', 'application/octet-stream'];
    }




    public static function resolveMediaIdFromFullPath(Request &$request, string $columnName): void
    {
        $filepath = $request->input($columnName);

        if (empty($filepath)) {
            return;
        }

        $filepath = self::normalizeMediaPath($filepath);

        $media = Media::where('file', $filepath)->first();
        $request->merge([$columnName => $media ? $media->file : null]);
    }

    /**
     * Handle media resolution and store operation.
     *
     * @param object $crudController
     * @param Request $request
     * @param string $columnName
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function handleMediaAndStore($crudController, Request $request, string $columnName)
    {
        self::resolveMediaIdFromPath($request, $columnName);
        $strippedData = $crudController->crud->getStrippedSaveRequest($request);
        $item = $crudController->crud->create($strippedData);
        $crudController->data['entry'] = $crudController->crud->entry = $item;
        \Alert::success(trans('backpack::crud.insert_success'))->flash();
        $crudController->crud->setSaveAction();

        return $crudController->crud->performSaveAction($item->getKey());
    }


    public static function handleMediaAndUpdate($crudController, Request $request, string $columnName)
    {
        self::resolveMediaIdFromPath($request, $columnName);
        $strippedData = $crudController->crud->getStrippedSaveRequest($request);
        $item = $crudController->crud->update(
            $request->get($crudController->crud->model->getKeyName()),
            $strippedData
        );
        $crudController->data['entry'] = $crudController->crud->entry = $item;
        \Alert::success(trans('backpack::crud.update_success'))->flash();
        $crudController->crud->setSaveAction();

        return $crudController->crud->performSaveAction($item->getKey());
    }







    public static function viewMediaFile(string $columnName = 'media', ?string $label = 'Media File'): void
    {
        $label = $label ?? ucwords(str_replace('_', ' ', $columnName));
        $preview = 'Preview';

        CRUD::addColumn([
            'label' => $label,
            'type'  => 'text',
            'name'  => $columnName . '_file',
            'function' => function ($entry) use ($preview) {
                $media = $entry->media;

                if ($media && $media->file) {
                    $url = rtrim(config('filesystems.cdn_url'), '/') . '/' . ltrim($media->file, '/');

                    return '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="la la-external-link"></i> ' . $preview . '
                        </a>';
                }

                return '<span class="text-muted">No file</span>';
            },
            'escaped' => false,
        ]);
    }


    public static function previewMediaImageFile(string $columnName = 'media', ?string $label = 'Media File'): void
    {
        $label = $label ?? ucwords(str_replace('_', ' ', $columnName));

        CRUD::addColumn([
            'label' => $label,
            'limit' => -1,
            'name'  => $columnName . '_file',
            'value' => function ($entry) use ($columnName) {
                $fileModel = $entry->{$columnName};
                $path =  $fileModel->file ?? null;

                if ($fileModel && $fileModel->thumbnails_generated) {
                    $path = $fileModel->thumbnail_medium ?? $path;
                }

                if ($path) {
                    $fullpath = config('filesystems.cdn_url') . '/' . $path;
                    $path = config('filesystems.cdn_url') . '/' . $path;

                    return " <a href='" . $fullpath . "' target='_blank'><img src='" . $path . "' alt='' class='img-fluid' style='width:60px;height:60px;' ></a>";
                }

                return '<span class="text-muted">No file</span>';
            },
            'escaped' => false,
        ]);
    }

    public static function previewMediaImagesFile(string $columnName = 'media', ?string $label = 'Media File'): void
    {
        $label = $label ?? ucwords(str_replace('_', ' ', $columnName));

        CRUD::addColumn([
            'label' => $label,
            'limit' => -1,
            'name'  => $columnName . '_file',
            'value' => function ($entry) use ($columnName) {
                $value = $entry->{$columnName};

                // Handle if value is a full URL string (e.g., from image column)
                if (is_string($value) && !empty($value)) {
                    $imageUrl = $value;
                    // If it's not already a full URL, prepend CDN URL
                    if (strpos($imageUrl, 'http://') !== 0 && strpos($imageUrl, 'https://') !== 0) {
                        $imageUrl = rtrim(config('filesystems.cdn_url'), '/') . '/' . ltrim($imageUrl, '/');
                    }
                    return " <a href='" . $imageUrl . "' target='_blank'><img src='" . $imageUrl . "' alt='' class='img-fluid' style='width:60px;height:60px;' ></a>";
                }

                // Handle if value is a Media model object
                if (is_object($value)) {
                    $fileModel = $value;
                    $path = $fileModel->file ?? null;

                    if ($fileModel && $fileModel->thumbnails_generated) {
                        $path = $fileModel->thumbnail_medium ?? $path;
                    }

                    if ($path) {
                        $fullpath = rtrim(config('filesystems.cdn_url'), '/') . '/' . ltrim($path, '/');
                        return " <a href='" . $fullpath . "' target='_blank'><img src='" . $fullpath . "' alt='' class='img-fluid' style='width:60px;height:60px;' ></a>";
                    }
                }

                return '<span class="text-muted">No file</span>';
            },
            'escaped' => false,
        ]);
    }







    public static function getMediaSelector(string $name, bool $multiple = false, array $disk_options = [], $label = '', $asArray = false, $value = '')
    {
        $type = $multiple ? 'browse_multiple' : 'browse';
        $label ?? $name;

        if ($asArray) {
            return [
                'name' => $name,
                'label' => $label,
                'type' => $type,
                'disk_options' => $disk_options,
                'value' => $value
            ];
        }

        $field =  CRUD::field($name)
            ->label($label)
            ->type($type)
            ->value($value)
            ->disk_options($disk_options)
            ->wrapper(['class' => 'form-group col-6']);;

        return $field;
    }


    public static function getDiskOptions(array $disks = [], array $allowedTypes = [''])
    {
        $disks = count($disks) ? $disks : ['public'];
        $allowedTypes = count($allowedTypes) ? $allowedTypes : array_merge(self::getImageTypes(), self::getVideoTypes(), self::getAudioTypes(), self::getDocumentTypes());

        $options = [
            "disks" => $disks,
            "allowed_types" => $allowedTypes,
        ];
        return $options;
    }



    public static function getProgrammeImagesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_PROGRAMME_IMAGES], self::getImageTypes());
    }

    public static function getCentreImagesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_CENTRE_IMAGES], self::getImageTypes());
    }

    public static function getCertificateFilesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_CERTIFICATE_FILES], self::getDocumentTypes());
    }


    private static function getImageTypes()
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    }

    private static function getVideoTypes()
    {
        return ['mp4', 'webm', 'mov'];
    }

    private static function getAudioTypes()
    {
        return ['mp3', 'wav', 'ogg'];
    }

    private static function getDocumentTypes()
    {
        return ['pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'ppt', 'pptx'];
    }

    public static function decryptDiskOptions()
    {
        $encryptedData = request()->header('X-Disks-Hash') ?? Session::get('disksHash');
        Session::flash('disksHash', $encryptedData);
        try {
            $decryptedData = Crypt::decrypt($encryptedData);
            return $decryptedData;
        } catch (\Throwable $th) {
            //throw $th;
            return [];
        }
    }
}
