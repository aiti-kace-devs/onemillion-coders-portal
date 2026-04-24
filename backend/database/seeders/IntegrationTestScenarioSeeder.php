<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Programme;
use App\Models\ProgrammeBatch;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

/**
 * Realistic data for manual QA and integration-style checks (in-person availability,
 * choose-course, programme cohorts). Idempotent: safe to run multiple times.
 *
 * Prerequisites: programmes, courses, centres, admission_batches (ideally one window
 * covering today). Does not delete existing rows.
 *
 *   php artisan db:seed --class=IntegrationTestScenarioSeeder
 *
 * Or .env: SEED_INTEGRATION_TEST_DATA=true then php artisan db:seed
 */
class IntegrationTestScenarioSeeder extends Seeder
{
    private const MAX_IN_PERSON_PROGRAMMES = 8;

    private const MAX_WEEKLY_COHORTS = 6;

    public function run(): void
    {
        if (! Schema::hasTable('programme_batches') || ! Schema::hasTable('course_sessions')) {
            $this->command?->error('IntegrationTestScenarioSeeder: required tables missing. Run migrations first.');

            return;
        }

        $today = Carbon::today();
        $activeAdmission = Batch::query()
            ->where('status', true)
            ->where('completed', false)
            ->orderBy('id')
            ->first();

        if (! $activeAdmission) {
            $this->command?->warn('IntegrationTestScenarioSeeder: no admission_batches row active for today. Create or extend an intake window in admin, then re-run.');

            return;
        }

        $windowStart = Carbon::parse($activeAdmission->start_date)->startOfDay();
        $windowEnd = Carbon::parse($activeAdmission->end_date)->endOfDay();

        $programmes = Programme::query()
            ->where('status', true)
            ->whereHas('courses', function ($q) {
                $q->where('status', true)->whereNotNull('centre_id');
            })
            ->with(['courses' => function ($q) {
                $q->where('status', true)->whereNotNull('centre_id');
            }])
            ->orderBy('id')
            ->get()
            ->filter(fn (Programme $p) => $p->isInPerson())
            ->take(self::MAX_IN_PERSON_PROGRAMMES);

        if ($programmes->isEmpty()) {
            $this->command?->warn('IntegrationTestScenarioSeeder: no active in-person programmes with centre-linked courses.');

            return;
        }

        $cohortsTouched = 0;
        $sessionsTouched = 0;

        foreach ($programmes as $programme) {
            $cohortsTouched += $this->seedProgrammeCohorts($programme, $activeAdmission->id, $windowStart, $windowEnd, $today);
        }

        foreach ($programmes as $programme) {
            foreach ($programme->courses as $course) {
                $sessionsTouched += $this->seedCentreSessionsForCourse($course);
            }
        }

        $this->command?->info(sprintf(
            'IntegrationTestScenarioSeeder: admission_batch_id=%d | programme_batch firstOrCreate calls=%d | centre session firstOrCreate calls=%d | programmes=%d',
            $activeAdmission->id,
            $cohortsTouched,
            $sessionsTouched,
            $programmes->count()
        ));
    }

    private function seedProgrammeCohorts(
        Programme $programme,
        int $admissionBatchId,
        Carbon $windowStart,
        Carbon $windowEnd,
        Carbon $today
    ): int {
        $anchor = $today->copy()->max($windowStart)->startOfWeek();
        if ($anchor->gt($windowEnd)) {
            $anchor = $windowStart->copy();
        }

        $count = 0;
        $cursor = $anchor->copy();

        for ($week = 0; $week < self::MAX_WEEKLY_COHORTS; $week++) {
            $start = $cursor->copy();
            $end = $start->copy()->addDays(6);

            if ($start->lt($windowStart)) {
                $cursor->addWeek();

                continue;
            }
            if ($end->gt($windowEnd)) {
                break;
            }

            ProgrammeBatch::firstOrCreate(
                [
                    'admission_batch_id' => $admissionBatchId,
                    'programme_id' => $programme->id,
                    'start_date' => $start->toDateString(),
                ],
                [
                    'end_date' => $end->toDateString(),
                    'status' => true,
                ]
            );
            $count++;
            $cursor->addWeek();
        }

        if ($count === 0) {
            $mid = $windowStart->copy()->addDays(
                (int) floor($windowStart->diffInDays($windowEnd) / 2)
            );
            $start = $mid->copy()->startOfWeek();
            $end = $start->copy()->addDays(6);
            if ($end->gt($windowEnd)) {
                $end = $windowEnd->copy();
                $start = $end->copy()->subDays(6);
            }
            if ($start->lt($windowStart)) {
                $start = $windowStart->copy();
            }

            ProgrammeBatch::firstOrCreate(
                [
                    'admission_batch_id' => $admissionBatchId,
                    'programme_id' => $programme->id,
                    'start_date' => $start->toDateString(),
                ],
                [
                    'end_date' => $end->toDateString(),
                    'status' => true,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function seedCentreSessionsForCourse(Course $course): int
    {
        if (! $course->centre_id || ! $course->programme?->isInPerson()) {
            return 0;
        }

        $n = 0;

        if (Schema::hasColumn('course_sessions', 'centre_sync_key')) {
            $morningKey = Uuid::uuid5(
                Uuid::NAMESPACE_URL,
                'https://omcp.local/seed/centre-session/morning/'.$course->id
            )->toString();

            CourseSession::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'centre_id' => (int) $course->centre_id,
                    'session_type' => CourseSession::TYPE_CENTRE,
                    'centre_sync_key' => $morningKey,
                ],
                [
                    'name' => 'Morning class block',
                    'session' => 'Morning',
                    'course_time' => '09:00 – 12:30',
                    'limit' => 45,
                    'status' => true,
                ]
            );
            $n++;

            $afternoonKey = Uuid::uuid5(
                Uuid::NAMESPACE_URL,
                'https://omcp.local/seed/centre-session/afternoon/'.$course->id
            )->toString();

            CourseSession::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'centre_id' => (int) $course->centre_id,
                    'session_type' => CourseSession::TYPE_CENTRE,
                    'centre_sync_key' => $afternoonKey,
                ],
                [
                    'name' => 'Afternoon lab block',
                    'session' => 'Afternoon',
                    'course_time' => '13:30 – 17:00',
                    'limit' => 40,
                    'status' => true,
                ]
            );
            $n++;
        } else {
            CourseSession::firstOrCreate(
                [
                    'course_id' => $course->id,
                    'centre_id' => (int) $course->centre_id,
                    'session_type' => CourseSession::TYPE_CENTRE,
                ],
                [
                    'name' => 'Centre intake (seed)',
                    'session' => 'Intake',
                    'course_time' => '09:00 – 15:00',
                    'limit' => 50,
                    'status' => true,
                ]
            );
            $n++;
        }

        return $n;
    }
}
