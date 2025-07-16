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

    const DISK_ARTICLE_IMAGES = 'article_images';
    const DISK_FEATURED_IMAGES = 'article_featured_images';
    const DISK_FORUM_IMAGES = 'forum_images';
    const DISK_ARTICLE_CATEGORY_IMAGES = 'article_category_images';
    const DISK_PARTNER_IMAGES = 'partner_images';
    const DISK_PRODUCT_TYPE_IMAGES = 'product_type_images';
    const DISK_PRODUCT_IMAGES = 'product_images';
    const DISK_SITE_SETTINGS_IMAGES = 'site_settings_images';
    const DISK_TRANSLATION_AUDIO = 'translation_audio';
    const DISK_REPORT_DOCUMENT = 'report_document';

    const DISK_RESOURCES = 'resources';

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




    protected static function processSingleMediaPath(string $mediaPath, Request $request)
    {
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

        if (empty($gcsPath)) {
            return;
        }

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



    public static function getArticleImagesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_ARTICLE_IMAGES], self::getImageTypes());
    }

    public static function getFeaturedImageDiskOptions()
    {
        return self::getDiskOptions([self::DISK_FEATURED_IMAGES], self::getImageTypes());
    }

    public static function getForumImagesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_FORUM_IMAGES], self::getImageTypes());
    }

    public static function getArticleCategoryImagesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_ARTICLE_CATEGORY_IMAGES], self::getImageTypes());
    }

    public static function getPartnerImagesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_PARTNER_IMAGES], self::getImageTypes());
    }

    public static function getProductTypeImagesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_PRODUCT_TYPE_IMAGES], self::getImageTypes());
    }

    public static function getProductImagesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_PRODUCT_IMAGES], self::getImageTypes());
    }

    public static function getSiteSettingsImagesDiskOptions()
    {
        return self::getDiskOptions([self::DISK_SITE_SETTINGS_IMAGES], self::getImageTypes());
    }

    public static function getTranslationAudioDiskOptions()
    {
        return self::getDiskOptions([self::DISK_TRANSLATION_AUDIO], self::getAudioTypes());
    }

    public static function getReportDocumentDiskOptions()
    {
        return self::getDiskOptions([self::DISK_REPORT_DOCUMENT], self::getDocumentTypes());
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
