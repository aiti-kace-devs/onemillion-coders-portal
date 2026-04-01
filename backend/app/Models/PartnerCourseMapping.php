<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerCourseMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_code',
        'course_id',
        'course_name_pattern',
        'learning_path_id',
        'is_active',
        'meta_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_json' => 'array',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
