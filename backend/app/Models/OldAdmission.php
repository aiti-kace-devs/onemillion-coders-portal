<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OldAdmission extends Model
{
    protected $table = 'old_admissions';

    protected $fillable = [
        'user_id',
        'course_id',
        'centre_id',
        'batch_id',
        'confirmed',
        'email_sent',
        'session',
        'status',
        'support_status',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'confirmed' => 'datetime',
        'email_sent' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'support_status' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function sessionRecord()
    {
        return $this->belongsTo(CourseSession::class, 'session');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }
}
