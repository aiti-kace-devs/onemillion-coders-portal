<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'title',
        'sub_title',
        'duration',
        'start_date',
        'end_date',
        'description',
        'overview',
        'prerequisites',
        'cover_image_id',
        'course_category_id',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function centre(){
        return $this->belongsToMany(Centre::class, 'courses');
    }

    public function category(){

        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function coverImage()
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }
}
