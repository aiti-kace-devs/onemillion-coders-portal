<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class AdmissionRun extends Model
{
    use CrudTrait;
    protected $fillable = [
        'course_id',
        'batch_id',
        'run_by',
        'run_at',
        'rules_applied',
        'selected_count',
        'admitted_count',
        'emailed_count',
        'accepted_count',
        'rejected_count',
        'manual_count',
        'automated_count',
        'preview_data',
        'status',
        'error_message',
    ];

    protected $casts = [
        'run_at' => 'datetime',
        'rules_applied' => 'array',
        'preview_data' => 'array',
    ];

    /**
     * Get the course for this admission run
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the batch for this admission run
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the admin who executed this run
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'run_by');
    }

    /**
     * Update statistics from UserAdmission records and invalidate cache
     */
    public function updateStats()
    {
        $admissions = UserAdmission::where('course_id', $this->course_id)
            ->where('batch_id', $this->batch_id)
            ->get();

        $this->update([
            'admitted_count' => $admissions->count(),
            'accepted_count' => $admissions->whereNotNull('confirmed')->count(),
            'rejected_count' => $admissions->whereNull('confirmed')->count(),
            'manual_count' => $admissions->where('admission_source', 'manual')->count(),
            'automated_count' => $admissions->where('admission_source', 'automated')->count(),
            'emailed_count' => $admissions->where('email_sent', true)->count(),
        ]);

        // Invalidate cache
        $this->invalidateCache();
    }

    /**
     * Invalidate admission statistics cache
     */
    public function invalidateCache()
    {
        Cache::forget("admission_stats:{$this->course_id}:{$this->batch_id}");
        Cache::forget("admission_stats:course:{$this->course_id}");
        Cache::forget("admission_stats:global");
    }
}
