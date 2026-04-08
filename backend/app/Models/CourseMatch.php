<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class CourseMatch extends Model
{
     protected $table = 'course_match';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = [
        'question',
        'description',
        'icon',
        'tag',
        'description',
        'order',
        'status',
        'is_multiple_select',
        'type',
        'reference_source'
       
    ];
    use CrudTrait;
    //

    protected $casts = [
        'course_match_options' => 'array',
        'is_multiple_select' => 'boolean',
        'type' => 'string',
        'reference_source' => 'string',
        'order' => 'integer'
    ];

    public function courseMatchOptions()
    {
        return $this->hasMany(CourseMatchOption::class, 'course_match_id');
    }
}
