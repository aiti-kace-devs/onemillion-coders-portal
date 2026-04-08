<?php

namespace App\Console\Commands;

use App\Models\StudentPartnerProgressHistory;
use App\Models\StudentPartnerProgressHistoryRollup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrunePartnerHistory extends Command
{
    protected $signature = 'partner:prune-history
        {--dry-run : Show counts only; do not write rollups or delete rows}
        {--days= : Override PARTNER_HISTORY_HOT_DAYS (full-resolution retention window)}
        {--batch= : Override delete batch size}
        {--skip-rollup : Delete cold rows without writing rollups (data loss)}';

    protected $description = 'Roll up partner progress history older than the hot window, then delete cold raw rows in batches';

    public function handle(): int
    {
        if (!(bool) config('services.partner_history_retention.enabled', true)) {
            $this->info('Partner history retention is disabled.');
            return self::SUCCESS;
        }

        $hotDays = (int) ($this->option('days') ?: config('services.partner_history_retention.hot_days', 90));
        $batch = (int) ($this->option('batch') ?: config('services.partner_history_retention.prune_batch_size', 1000));
        $dryRun = (bool) $this->option('dry-run');
        $skipRollup = (bool) $this->option('skip-rollup');

        if ($hotDays < 1) {
            $this->error('--days must be at least 1.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($hotDays);
        $this->info(sprintf('Hot window: captured_at >= %s (%d day(s)).', $cutoff->toIso8601String(), $hotDays));

        $coldCount = StudentPartnerProgressHistory::query()
            ->where('captured_at', '<', $cutoff)
            ->count();

        if ($coldCount === 0) {
            $this->info('No cold history rows to process.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Cold history rows (captured_at < cutoff): %d', $coldCount));

        if ($dryRun) {
            $this->warn('Dry run: no rollups or deletes performed.');
            return self::SUCCESS;
        }

        if ($skipRollup) {
            if (!$this->confirm('Skip rollup will permanently delete cold rows without daily aggregates. Continue?')) {
                return self::FAILURE;
            }
        } else {
            $rolled = $this->rollupColdHistory($cutoff);
            $this->info(sprintf('Rollups upserted: %d', $rolled));
        }

        $deletedTotal = 0;
        do {
            $ids = StudentPartnerProgressHistory::query()
                ->where('captured_at', '<', $cutoff)
                ->orderBy('id')
                ->limit($batch)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted = StudentPartnerProgressHistory::query()->whereIn('id', $ids)->delete();
            $deletedTotal += $deleted;
            $this->line(sprintf('Deleted %d cold row(s) (total %d).', $deleted, $deletedTotal));
        } while ($ids->count() === $batch);

        $this->info(sprintf('Done. Deleted %d cold history row(s) total.', $deletedTotal));

        return self::SUCCESS;
    }

    private function rollupColdHistory(\DateTimeInterface $cutoff): int
    {
        $snapshotIds = StudentPartnerProgressHistory::query()
            ->where('captured_at', '<', $cutoff)
            ->distinct()
            ->pluck('student_partner_progress_id');

        $upserted = 0;

        foreach ($snapshotIds as $snapshotId) {
            $dates = DB::table('student_partner_progress_history')
                ->where('student_partner_progress_id', $snapshotId)
                ->where('captured_at', '<', $cutoff)
                ->selectRaw('DATE(captured_at) as d')
                ->groupBy(DB::raw('DATE(captured_at)'))
                ->pluck('d');

            foreach ($dates as $periodDate) {
                $row = StudentPartnerProgressHistory::query()
                    ->where('student_partner_progress_id', $snapshotId)
                    ->where('captured_at', '<', $cutoff)
                    ->whereRaw('DATE(captured_at) = ?', [$periodDate])
                    ->orderByDesc('captured_at')
                    ->orderByDesc('id')
                    ->first();

                if (!$row) {
                    continue;
                }

                $metrics = [];
                if (is_array($row->payload_json['selected_metrics'] ?? null)) {
                    $metrics = $row->payload_json['selected_metrics'];
                }

                StudentPartnerProgressHistoryRollup::query()->updateOrCreate(
                    [
                        'student_partner_progress_id' => $row->student_partner_progress_id,
                        'period_date' => $periodDate,
                        'granularity' => 'daily',
                    ],
                    [
                        'user_id' => $row->user_id,
                        'partner_code' => $row->partner_code,
                        'course_id' => $row->course_id,
                        'last_captured_at' => $row->captured_at,
                        'overall_progress_percent' => $row->overall_progress_percent,
                        'metrics_json' => $metrics,
                    ]
                );
                $upserted++;
            }
        }

        return $upserted;
    }
}
