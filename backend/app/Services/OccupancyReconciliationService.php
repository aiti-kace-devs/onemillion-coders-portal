<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\CarbonPeriod;
use Closure;
use Illuminate\Support\Facades\DB;

class OccupancyReconciliationService
{
    public function confirmedCentreCapacityBookingCount(): int
    {
        return $this->confirmedCentreCapacityBookings()->count();
    }

    /**
     * @return array<string, array{date: string, centre_id: int, master_session_id: int, course_type: string, occupied_count: int, protocol_occupied_count: int}>
     */
    public function expectedRows(?Closure $onBookingProcessed = null): array
    {
        $rows = [];

        $this->confirmedCentreCapacityBookings()
            ->with('programmeBatch')
            ->orderBy('id')
            ->chunkById(500, function ($bookings) use (&$rows, $onBookingProcessed) {
                foreach ($bookings as $booking) {
                    $batch = $booking->programmeBatch;
                    if (!$batch || !$batch->start_date || !$batch->end_date) {
                        $onBookingProcessed?->__invoke();
                        continue;
                    }

                    $period = CarbonPeriod::create($batch->start_date, $batch->end_date);

                    foreach ($period as $date) {
                        $key = $this->rowKey(
                            $date->toDateString(),
                            (int) $booking->centre_id,
                            (int) $booking->master_session_id
                        );

                        if (!isset($rows[$key])) {
                            $rows[$key] = [
                                'date' => $date->toDateString(),
                                'centre_id' => (int) $booking->centre_id,
                                'master_session_id' => (int) $booking->master_session_id,
                                'course_type' => (string) $booking->course_type,
                                'occupied_count' => 0,
                                'protocol_occupied_count' => 0,
                            ];
                        }

                        $rows[$key]['occupied_count']++;
                        if ($this->bookingUsesReservedPool($booking)) {
                            $rows[$key]['protocol_occupied_count']++;
                        }
                    }

                    $onBookingProcessed?->__invoke();
                }
            });

        return $rows;
    }

