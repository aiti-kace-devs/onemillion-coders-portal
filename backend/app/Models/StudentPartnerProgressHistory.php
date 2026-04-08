<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Merge daily rollups (cold window) with raw history (hot window) for charts.
     * Returns the most recent {@see $limit} points by captured time.
     */
    public static function combinedSeriesForSnapshot(int $snapshotId, ?int $limit = null): Collection
    {
        $limit = $limit ?? (int) config('services.partner_history_retention.visualization_point_limit', 180);
        $retentionEnabled = (bool) config('services.partner_history_retention.enabled', true);

        $fullRawLatest = function () use ($snapshotId, $limit): Collection {
            return static::query()
                ->where('student_partner_progress_id', $snapshotId)
                ->orderBy('captured_at')
                ->get()
                ->slice(-$limit)
                ->values();
        };

        if (!$retentionEnabled || !Schema::hasTable('student_partner_progress_history_rollups')) {
            return $fullRawLatest();
        }

        $hotDays = (int) config('services.partner_history_retention.hot_days', 90);
        $cutoff = now()->subDays($hotDays);

        $coldRawStillPresent = static::query()
            ->where('student_partner_progress_id', $snapshotId)
            ->where('captured_at', '<', $cutoff)
            ->exists();

        if ($coldRawStillPresent) {
            return $fullRawLatest();
        }

        $raw = static::query()
            ->where('student_partner_progress_id', $snapshotId)
            ->where('captured_at', '>=', $cutoff)
            ->orderBy('captured_at')
            ->get();

        $rollupRows = StudentPartnerProgressHistoryRollup::query()
            ->where('student_partner_progress_id', $snapshotId)
            ->where('last_captured_at', '<', $cutoff)
            ->orderBy('last_captured_at')
            ->get();

        $synthetic = $rollupRows->map(function (StudentPartnerProgressHistoryRollup $r) {
            $metrics = is_array($r->metrics_json) ? $r->metrics_json : [];
            $h = new static([
                'student_partner_progress_id' => $r->student_partner_progress_id,
                'user_id' => $r->user_id,
                'partner_code' => $r->partner_code,
                'course_id' => $r->course_id,
                'captured_at' => $r->last_captured_at,
                'overall_progress_percent' => $r->overall_progress_percent,
                'payload_json' => [
                    'selected_metrics' => $metrics,
                ],
            ]);
            $h->exists = false;

            return $h;
        });

        return $synthetic->merge($raw)
            ->sortBy(fn (self $row) => $row->captured_at?->timestamp ?? 0)
            ->values()
            ->slice(-$limit)
            ->values();
    }
}
