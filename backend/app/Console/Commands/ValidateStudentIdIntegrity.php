<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateStudentIdIntegrity extends Command
{
    protected $signature = 'students:validate-id-integrity
        {--limit=20 : Max example rows per issue}
        {--json : Output machine-readable JSON summary}';

    protected $description = 'Validate admitted student ID integrity, uniqueness, and batch/year alignment.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        foreach (['users', 'user_admission', 'admission_batches'] as $table) {
            if ($table === 'admission_batches') {
                continue;
            }
            if (!Schema::hasTable($table)) {
                $this->error("Required table '{$table}' not found.");
                return self::FAILURE;
            }
        }

        $hasAdmissionBatches = Schema::hasTable('admission_batches');
        $hasBatchIdOnAdmissions = Schema::hasColumn('user_admission', 'batch_id');

        // "Admitted" in this project generally means confirmed is not null.
        $query = DB::table('user_admission as ua')
            ->leftJoin('users as u', 'u.userId', '=', 'ua.user_id')
            ->whereNotNull('ua.confirmed')
            ->select([
                'ua.id as admission_id',
                'ua.user_id as admission_user_id',
                'ua.course_id',
                'ua.confirmed',
                'u.id as user_pk',
                'u.userId as user_uid',
                'u.student_id',
                'u.email',
            ]);

        if ($hasBatchIdOnAdmissions) {
            $query->addSelect('ua.batch_id');
        } else {
            $query->selectRaw('NULL as batch_id');
        }

        if ($hasAdmissionBatches && $hasBatchIdOnAdmissions) {
            $query->leftJoin('admission_batches as ab', 'ab.id', '=', 'ua.batch_id');
            $query->addSelect([
                'ab.year as batch_year',
                'ab.start_date as batch_start_date',
            ]);
        } else {
            $query->selectRaw('NULL as batch_year');
            $query->selectRaw('NULL as batch_start_date');
        }

        $admittedRows = $query->get();

        $issues = [
            'missing_user_for_admission' => [],
            'missing_student_id' => [],
            'malformed_student_id' => [],
            'student_id_batch_mismatch' => [],
            'student_id_year_mismatch' => [],
            'user_uid_student_id_mismatch' => [],
            'duplicate_student_id_among_admitted' => [],
            'duplicate_user_uid_among_admitted' => [],
            'multiple_admissions_same_user_same_batch' => [],
        ];

        $studentIdPattern = '/^\d{3}-\d{2}-\d{5}$/';
        $studentIdSeen = [];
        $userUidSeen = [];
        $userBatchCounter = [];

        foreach ($admittedRows as $row) {
            if ($row->user_pk === null) {
                $issues['missing_user_for_admission'][] = $this->rowRef($row);
                continue;
            }

            $studentId = trim((string) ($row->student_id ?? ''));
            $userUid = trim((string) ($row->user_uid ?? ''));

            if ($studentId === '') {
                $issues['missing_student_id'][] = $this->rowRef($row);
            } elseif (!preg_match($studentIdPattern, $studentId)) {
                $issues['malformed_student_id'][] = $this->rowRef($row) + ['student_id' => $studentId];
            } else {
                [$sidBatch, $sidYear] = explode('-', $studentId, 3);
                if ($row->batch_id !== null && (int) $sidBatch !== (int) $row->batch_id) {
                    $issues['student_id_batch_mismatch'][] = $this->rowRef($row) + [
                        'student_id' => $studentId,
                        'expected_batch_id' => (int) $row->batch_id,
                    ];
                }

                $expectedYear2 = $this->resolveBatchYearTwoDigits($row->batch_year, $row->batch_start_date);
                if ($expectedYear2 !== null && $sidYear !== $expectedYear2) {
                    $issues['student_id_year_mismatch'][] = $this->rowRef($row) + [
                        'student_id' => $studentId,
                        'expected_year_suffix' => $expectedYear2,
                    ];
                }
            }

            if ($studentId !== '' && $userUid !== '' && $studentId !== $userUid) {
                $issues['user_uid_student_id_mismatch'][] = $this->rowRef($row) + [
                    'student_id' => $studentId,
                    'user_uid' => $userUid,
                ];
            }

            if ($studentId !== '') {
                if (!array_key_exists($studentId, $studentIdSeen)) {
                    $studentIdSeen[$studentId] = $this->rowRef($row);
                } else {
                    $issues['duplicate_student_id_among_admitted'][] = [
                        'student_id' => $studentId,
                        'first' => $studentIdSeen[$studentId],
                        'second' => $this->rowRef($row),
                    ];
                }
            }

            if ($userUid !== '') {
                if (!array_key_exists($userUid, $userUidSeen)) {
                    $userUidSeen[$userUid] = $this->rowRef($row);
                } else {
                    $issues['duplicate_user_uid_among_admitted'][] = [
                        'user_uid' => $userUid,
                        'first' => $userUidSeen[$userUid],
                        'second' => $this->rowRef($row),
                    ];
                }
            }

            $batchKey = (string) ($row->batch_id ?? 'null');
            $ubKey = $userUid . '|' . $batchKey;
            $userBatchCounter[$ubKey] = ($userBatchCounter[$ubKey] ?? 0) + 1;
            if ($userBatchCounter[$ubKey] > 1) {
                $issues['multiple_admissions_same_user_same_batch'][] = $this->rowRef($row) + [
                    'user_uid' => $userUid,
                    'batch_id' => $row->batch_id,
                    'admissions_for_user_batch' => $userBatchCounter[$ubKey],
                ];
            }
        }

        $summary = [
            'checked_at' => now()->toIso8601String(),
            'admitted_rows_checked' => $admittedRows->count(),
            'issues' => [],
            'status' => 'ok',
        ];

        foreach ($issues as $issueKey => $rows) {
            $count = count($rows);
            $summary['issues'][$issueKey] = [
                'count' => $count,
                'examples' => array_slice($rows, 0, $limit),
            ];
            if ($count > 0) {
                $summary['status'] = 'issues_found';
            }
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT));
        } else {
            $this->info('Student ID integrity validation complete.');
            $this->line('Admitted rows checked: ' . $summary['admitted_rows_checked']);
            foreach ($summary['issues'] as $issueKey => $payload) {
                $count = (int) $payload['count'];
                if ($count === 0) {
                    $this->line("  - {$issueKey}: 0");
                    continue;
                }

                $this->warn("  - {$issueKey}: {$count}");
                foreach ($payload['examples'] as $example) {
                    $this->line('    example: ' . json_encode($example, JSON_UNESCAPED_SLASHES));
                }
            }
        }

        return $summary['status'] === 'ok' ? self::SUCCESS : self::FAILURE;
    }

    private function resolveBatchYearTwoDigits(mixed $batchYear, mixed $batchStartDate): ?string
    {
        $year = trim((string) ($batchYear ?? ''));
        if ($year === '' && !empty($batchStartDate)) {
            $year = substr((string) $batchStartDate, 0, 4);
        }
        if ($year === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $year);
        if ($digits === '') {
            return null;
        }

        return substr($digits, -2);
    }

    /**
     * @return array<string,mixed>
     */
    private function rowRef(object $row): array
    {
        return [
            'admission_id' => $row->admission_id,
            'email' => $row->email,
            'admission_user_id' => $row->admission_user_id,
            'user_uid' => $row->user_uid,
            'student_id' => $row->student_id,
            'batch_id' => $row->batch_id,
            'course_id' => $row->course_id,
        ];
    }
}

