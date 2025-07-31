<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'media_media';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    protected $appends = ['title'];

    protected $fillable = [
        'updated_on',
        'created_on',
        'title',
        'description',
        'file',
        'media_type',
        'storage_backend',
        'file_size',
        'mime_type',
        'width',
        'height',
        'duration',
        'thumbnail_small',
        'thumbnail_medium',
        'thumbnail_large',
        'thumbnails_generated',
        'is_reusable',
        'uploaded_by_id',
    ];


    protected $attributes = [
        'mime_type' => 'image/png',
        'thumbnails_generated' => false,
    ];

    protected $identifiableAttribute = 'file';

    public function getTitleAttribute()
    {
        if ($this->media_type == 'unknown') {
            $title = $this->attributes['title'] ?? '';
            return $title . " ($this->file)";
        } else {
            $title = $this->attributes['title'] ?? '';
            return $title . " ($this->media_type)";
        }
    }


    public function articleTranslations()
    {
        return $this->hasMany(ArticleTranslation::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($media) {
            $media->thumbnails_generated = false;
            $media->uploaded_by_id = auth()->id();
        });
    }


    public function getFilePreviewUrlAttribute()
    {
        return config('filesystems.cdn_url') . '/' . ltrim($this->file, '/');
    }


    public function getFullPreviewUrl()
    {
        $fileUrl = $this->file ?? $this->file ?? null;

        if (empty($fileUrl)) {
            return '<span class="text-muted">No file available</span>';
        }
        $url = config('filesystems.cdn_url') . '/' . ltrim($fileUrl, '/');

        return '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="la la-external-link"></i> Preview
            </a>';
    }
}
