<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCompleted extends Model
{
    use HasFactory;
    protected $table = 'course_completed';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'course_id',
        'completed_at',
        // 'status'
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }
}
