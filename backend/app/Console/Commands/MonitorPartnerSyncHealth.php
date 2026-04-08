<?php

namespace App\Console\Commands;

use App\Models\PartnerCourseMapping;
use App\Models\StudentPartnerProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorPartnerSyncHealth extends Command
{
    protected $signature = 'partner:monitor-sync-health
        {--partner-code= : Limit checks to a single partner_code}
        {--fail-on-alert : Exit with non-zero code when alerts are raised}
        {--json : Emit machine-readable JSON output}';

    protected $description = 'Alert on partner sync failure-rate spikes and SLA freshness breaches';

    public function handle(): int
    {
        if (!(bool) config('services.partner_monitoring.enabled', true)) {
            $this->info('Partner sync monitoring disabled.');
            return self::SUCCESS;
        }

        $partnerCodes = $this->resolvePartnerCodes();
        if ($partnerCodes === []) {
            $this->info('No partner codes found to monitor.');
            return self::SUCCESS;
        }

        $windowMinutes = (int) config('services.partner_monitoring.failure_window_minutes', 60);
        $minAttempts = (int) config('services.partner_monitoring.min_attempts_for_rate_alert', 20);
        $failureRateThreshold = (float) config('services.partner_monitoring.failure_rate_threshold', 0.35);
        $defaultSlaHours = (int) config('services.partner_monitoring.default_sla_hours', 6);

        $cutoff = now()->subMinutes($windowMinutes);
        $alertsRaised = 0;
        $report = [
            'checked_at' => now()->toIso8601String(),
            'window_minutes' => $windowMinutes,
            'min_attempts_for_rate_alert' => $minAttempts,
            'failure_rate_threshold' => $failureRateThreshold,
            'default_sla_hours' => $defaultSlaHours,
            'partners' => [],
            'alerts' => [],
        ];

        foreach ($partnerCodes as $partnerCode) {
            $attempts = StudentPartnerProgress::query()
                ->where('partner_code', $partnerCode)
                ->whereNotNull('last_sync_attempt_at')
                ->where('last_sync_attempt_at', '>=', $cutoff)
                ->count();

            $failedAttempts = StudentPartnerProgress::query()
                ->where('partner_code', $partnerCode)
                ->whereNotNull('last_sync_attempt_at')
                ->where('last_sync_attempt_at', '>=', $cutoff)
                ->whereNotNull('last_sync_error')
                ->where('last_sync_error', '!=', '')
                ->count();

            $failureRate = $attempts > 0 ? ($failedAttempts / $attempts) : 0.0;
            $partnerAlerts = [];

            if ($attempts >= $minAttempts) {
                if ($failureRate >= $failureRateThreshold) {
                    $alertsRaised++;
                    $msg = sprintf(
                        'Partner sync failure rate spike detected [partner=%s, window=%dmin, attempts=%d, failed=%d, rate=%.2f%%, threshold=%.2f%%]',
                        $partnerCode,
                        $windowMinutes,
                        $attempts,
                        $failedAttempts,
                        $failureRate * 100,
                        $failureRateThreshold * 100
                    );
                    Log::alert($msg);
                    $this->warn($msg);
                    $partnerAlerts[] = [
                        'type' => 'failure_rate_spike',
                        'message' => $msg,
                    ];
                    $report['alerts'][] = [
                        'partner_code' => $partnerCode,
                        'type' => 'failure_rate_spike',
                        'message' => $msg,
                    ];
                }
            }

            $slaHours = (int) config("services.partner_monitoring.partner_sla_hours.{$partnerCode}", $defaultSlaHours);
            $lastSuccess = StudentPartnerProgress::query()
                ->where('partner_code', $partnerCode)
                ->max('last_synced_at');

            if (!$lastSuccess || now()->diffInHours($lastSuccess) > $slaHours) {
                $alertsRaised++;
                $msg = sprintf(
                    'No successful sync within SLA window [partner=%s, last_synced_at=%s, sla_hours=%d]',
                    $partnerCode,
                    $lastSuccess ? (string) $lastSuccess : 'null',
                    $slaHours
                );
                Log::alert($msg);
                $this->warn($msg);
                $partnerAlerts[] = [
                    'type' => 'sla_breach',
                    'message' => $msg,
                ];
                $report['alerts'][] = [
                    'partner_code' => $partnerCode,
                    'type' => 'sla_breach',
                    'message' => $msg,
                ];
            }

            $report['partners'][] = [
                'partner_code' => $partnerCode,
                'attempts' => $attempts,
                'failed_attempts' => $failedAttempts,
                'failure_rate' => round($failureRate, 4),
                'sla_hours' => $slaHours,
                'last_synced_at' => $lastSuccess ? (string) $lastSuccess : null,
                'alerts' => $partnerAlerts,
            ];
        }

        if ($alertsRaised === 0) {
            $this->info('Partner sync health check passed (no alerts).');
        }

        if ((bool) $this->option('json')) {
            $report['alerts_raised'] = $alertsRaised;
            $report['status'] = $alertsRaised > 0 ? 'alert' : 'ok';
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
        }

        if ($alertsRaised > 0 && (bool) $this->option('fail-on-alert')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function resolvePartnerCodes(): array
    {
        $single = $this->option('partner-code');
        if (is_string($single) && trim($single) !== '') {
            return [trim($single)];
        }

        $fromSnapshots = StudentPartnerProgress::query()
            ->whereNotNull('partner_code')
            ->distinct()
            ->pluck('partner_code')
            ->filter(fn ($code) => is_string($code) && trim($code) !== '')
            ->map(fn ($code) => trim((string) $code))
            ->all();

        $fromMappings = PartnerCourseMapping::query()
            ->whereNotNull('partner_code')
            ->distinct()
            ->pluck('partner_code')
            ->filter(fn ($code) => is_string($code) && trim($code) !== '')
            ->map(fn ($code) => trim((string) $code))
            ->all();

        return array_values(array_unique(array_merge($fromSnapshots, $fromMappings)));
    }
}

