<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class CourseMatchOption extends Model
{
    use CrudTrait;
    //
    protected $fillable = [
        'course_match_id',
        'answer',
        'description',
        'value',
        'icon',
        'status'
    ];

    public function courseMatch(){
        return $this->belongsTo(CourseMatch::class);
    }
}
