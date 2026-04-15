<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Booking;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use App\Models\Oex_exam_master;
use App\Models\Oex_category;
use App\Models\ProgrammeBatch;
use App\Models\User;
use App\Models\UserAdmission;
use App\Models\UserAssessment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RealisticStudentLifecycleSeeder extends Seeder
{
    private const DEFAULT_ASSESSED_NO_BOOKING = 24;
    private const DEFAULT_NO_ASSESSMENT_NO_BOOKING = 24;
    private const DEFAULT_CONTROL_WITH_BOOKING = 8;
    private const DEFAULT_PASSWORD_HASH = '$2y$10$h5CBf1pj5TRKyF6xi.wgzOzmH.i/Kx7R7TCtDR3GLPiSpne3eRhFy';
    private const EMAIL_DOMAIN = 'omc.gh';
    private const GHANAIAN_FIRST_NAMES = [
        'Kwame', 'Kofi', 'Kojo', 'Yaw', 'Kwaku', 'Kwabena', 'Ekow', 'Kweku', 'Nana', 'Nii',
        'Ama', 'Akosua', 'Abena', 'Adwoa', 'Yaa', 'Esi', 'Efua', 'Afia', 'Araba', 'Mansa',
        'Fiifi', 'Sena', 'Akua', 'Afi', 'Selorm', 'Nhyira', 'Mawuli', 'Elsie', 'Naa', 'Adjoa',
    ];
    private const GHANAIAN_LAST_NAMES = [
        'Mensah', 'Asare', 'Boateng', 'Owusu', 'Ofori', 'Ankomah', 'Nyarko', 'Agyeman', 'Boadu', 'Tetteh',
        'Appiah', 'Acheampong', 'Addo', 'Aidoo', 'Frimpong', 'Antwi', 'Amponsah', 'Sarpong', 'Yeboah', 'Ansah',
        'Osei', 'Darko', 'Opoku', 'Quaye', 'Lamptey', 'Adomako', 'Sackey', 'Tawiah', 'Koomson', 'Baffoe',
    ];

    private ?int $defaultExamId = null;

    public function run(): void
    {
        $assessedNoBookingCount = (int) env('SEED_COHORT_ASSESSED_NO_BOOKING', self::DEFAULT_ASSESSED_NO_BOOKING);
        $noAssessmentNoBookingCount = (int) env('SEED_COHORT_NO_ASSESSMENT_NO_BOOKING', self::DEFAULT_NO_ASSESSMENT_NO_BOOKING);
        $controlWithBookingCount = (int) env('SEED_COHORT_CONTROL_WITH_BOOKING', self::DEFAULT_CONTROL_WITH_BOOKING);

        $courses = Course::query()
            ->with(['programme', 'centre', 'batch', 'sessions'])
            ->get()
            ->filter(fn (Course $course) => $course->programme_id && $course->centre_id)
            ->values();

        if ($courses->isEmpty()) {
            $this->command?->warn('RealisticStudentLifecycleSeeder skipped: no eligible courses found.');
            return;
        }

        $this->defaultExamId = $this->resolveDefaultExamId();

        $this->seedAssessedNoBooking($courses, $assessedNoBookingCount);
        $this->seedNoAssessmentNoBooking($courses, $noAssessmentNoBookingCount);
        $this->seedControlWithBooking($courses, $controlWithBookingCount);
        $this->normalizeExistingLifecycleUsers();

        $this->command?->info('RealisticStudentLifecycleSeeder completed successfully.');
    }

    private function seedAssessedNoBooking($courses, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $course = $courses->random();
            $programmeBatch = $this->resolveProgrammeBatch($course);
            $session = $this->resolveCourseSession($course);

            $user = $this->createLifecycleUser($i, 'A', $course, true);
            $admission = $this->upsertAdmission(
                $user,
                $course,
                $programmeBatch?->id,
                $session?->id,
                true
            );

            UserAssessment::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'current_level' => 'advanced',
                    'questions_answered' => 15,
                    'correct_answers' => 12,
                    'wrong_answers' => 3,
                    'answered_question_ids' => range(1, 15),
                    'violation_count' => random_int(0, 1),
                    'level_started_at' => Carbon::now()->subDays(random_int(10, 35)),
                    'completed' => true,
                ]
            );

            // Enforce the cohort rule: complete assessment, but no booking.
            Booking::query()->where('user_id', $user->userId)->delete();

            $this->command?->line("Cohort A seeded for {$user->userId} (admission #{$admission->id}).");
        }
    }

    private function seedNoAssessmentNoBooking($courses, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $course = $courses->random();
            $programmeBatch = $this->resolveProgrammeBatch($course);

            $user = $this->createLifecycleUser($i, 'B', $course, false);

            $this->upsertAdmission(
                $user,
                $course,
                $programmeBatch?->id,
                null,
                false
            );

            // Enforce the cohort rule: no assessment, no booking.
            UserAssessment::query()->where('user_id', $user->id)->delete();
            Booking::query()->where('user_id', $user->userId)->delete();

            $this->command?->line("Cohort B seeded for {$user->userId}.");
        }
    }

    private function seedControlWithBooking($courses, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $course = $courses->random();
            $programmeBatch = $this->resolveProgrammeBatch($course);
            $session = $this->resolveCourseSession($course);
            $masterSession = $this->resolveMasterSession($session);

            if (! $programmeBatch || ! $masterSession) {
                $this->command?->warn("Control cohort skipped for index {$i}: missing programme batch or master session.");
                continue;
            }

            $user = $this->createLifecycleUser($i, 'C', $course, true);
            $admission = $this->upsertAdmission(
                $user,
                $course,
                $programmeBatch->id,
                $session?->id,
                true
            );

            UserAssessment::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'current_level' => 'advanced',
                    'questions_answered' => 15,
                    'correct_answers' => 11,
                    'wrong_answers' => 4,
                    'answered_question_ids' => range(1, 15),
                    'violation_count' => 0,
                    'level_started_at' => Carbon::now()->subDays(random_int(12, 40)),
                    'completed' => true,
                ]
            );

            Booking::updateOrCreate(
                [
                    'user_id' => $user->userId,
                    'programme_batch_id' => $programmeBatch->id,
                ],
                [
                    'course_session_id' => $session?->id,
                    'master_session_id' => $masterSession->id,
                    'centre_id' => $course->centre_id,
                    'course_id' => $course->id,
                    'course_type' => $course->programme?->courseType() ?? 'short',
                    'status' => true,
                    'booked_at' => Carbon::now()->subDays(random_int(1, 8)),
                    'cancelled_at' => null,
                    'user_admission_id' => $admission->id,
                ]
            );

            $this->command?->line("Control cohort seeded with booking for {$user->userId}.");
        }
    }

    private function createLifecycleUser(int $index, string $cohortCode, Course $course, bool $assessed): User
    {
        $suffix = strtoupper(Str::random(5));
        $userId = sprintf('OMC%s%s', $cohortCode, now()->format('ymdHis')) . $suffix;
        $firstName = self::GHANAIAN_FIRST_NAMES[array_rand(self::GHANAIAN_FIRST_NAMES)];
        $lastName = self::GHANAIAN_LAST_NAMES[array_rand(self::GHANAIAN_LAST_NAMES)];
        $email = $this->generateShortUniqueEmail($firstName, $lastName, $cohortCode, $index);

        return User::create([
            'name' => "{$firstName} {$lastName}",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'mobile_no' => '23324' . str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'password' => self::DEFAULT_PASSWORD_HASH,
            'userId' => $userId,
            'exam' => $this->defaultExamId,
            'registered_course' => $course->id,
            'shortlist' => true,
            'student_level' => $assessed ? 'advanced' : null,
            'support' => (bool) random_int(0, 1),
            'status' => true,
            'student_id' => sprintf('SID-%s-%04d', now()->format('ymd'), random_int(1, 9999)),
        ]);
    }

    private function upsertAdmission(
        User $user,
        Course $course,
        ?int $programmeBatchId,
        ?int $sessionId,
        bool $confirmed
    ): UserAdmission {
        $payload = [
            'course_id' => $course->id,
            'programme_batch_id' => $programmeBatchId,
            'session' => $sessionId,
            'confirmed' => $confirmed ? Carbon::now()->subDays(random_int(1, 20)) : null,
            'email_sent' => $confirmed ? Carbon::now()->subDays(random_int(1, 20)) : null,
        ];

        // Keep compatibility with environments that still have legacy batch_id.
        if (Schema::hasColumn('user_admission', 'batch_id')) {
            $payload['batch_id'] = $course->batch_id;
        }

        return UserAdmission::updateOrCreate(
            ['user_id' => $user->userId],
            $payload
        );
    }

    private function resolveProgrammeBatch(Course $course): ?ProgrammeBatch
    {
        $query = ProgrammeBatch::query()->where('programme_id', $course->programme_id);
        if ($course->batch_id) {
            $query->where('admission_batch_id', $course->batch_id);
        }

        $programmeBatch = $query->orderByDesc('id')->first();
        if ($programmeBatch) {
            return $programmeBatch;
        }

        if (! $course->batch_id || ! $course->programme_id) {
            return ProgrammeBatch::query()->where('programme_id', $course->programme_id)->latest('id')->first();
        }

        $batch = Batch::find($course->batch_id);
        if (! $batch) {
            return null;
        }

        $startDate = $batch->start_date ?: Carbon::today()->toDateString();
        $endDate = $batch->end_date ?: Carbon::today()->addWeeks(8)->toDateString();

        return ProgrammeBatch::firstOrCreate(
            [
                'admission_batch_id' => $course->batch_id,
                'programme_id' => $course->programme_id,
                'start_date' => $startDate,
            ],
            [
                'end_date' => $endDate,
                'status' => true,
            ]
        );
    }

    private function resolveCourseSession(Course $course): ?CourseSession
    {
        return CourseSession::query()
            ->where('course_id', $course->id)
            ->where('status', true)
            ->orderBy('id')
            ->first();
    }

    private function resolveMasterSession(?CourseSession $courseSession): ?MasterSession
    {
        if ($courseSession?->master_session_id) {
            $master = MasterSession::find($courseSession->master_session_id);
            if ($master) {
                return $master;
            }
        }

        return MasterSession::query()->where('status', true)->orderBy('id')->first();
    }

    private function resolveDefaultExamId(): int
    {
        $existingExamId = Oex_exam_master::query()->orderBy('id')->value('id');
        if ($existingExamId) {
            return (int) $existingExamId;
        }

        $categoryId = Oex_category::query()->orderBy('id')->value('id');
        if (! $categoryId) {
            $category = Oex_category::create([
                'name' => 'General Assessment',
                'status' => 1,
            ]);
            $categoryId = (int) $category->id;
        }

        $exam = Oex_exam_master::create([
            'title' => 'Seed Baseline Exam',
            'category' => $categoryId,
            'passmark' => 60,
            'exam_date' => Carbon::now()->addMonths(6),
            'exam_duration' => 60,
            'number_of_questions' => 20,
            'status' => 1,
        ]);

        return (int) $exam->id;
    }

    private function normalizeExistingLifecycleUsers(): void
    {
        $existingUsers = User::query()
            ->where('userId', 'like', 'OMC%')
            ->get();

        foreach ($existingUsers as $position => $user) {
            $firstName = self::GHANAIAN_FIRST_NAMES[array_rand(self::GHANAIAN_FIRST_NAMES)];
            $lastName = self::GHANAIAN_LAST_NAMES[array_rand(self::GHANAIAN_LAST_NAMES)];
            $cohortCode = Str::of((string) $user->userId)->substr(3, 1)->upper()->value() ?: 'X';
            $email = $this->generateShortUniqueEmail($firstName, $lastName, $cohortCode, $position + 1, $user->id);

            $user->forceFill([
                'name' => "{$firstName} {$lastName}",
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => self::DEFAULT_PASSWORD_HASH,
            ])->save();
        }
    }

    private function generateShortUniqueEmail(
        string $firstName,
        string $lastName,
        string $cohortCode,
        int $index,
        ?int $excludeUserId = null
    ): string {
        $first = strtolower(Str::substr($firstName, 0, 2));
        $last = strtolower(Str::substr($lastName, 0, 3));
        $cohort = strtolower(Str::substr($cohortCode, 0, 1));

        for ($attempt = 1; $attempt <= 100; $attempt++) {
            $token = strtolower(base_convert((string) random_int(46656, 1679615), 10, 36)); // 3-4 chars
            $localPart = "{$first}{$last}{$cohort}{$index}{$token}";
            $email = "{$localPart}@" . self::EMAIL_DOMAIN;

            $exists = User::query()
                ->where('email', $email)
                ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
                ->exists();

            if (! $exists) {
                return $email;
            }
        }

        return strtolower(Str::uuid()) . '@' . self::EMAIL_DOMAIN;
    }
}
