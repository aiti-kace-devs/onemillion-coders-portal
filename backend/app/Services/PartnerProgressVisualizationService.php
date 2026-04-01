<?php

namespace App\Services;

use App\Models\StudentPartnerProgress;
use App\Models\StudentPartnerProgressHistory;
use Illuminate\Support\Collection;

class PartnerProgressVisualizationService
{
    public function buildStudentProgressPayload(?StudentPartnerProgress $snapshot, Collection $history): array
    {
        $summary = $snapshot?->progress_summary_json ?? [];
        $selected = is_array($summary['selected'] ?? null) ? $summary['selected'] : [];
        $activityKeys = $this->extractActivityKeys($selected, $history);

        $activities = collect($activityKeys)->map(function (string $key, int $index) use ($selected) {
            return [
                'key' => $key,
                'label' => $this->labelFromKey($key),
                'current' => (float) ($selected[$key] ?? 0),
                'color' => $this->palette($index),
            ];
        })->values()->all();

        $historyRows = $history->map(function (StudentPartnerProgressHistory $row) {
            return [
                'captured_at' => $row->captured_at?->toIso8601String(),
                'overall_progress_percent' => (float) ($row->overall_progress_percent ?? 0),
                'selected_metrics' => is_array($row->payload_json['selected_metrics'] ?? null)
                    ? $row->payload_json['selected_metrics']
                    : [],
            ];
        })->values()->all();

        return [
            'snapshot' => $snapshot ? [
                'overall_progress_percent' => (float) ($snapshot->overall_progress_percent ?? 0),
                'last_activity_at' => optional($snapshot->last_activity_at)->toIso8601String(),
                'last_synced_at' => optional($snapshot->last_synced_at)->toIso8601String(),
                'stale_after_at' => optional($snapshot->stale_after_at)->toIso8601String(),
                'is_stale' => $snapshot->stale_after_at ? $snapshot->stale_after_at->lte(now()) : false,
            ] : null,
            'activities' => $activities,
            'history' => $historyRows,
        ];
    }

    private function extractActivityKeys(array $selected, Collection $history): array
    {
        $keys = collect(array_keys($selected))
            ->filter(fn(string $k) => str_ends_with($k, '_percentage_complete'))
            ->values();

        if ($keys->isNotEmpty()) {
            return $keys->all();
        }

        $fromHistory = $history->flatMap(function (StudentPartnerProgressHistory $row) {
            $selectedMetrics = is_array($row->payload_json['selected_metrics'] ?? null) ? $row->payload_json['selected_metrics'] : [];
            return collect(array_keys($selectedMetrics));
        })->filter(fn(string $k) => str_ends_with($k, '_percentage_complete'))->unique()->values();

        if ($fromHistory->isNotEmpty()) {
            return $fromHistory->all();
        }

        return [
            'video_percentage_complete',
            'quiz_percentage_complete',
            'project_percentage_complete',
            'task_percentage_complete',
        ];
    }

    private function labelFromKey(string $key): string
    {
        return str($key)
            ->replace('_percentage_complete', '')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function palette(int $index): string
    {
        $colors = [
            'rgba(230, 25, 75, 1)',
            'rgba(60, 180, 75, 1)',
            'rgba(0, 130, 200, 1)',
            'rgba(245, 130, 48, 1)',
            'rgba(145, 30, 180, 1)',
            'rgba(70, 240, 240, 1)',
            'rgba(240, 50, 230, 1)',
            'rgba(210, 245, 60, 1)',
        ];

        return $colors[$index % count($colors)];
    }
}
