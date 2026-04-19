<?php

namespace App\Console\Commands;

use App\Models\District;
use App\Models\User;
use App\Models\UserAdmission;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Statamic\Facades\Entry;

class SyncPublicStatisticsMetricsCommand extends Command
{
    protected $signature = 'metrics:sync-public-statistics';

    protected $description = 'Update read-only live metric fields on the Homepage Statistics block entry from the database';

    /**
     * Blocks collection entry ID for content/collections/blocks/statistics.md (textdatablock).
     */
    private const STATISTICS_BLOCK_ENTRY_ID = 'bb2b7ca1-7d87-4c3a-abb8-9cf7f73186fd';

    public function handle(): int
    {
        $entry = Entry::find(self::STATISTICS_BLOCK_ENTRY_ID);

        if (! $entry) {
            $this->error('Statistics block entry not found. Check STATISTICS_BLOCK_ENTRY_ID matches your Statamic entry.');

            return self::FAILURE;
        }

        $usersRegisteredCount = User::query()->count();
        $districts = District::query()
            ->whereHas('centres', fn ($q) => $q->where('is_ready', true))
            ->count();
        $studentsTrainedCount = UserAdmission::query()->whereNotNull('confirmed')->count();

        $usersRegistered = $this->formatCompactCount($usersRegisteredCount);
        $studentsTrained = $this->formatCompactCount($studentsTrainedCount);

        $entry->set('live_users_registered', $usersRegistered);
        $entry->set('live_districts', $districts);
        $entry->set('live_students_trained', $studentsTrained);

        $numbersByMetricId = [
            'live_users_registered' => $usersRegistered,
            'live_students_trained' => $studentsTrained,
            'live_districts' => (string) $districts,
        ];

        $rawMetrics = $entry->get('metrics');
        if ($rawMetrics instanceof Collection) {
            $metrics = $rawMetrics->all();
        } elseif (is_array($rawMetrics)) {
            $metrics = $rawMetrics;
        } else {
            $metrics = [];
        }

        foreach ($metrics as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $metricId = $row['id'] ?? null;
            if ($metricId !== null && isset($numbersByMetricId[$metricId])) {
                $metrics[$index]['number'] = $numbersByMetricId[$metricId];
            }
        }

        $entry->set('metrics', $metrics);
        $entry->save();

        $this->info("Synced: users={$usersRegistered} ({$usersRegisteredCount}), districts={$districts}, students={$studentsTrained} ({$studentsTrainedCount})");

        return self::SUCCESS;
    }

    /**
     * Format large counts for display (e.g. 1.2K, 155K, 1.5M).
     */
    private function formatCompactCount(int $n): string
    {
        if ($n >= 1_000_000) {
            return $this->formatWithSuffix($n, 1_000_000, 'M');
        }
        if ($n >= 1_000) {
            return $this->formatWithSuffix($n, 1_000, 'K');
        }

        return (string) $n;
    }

    private function formatWithSuffix(int $n, int $divisor, string $suffix): string
    {
        $v = $n / $divisor;
        $rounded = round($v, 1);

        if (abs($rounded - (int) $rounded) < 0.001) {
            return ((string) (int) $rounded).$suffix;
        }

        return rtrim(rtrim(sprintf('%.1f', $rounded), '0'), '.').$suffix;
    }
}