    /**
     * @return array<string, array{date: string, centre_id: int, master_session_id: int, course_type: string, occupied_count: int, protocol_occupied_count: int}>
     */
    public function actualRows(): array
    {
        return DB::table('daily_session_occupancy')
            ->get(['date', 'centre_id', 'master_session_id', 'course_type', 'occupied_count', 'protocol_occupied_count'])
            ->mapWithKeys(function ($row) {
                $date = (string) $row->date;
                $centreId = (int) $row->centre_id;
                $masterSessionId = (int) $row->master_session_id;

                return [
                    $this->rowKey($date, $centreId, $masterSessionId) => [
                        'date' => $date,
                        'centre_id' => $centreId,
                        'master_session_id' => $masterSessionId,
                        'course_type' => (string) $row->course_type,
                        'occupied_count' => max(0, (int) $row->occupied_count),
                        'protocol_occupied_count' => max(0, (int) $row->protocol_occupied_count),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, array<string, int|string>>  $expectedRows
     * @param  array<string, array<string, int|string>>  $actualRows
     * @return array{mismatch_count: int, samples: array<int, array<string, mixed>>}
     */
    public function diffRows(array $expectedRows, array $actualRows, int $sampleLimit = 20): array
    {
        $keys = array_unique(array_merge(array_keys($expectedRows), array_keys($actualRows)));
        sort($keys);

        $mismatchCount = 0;
        $samples = [];

        foreach ($keys as $key) {
            $expected = $expectedRows[$key] ?? null;
            $actual = $actualRows[$key] ?? null;

            if ($expected === $actual) {
                continue;
            }

            $mismatchCount++;

            if (count($samples) < $sampleLimit) {
                $samples[] = [
                    'key' => $key,
                    'expected' => $expected,
                    'actual' => $actual,
                ];
            }
        }

        return [
            'mismatch_count' => $mismatchCount,
            'samples' => $this->describeSamples($samples),
        ];
    }

    /**
     * @param  array<int, array{key: string, expected: ?array<string, int|string>, actual: ?array<string, int|string>}>  $samples
     * @return array<int, array<string, mixed>>
     */
    private function describeSamples(array $samples): array
    {
        return array_map(function (array $sample) {
            [$date, $centreId, $masterSessionId] = $this->parseRowKey($sample['key']);
            $expected = $sample['expected'];
            $actual = $sample['actual'];
            $row = $expected ?: $actual ?: [];

            $expectedUsed = $expected ? (int) ($expected['occupied_count'] ?? 0) : 0;
            $actualUsed = $actual ? (int) ($actual['occupied_count'] ?? 0) : 0;
            $expectedReserved = $expected ? (int) ($expected['protocol_occupied_count'] ?? 0) : 0;
            $actualReserved = $actual ? (int) ($actual['protocol_occupied_count'] ?? 0) : 0;

            $centre = $this->centreLabel($centreId);
            $session = $this->sessionLabel($masterSessionId);
            $context = $this->bookingContext($date, $centreId, $masterSessionId);
            $issue = $this->issueText($expected, $actual, $expectedUsed, $actualUsed, $expectedReserved, $actualReserved);

            return $sample + [
                'date' => $date,
                'centre_id' => $centreId,
                'centre_name' => $centre,
                'master_session_id' => $masterSessionId,
                'session_name' => $session,
                'course_type' => ucfirst((string) ($row['course_type'] ?? 'Unknown')),
                'course_names' => $context['course_names'],
                'cohort_names' => $context['cohort_names'],
                'programme_batch_labels' => $context['programme_batch_labels'],
                'issue' => $issue,
                'correct_display' => "{$expectedUsed} total used, {$expectedReserved} reserved/protocol used",
                'current_display' => $actual
                    ? "{$actualUsed} total used, {$actualReserved} reserved/protocol used"
                    : 'Not currently shown',
                'expected_used' => $expectedUsed,
                'actual_used' => $actualUsed,
                'expected_reserved_used' => $expectedReserved,
                'actual_reserved_used' => $actualReserved,
            ];
        }, $samples);
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    private function parseRowKey(string $key): array
    {
        $parts = explode('|', $key);

        return [
            $parts[0] ?? 'Unknown date',
            (int) ($parts[1] ?? 0),
            (int) ($parts[2] ?? 0),
        ];
    }

    private function centreLabel(int $centreId): string
    {
        $title = DB::table('centres')->where('id', $centreId)->value('title');

        return $title ? "{$title} (ID {$centreId})" : "Centre ID {$centreId}";
    }

    private function sessionLabel(int $masterSessionId): string
    {
        $session = DB::table('master_sessions')
            ->where('id', $masterSessionId)
            ->first(['master_name', 'session_type', 'time']);

        if (! $session) {
            return "Session ID {$masterSessionId}";
        }

        $parts = array_filter([
            $session->master_name ?? null,
            $session->session_type ?? null,
            $session->time ?? null,
        ]);

        return trim(implode(' / ', $parts))." (ID {$masterSessionId})";
    }

    /**
     * @return array{course_names: array<int, string>, cohort_names: array<int, string>, programme_batch_labels: array<int, string>}
     */
    private function bookingContext(string $date, int $centreId, int $masterSessionId): array
    {
        $rows = DB::table('bookings as b')
            ->join('programme_batches as pb', 'pb.id', '=', 'b.programme_batch_id')
            ->leftJoin('courses as c', 'c.id', '=', 'b.course_id')
            ->leftJoin('programmes as p', 'p.id', '=', 'pb.programme_id')
            ->leftJoin('admission_batches as ab', 'ab.id', '=', 'pb.admission_batch_id')
            ->where('b.status', true)
            ->where('b.centre_id', $centreId)
            ->where('b.master_session_id', $masterSessionId)
            ->whereDate('pb.start_date', '<=', $date)
            ->whereDate('pb.end_date', '>=', $date)
            ->limit(10)
            ->get([
                'c.id as course_id',
                'c.course_name',
                'p.title as programme_title',
                'pb.id as programme_batch_id',
                'pb.start_date',
                'pb.end_date',
                'ab.title as admission_batch_title',
                'ab.batch_number',
                'ab.year',
            ]);

        if ($rows->isEmpty()) {
            return [
                'course_names' => ['No confirmed booking found for this displayed row'],
                'cohort_names' => ['No active cohort found for this displayed row'],
                'programme_batch_labels' => ['No programme batch found'],
            ];
        }

        $courseNames = [];
        $cohortNames = [];
        $programmeBatchLabels = [];

        foreach ($rows as $row) {
            $courseNames[] = $row->course_name
                ? "{$row->course_name} (ID {$row->course_id})"
                : ($row->programme_title ? "{$row->programme_title} (course unknown)" : 'Course unknown');

            $cohortLabel = $row->admission_batch_title ?: 'Cohort';
            if ($row->batch_number) {
                $cohortLabel .= " #{$row->batch_number}";
            }
            if ($row->year) {
                $cohortLabel .= " ({$row->year})";
            }
            $cohortNames[] = $cohortLabel;

            $programmeBatchLabels[] = "Programme batch {$row->programme_batch_id}: {$row->start_date} to {$row->end_date}";
        }

        return [
            'course_names' => array_values(array_unique($courseNames)),
            'cohort_names' => array_values(array_unique($cohortNames)),
            'programme_batch_labels' => array_values(array_unique($programmeBatchLabels)),
        ];
    }

    /**
     * @param  ?array<string, int|string>  $expected
     * @param  ?array<string, int|string>  $actual
     */
    private function issueText(?array $expected, ?array $actual, int $expectedUsed, int $actualUsed, int $expectedReserved, int $actualReserved): string
    {
        if ($expected === null && $actual !== null) {
            return "The system is showing {$actualUsed} used slot(s), but there are no confirmed bookings for this centre, session, and date. This is likely a stale displayed-count row.";
        }

        if ($expected !== null && $actual === null) {
            return "There are {$expectedUsed} confirmed used slot(s), but the displayed-count row is missing. Learners may see too many available slots.";
        }

        if ($actualUsed > $expectedUsed) {
            $difference = $actualUsed - $expectedUsed;

            return "Learners may see {$difference} fewer available slot(s) than they should, because the displayed used-slot count is too high.";
        }

        if ($actualUsed < $expectedUsed) {
            $difference = $expectedUsed - $actualUsed;

            return "Learners may see {$difference} more available slot(s) than they should, because the displayed used-slot count is too low.";
        }

        if ($actualReserved !== $expectedReserved) {
            return 'The total used-slot count is correct, but the reserved/protocol portion is different. Protocol availability may display incorrectly.';
        }

        return 'Displayed availability slot count does not match confirmed bookings.';
    }

    private function confirmedCentreCapacityBookings()
    {
        return Booking::query()
            ->whereNotNull('master_session_id')
            ->whereNotNull('centre_id')
            ->whereNotNull('course_type')
            ->where('status', true);
    }

    private function bookingUsesReservedPool(Booking $booking): bool
    {
        if ($booking->capacity_pool === Booking::CAPACITY_POOL_RESERVED) {
            return true;
        }

        return $booking->capacity_pool === null && (bool) $booking->is_protocol;
    }

    private function rowKey(string $date, int $centreId, int $masterSessionId): string
    {
        return "{$date}|{$centreId}|{$masterSessionId}";
    }
}
