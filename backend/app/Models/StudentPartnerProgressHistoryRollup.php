<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPartnerProgressHistoryRollup extends Model
{
    protected $table = 'student_partner_progress_history_rollups';

    protected $fillable = [
        'student_partner_progress_id',
        'user_id',
        'partner_code',
        'course_id',
        'period_date',
        'granularity',
        'last_captured_at',
        'overall_progress_percent',
        'metrics_json',
    ];

    protected $casts = [
        'period_date' => 'date',
        'last_captured_at' => 'datetime',
        'overall_progress_percent' => 'float',
        'metrics_json' => 'array',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(StudentPartnerProgress::class, 'student_partner_progress_id');
    }
}
