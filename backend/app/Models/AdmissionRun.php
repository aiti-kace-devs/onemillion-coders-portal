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
        // Invalidate cache
        $this->invalidateCache();

        // Calculate stats with a single optimized query
        $stats = UserAdmission::where('admission_run_id', $this->id)
            ->selectRaw('
            COUNT(*) as admitted_count,
            COUNT(confirmed) as accepted_count,
            COUNT(*) - COUNT(confirmed) as rejected_count,
            SUM(CASE WHEN admission_source = "manual" THEN 1 ELSE 0 END) as manual_count,
            SUM(CASE WHEN admission_source = "automated" THEN 1 ELSE 0 END) as automated_count,
            SUM(CASE WHEN email_sent = 1 THEN 1 ELSE 0 END) as emailed_count
        ')
            ->first();

        // Cache the results for 10 minutes
        $cacheKey = "admission_stats:{$this->course_id}:{$this->batch_id}";
        Cache::put($cacheKey, $stats, 600);

        // Bulk update all admissions in a single query
        UserAdmission::where('admission_run_id', $this->id)
            ->update([
                'admitted_count' => $stats->admitted_count,
                'accepted_count' => $stats->accepted_count,
                'rejected_count' => $stats->rejected_count,
                'manual_count' => $stats->manual_count,
                'automated_count' => $stats->automated_count,
                'emailed_count' => $stats->emailed_count,
            ]);
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
