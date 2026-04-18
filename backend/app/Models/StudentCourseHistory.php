<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCourseHistory extends Model
{
    protected $table = 'student_course_histories';

    protected $fillable = [
        'user_id',
        'course_id',
        'centre_id',
        'session_id',
        'batch_id',
        'status',
        'support_status',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'support_status' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    public function session()
    {
        return $this->belongsTo(CourseSession::class, 'session_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }
}
