<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPartnerProgressHistory extends Model
{
    use HasFactory;

    protected $table = 'student_partner_progress_history';

    protected $fillable = [
        'student_partner_progress_id',
        'user_id',
        'partner_code',
        'course_id',
        'captured_at',
        'overall_progress_percent',
        'video_percentage_complete',
        'quiz_percentage_complete',
        'project_percentage_complete',
        'task_percentage_complete',
        'payload_json',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'overall_progress_percent' => 'float',
        'video_percentage_complete' => 'float',
        'quiz_percentage_complete' => 'float',
        'project_percentage_complete' => 'float',
        'task_percentage_complete' => 'float',
        'payload_json' => 'array',
    ];

    public function snapshot()
    {
        return $this->belongsTo(StudentPartnerProgress::class, 'student_partner_progress_id');
    }
}
