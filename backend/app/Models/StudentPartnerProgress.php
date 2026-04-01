<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPartnerProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'partner_code',
        'omcp_id',
        'course_id',
        'learning_path_id',
        'partner_student_ref',
        'progress_summary_json',
        'progress_raw_json',
        'overall_progress_percent',
        'last_activity_at',
        'last_synced_at',
        'last_sync_attempt_at',
        'stale_after_at',
        'last_reminder_sent_at',
        'reminder_count',
        'last_sync_error',
    ];

    protected $casts = [
        'progress_summary_json' => 'array',
        'progress_raw_json' => 'array',
        'overall_progress_percent' => 'float',
        'last_activity_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'last_sync_attempt_at' => 'datetime',
        'stale_after_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function history()
    {
        return $this->hasMany(StudentPartnerProgressHistory::class, 'student_partner_progress_id');
    }
}
