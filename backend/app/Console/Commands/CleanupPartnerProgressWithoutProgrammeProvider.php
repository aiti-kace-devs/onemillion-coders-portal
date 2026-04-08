<?php

namespace App\Console\Commands;

use App\Models\StudentPartnerProgress;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CleanupPartnerProgressWithoutProgrammeProvider extends Command
{
    protected $signature = 'partner:cleanup-progress-without-programme-provider
        {--dry-run : Show how many rows would be deleted; no deletes}
        {--chunk=200 : Delete student_partner_progress in batches of this size (by id)}
        {--force : Skip confirmation (use with care)}';

    protected $description = 'Delete student_partner_progress (and cascading history/rollups) for courses with no programme provider or no programme';

    public function handle(): int
    {
        if (! Schema::hasTable('student_partner_progress')) {
            $this->error('Table `student_partner_progress` does not exist.');

            return self::FAILURE;
        }

        $total = $this->nonCompliantSnapshotsQuery()->count();

        if ($total === 0) {
            $this->info('No student_partner_progress rows match (all tied to programmes with a non-empty provider).');

            return self::SUCCESS;
        }

        $this->warn(sprintf(
            'Found %d student_partner_progress row(s) whose course has no programme, or programme.provider is empty/whitespace.',
            $total
        ));

        if ($this->option('dry-run')) {
            $sample = $this->nonCompliantSnapshotsQuery()
                ->orderBy('id')
                ->limit(15)
                ->get(['id', 'user_id', 'partner_code', 'course_id', 'omcp_id']);
            $this->table(
                ['id', 'user_id', 'partner_code', 'course_id', 'omcp_id'],
                $sample->map(fn ($r) => [$r->id, $r->user_id, $r->partner_code, $r->course_id, $r->omcp_id])->all()
            );
            $this->info('Dry run: no rows deleted. Remove --dry-run to delete.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            if (! $this->confirm('Permanently delete these snapshots (history/rollups cascade)?')) {
                $this->info('Aborted.');

                return self::FAILURE;
            }
        }

        $chunk = max(1, min(5000, (int) $this->option('chunk')));
        $deleted = 0;

        do {
            $ids = $this->nonCompliantSnapshotsQuery()->orderBy('id')->limit($chunk)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }

            $n = StudentPartnerProgress::query()->whereIn('id', $ids)->delete();
            $deleted += $n;
            $this->line(sprintf('Deleted %d snapshot(s); running total %d.', $n, $deleted));
        } while ($ids->count() === $chunk);

        $this->info(sprintf('Finished. Removed %d student_partner_progress row(s) (dependent history/rollups removed by FK cascade).', $deleted));

        return self::SUCCESS;
    }

    /**
     * Snapshots to remove: no course, missing course row, course without programme, or programme.provider blank.
     */
    private function nonCompliantSnapshotsQuery(): Builder
    {
        return StudentPartnerProgress::query()
            ->where(function ($q) {
                $q->whereNull('course_id')
                    ->orWhereDoesntHave('course')
                    ->orWhereHas('course', function ($cq) {
                        $cq->whereNull('programme_id');
                    })
                    ->orWhereHas('course.programme', function ($pq) {
                        $pq->whereRaw("TRIM(COALESCE(provider, '')) = ''");
                    });
            });
    }
}
