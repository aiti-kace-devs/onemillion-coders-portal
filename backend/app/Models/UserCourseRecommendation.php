<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCourseRecommendation extends Model
{
    protected $table = 'user_course_recommendations';

    protected $fillable = [
        'user_id',
        'course_id',
        'rank',
        'match_percentage',
        'option_ids',
    ];

    protected $casts = [
        'option_ids' => 'array',
        'match_percentage' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }
}
