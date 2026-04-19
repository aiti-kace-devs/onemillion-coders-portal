<?php

declare(strict_types=1);

/**
 * One-off audit: row counts and checks for manual / integration testing.
 * Run: php scripts/db-readiness-audit.php (from backend directory)
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function tableExists(string $table): bool
{
    return Schema::hasTable($table);
}

function cnt(string $table): int|string
{
    if (! tableExists($table)) {
        return 'N/A (no table)';
    }

    try {
        return (int) DB::table($table)->count();
    } catch (Throwable $e) {
        return 'ERR: '.$e->getMessage();
    }
}

function line(string $label, mixed $value): void
{
    echo str_pad($label, 42, ' ', STR_PAD_RIGHT).' '.$value.PHP_EOL;
}

echo 'Database: '.DB::connection()->getDatabaseName().PHP_EOL;
echo str_repeat('-', 72).PHP_EOL;

$core = [
    'users' => 'Students (users table)',
    'admins' => 'Admins',
    'programmes' => 'Programmes',
    'courses' => 'Courses (centre offerings)',
    'centres' => 'Centres',
    'branches' => 'Branches',
    'programme_batches' => 'Programme batches (cohorts)',
    'admission_batches' => 'Admission / intake windows',
    'course_sessions' => 'Course sessions (all types)',
    'user_admission' => 'User admissions',
    'bookings' => 'Bookings',
    'oex_categories' => 'Exam categories',
    'oex_exam_masters' => 'Exam masters (users.exam FK)',
    'course_match' => 'Course match (quiz questions)',
    'course_match_options' => 'Course match options',
    'programme_course_match_options' => 'Programme ↔ match option links',
    'ghana_card_verifications' => 'Ghana Card verifications',
    'admission_waitlist' => 'Admission waitlist',
];

foreach ($core as $table => $desc) {
    line($desc, cnt($table));
}

echo str_repeat('-', 72).PHP_EOL;
echo "Targeted checks (in-person enrolment / choose-course style flows)\n";

$today = now()->toDateString();

// Current admission window (matches InPersonAvailabilityController logic)
$activeAdmission = tableExists('admission_batches')
    ? DB::table('admission_batches')
        ->where('start_date', '<=', $today)
        ->where('end_date', '>=', $today)
        ->where('status', true)
        ->where('completed', false)
        ->count()
    : 0;
line('Active admission_batches (today window)', $activeAdmission);

$pb = tableExists('programme_batches')
    ? DB::table('programme_batches')->where('status', true)->count()
    : 0;
line('Active programme_batches', $pb);

$centreSessions = 0;
if (tableExists('course_sessions')) {
    $centreSessions = DB::table('course_sessions')
        ->where('session_type', 'centre')
        ->where('status', true)
        ->count();
}
line('Active centre-type course_sessions', $centreSessions);

$inPersonCourses = 0;
if (tableExists('courses') && tableExists('programmes')) {
    $inPersonCourses = DB::table('courses')
        ->join('programmes', 'programmes.id', '=', 'courses.programme_id')
        ->whereRaw('LOWER(TRIM(programmes.mode_of_delivery)) = ?', ['in person'])
        ->where('courses.status', true)
        ->count();
}
line('Courses under In Person programmes', $inPersonCourses);

$usersReady = 0;
if (tableExists('users') && tableExists('oex_exam_masters')) {
    $usersReady = DB::table('users')
        ->whereNotNull('exam')
        ->whereExists(function ($q) {
            $q->selectRaw('1')->from('oex_exam_masters')->whereColumn('oex_exam_masters.id', 'users.exam');
        })
        ->count();
}
line('Users with valid exam FK', $usersReady);

echo str_repeat('-', 72).PHP_EOL;
echo "Summary (minimums for common manual tests)\n";

$issues = [];
if ((int) cnt('oex_exam_masters') < 1) {
    $issues[] = 'Need at least one oex_exam_masters row (users.exam FK).';
}
if ((int) cnt('oex_categories') < 1) {
    $issues[] = 'Need oex_categories (for exam creation / CategorySeeder).';
}
if ($activeAdmission < 1) {
    $issues[] = 'No active admission_batches covering today — in-person batch API returns empty batches[].';
}
if ($pb < 1) {
    $issues[] = 'No programme_batches — no cohorts to attach sessions to.';
}
if ($centreSessions < 1) {
    $issues[] = 'No active centre course_sessions — in-person availability will list sessions: [] per cohort.';
}
if ((int) cnt('courses') < 1) {
    $issues[] = 'No courses — enrolment / recommendations cannot target a course.';
}
if ((int) cnt('programmes') < 1) {
    $issues[] = 'No programmes.';
}
if ((int) cnt('centres') < 1) {
    $issues[] = 'No centres.';
}
if ((int) cnt('users') < 1) {
    $issues[] = 'No student users.';
}

if ($issues === []) {
    echo "OK: Core tables populated; review centre_sessions vs cohort count for your scenarios.\n";
} else {
    echo "Gaps likely to block or weaken tests:\n";
    foreach ($issues as $i) {
        echo ' - '.$i.PHP_EOL;
    }
}
