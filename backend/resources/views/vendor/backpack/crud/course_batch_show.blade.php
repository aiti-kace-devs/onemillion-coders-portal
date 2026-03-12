@extends('crud::show')

@php
    /** @var \App\Models\Course $entry */
    $course = $entry;
    $courseId = $course->getKey();

    $course->loadMissing([
        'batch',
        'centre.branch',
        'programme',
        'sessions',
        'assignedAdmins',
    ]);

    $batch = $course->batch;
    $centre = $course->centre;
    $branch = $centre?->branch;
    $programme = $course->programme;
    $admins = $course->assignedAdmins ?? collect();
    $sessions = $course->sessions ?? collect();

    $backToCourseUrl = backpack_url('course/');
    $backUrl = $course->batch_id
        ? backpack_url('batch/' . $course->batch_id . '/edit')
        : backpack_url('batch');

    $startRaw = $course->start_date ?: ($batch?->start_date);
    $endRaw = $course->end_date ?: ($batch?->end_date);

    $startDate = null;
    $endDate = null;
    try {
        if (!empty($startRaw)) {
            $startDate = $startRaw instanceof \Carbon\Carbon ? $startRaw : \Carbon\Carbon::parse($startRaw);
        }
    } catch (\Throwable $e) {
        $startDate = null;
    }
    try {
        if (!empty($endRaw)) {
            $endDate = $endRaw instanceof \Carbon\Carbon ? $endRaw : \Carbon\Carbon::parse($endRaw);
        }
    } catch (\Throwable $e) {
        $endDate = null;
    }

    // If dates are reversed in DB, normalize them to avoid filtering out all records.
    if ($startDate && $endDate && $startDate->gt($endDate)) {
        [$startDate, $endDate] = [$endDate, $startDate];
    }

    $totalCourseDays = ($startDate && $endDate) ? ($startDate->diffInDays($endDate) + 1) : 0;
    $dateRangeLabel = ($startDate && $endDate)
        ? $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y')
        : 'Not set';

    $metricsCacheTtl = now()->addMinutes(2);
    $metricsCacheKeyPrefix = 'course_batch_show:' . $courseId . ':';

    $admissionsBase = \App\Models\UserAdmission::query()
        ->where('course_id', $courseId);

    $admissionsAgg = \Illuminate\Support\Facades\Cache::remember(
        $metricsCacheKeyPrefix . 'admissions_agg:v1',
        $metricsCacheTtl,
        function () use ($courseId) {
            return \Illuminate\Support\Facades\DB::table('user_admission')
                ->where('course_id', $courseId)
                ->selectRaw('
                    COUNT(*) as total_count,
                    SUM(CASE WHEN confirmed IS NOT NULL THEN 1 ELSE 0 END) as confirmed_count,
                    COUNT(DISTINCT CASE WHEN confirmed IS NOT NULL THEN user_id END) as admitted_students_count
                ')
                ->first();
        }
    );

    $admissionsTotal = (int) ($admissionsAgg->total_count ?? 0);
    $admissionsConfirmed = (int) ($admissionsAgg->confirmed_count ?? 0);
    $admissionsPending = max(0, $admissionsTotal - $admissionsConfirmed);
    $admittedStudentsCount = (int) ($admissionsAgg->admitted_students_count ?? 0);

    $userAgg = \Illuminate\Support\Facades\Cache::remember(
        $metricsCacheKeyPrefix . 'user_agg:v1',
        $metricsCacheTtl,
        function () use ($courseId) {
            return \Illuminate\Support\Facades\DB::table('users')
                ->where('registered_course', $courseId)
                ->selectRaw('
                    COUNT(*) as total_registered,
                    SUM(CASE WHEN shortlist = 1 OR shortlist = true THEN 1 ELSE 0 END) as total_shortlisted
                ')
                ->first();
        }
    );

    $totalRegisteredUsers = (int) ($userAgg->total_registered ?? 0);
    $totalShortlistedUsers = (int) ($userAgg->total_shortlisted ?? 0);
    $totalAdmittedUsers = $admittedStudentsCount;

    $shortlistRate = $totalRegisteredUsers > 0
        ? round(($totalShortlistedUsers / $totalRegisteredUsers) * 100, 1)
        : 0;
    $admissionRate = $totalRegisteredUsers > 0
        ? round(($totalAdmittedUsers / $totalRegisteredUsers) * 100, 1)
        : 0;

    // Attendance
    $attendanceBase = \App\Models\Attendance::query()
        ->where('course_id', $courseId);

    // Treat missing/unknown status as "present", because attendance recording currently stores a row without status.
    $attendanceAgg = \Illuminate\Support\Facades\Cache::remember(
        $metricsCacheKeyPrefix . 'attendance_agg:v1',
        $metricsCacheTtl,
        function () use ($courseId) {
            return \Illuminate\Support\Facades\DB::table('attendances')
                ->where('course_id', $courseId)
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(DISTINCT user_id) as unique_students,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) IN ('absent','late','excused') THEN 0 ELSE 1 END) as present,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'excused' THEN 1 ELSE 0 END) as excused
                ")
                ->first();
        }
    );

    $attendanceTotal = (int) ($attendanceAgg->total ?? 0);
    $attendanceUniqueStudents = (int) ($attendanceAgg->unique_students ?? 0);

    $presentCount = (int) ($attendanceAgg->present ?? 0);
    $absentCount = (int) ($attendanceAgg->absent ?? 0);
    $lateCount = (int) ($attendanceAgg->late ?? 0);
    $excusedCount = (int) ($attendanceAgg->excused ?? 0);

    $attendanceRate = $attendanceTotal > 0 ? round((($presentCount + $lateCount) / $attendanceTotal) * 100, 1) : 0;

    $attendanceWindowDays = 7;
    $attendanceWindowEndRaw = \Illuminate\Support\Facades\Cache::remember(
        $metricsCacheKeyPrefix . 'attendance_window_end:v1',
        now()->addMinutes(1),
        function () use ($courseId) {
            return \Illuminate\Support\Facades\DB::table('attendances')
                ->where('course_id', $courseId)
                ->orderByDesc('date')
                ->value('date');
        }
    );
    $attendanceWindowEnd = $attendanceWindowEndRaw ? \Carbon\Carbon::parse($attendanceWindowEndRaw) : \Carbon\Carbon::today();
    $attendanceWindowStart = $attendanceWindowEnd->copy()->subDays($attendanceWindowDays - 1);

    $attendanceByDayRaw = \Illuminate\Support\Facades\Cache::remember(
        $metricsCacheKeyPrefix . 'attendance_by_day:v1:' . $attendanceWindowStart->toDateString() . ':' . $attendanceWindowEnd->toDateString(),
        now()->addMinutes(1),
        function () use ($courseId, $attendanceWindowStart, $attendanceWindowEnd) {
            return \Illuminate\Support\Facades\DB::table('attendances')
                ->where('course_id', $courseId)
                ->whereBetween('date', [$attendanceWindowStart->toDateString(), $attendanceWindowEnd->toDateString()])
                ->selectRaw("date as day,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) IN ('absent','late','excused') THEN 0 ELSE 1 END) as present,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'excused' THEN 1 ELSE 0 END) as excused,
                    COUNT(*) as total")
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->keyBy('day');
        }
    );

    $attendanceByDay = collect();
    for ($d = $attendanceWindowStart->copy(); $d->lte($attendanceWindowEnd); $d->addDay()) {
        $key = $d->toDateString();
        $row = $attendanceByDayRaw->get($key);
        $attendanceByDay->push((object) [
            'day' => $key,
            'present' => (int) ($row->present ?? 0),
            'absent' => (int) ($row->absent ?? 0),
            'late' => (int) ($row->late ?? 0),
            'excused' => (int) ($row->excused ?? 0),
            'total' => (int) ($row->total ?? 0),
        ]);
    }

    $attendanceWindowPresent = (int) $attendanceByDay->sum('present');
    $attendanceWindowAbsent = (int) $attendanceByDay->sum('absent');
    $attendanceWindowLate = (int) $attendanceByDay->sum('late');
    $attendanceWindowExcused = (int) $attendanceByDay->sum('excused');

    $attendanceChartLabels = $attendanceByDay->pluck('day')->map(function ($d) {
        try {
            return \Carbon\Carbon::parse($d)->format('M d');
        } catch (\Throwable $e) {
            return (string) $d;
        }
    })->values();
    $attendanceChartLabelsFull = $attendanceByDay->pluck('day')->map(fn($d) => (string) $d)->values();

    // Exams (oex_results uses users.id; admissions uses users.userId)
    $examAgg = \Illuminate\Support\Facades\Cache::remember(
        $metricsCacheKeyPrefix . 'exam_agg:v1',
        $metricsCacheTtl,
        function () use ($courseId) {
            return \Illuminate\Support\Facades\DB::table('oex_results as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->join('user_admission as ua', 'ua.user_id', '=', 'u.userId')
                ->where('ua.course_id', $courseId)
                ->whereNotNull('ua.confirmed')
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(DISTINCT r.user_id) as students,
                    AVG(CASE WHEN (r.yes_ans+r.no_ans) > 0 THEN (r.yes_ans/(r.yes_ans+r.no_ans))*100 ELSE NULL END) as avg_score,
                    SUM(CASE WHEN (r.yes_ans+r.no_ans) > 0 AND (r.yes_ans/(r.yes_ans+r.no_ans))*100 >= 50 THEN 1 ELSE 0 END) as passed,
                    SUM(CASE WHEN (r.yes_ans+r.no_ans) > 0 AND (r.yes_ans/(r.yes_ans+r.no_ans))*100 < 50 THEN 1 ELSE 0 END) as failed
                ')
                ->first();
        }
    );

    $examTotal = (int) ($examAgg->total ?? 0);
    $examStudents = (int) ($examAgg->students ?? 0);
    $avgScore = $examAgg?->avg_score !== null ? round((float) $examAgg->avg_score, 1) : 0;

    $passedCount = (int) ($examAgg->passed ?? 0);
    $failedCount = (int) ($examAgg->failed ?? 0);
    $passRate = $examTotal > 0 ? round(($passedCount / $examTotal) * 100, 1) : 0;

    $examTrend = \Illuminate\Support\Facades\Cache::remember(
        $metricsCacheKeyPrefix . 'exam_trend:v1',
        $metricsCacheTtl,
        function () use ($courseId) {
            return \Illuminate\Support\Facades\DB::table('oex_results as r')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->join('user_admission as ua', 'ua.user_id', '=', 'u.userId')
                ->where('ua.course_id', $courseId)
                ->whereNotNull('ua.confirmed')
                ->whereNotNull('r.created_at')
                ->selectRaw("DATE(r.created_at) as day,
                    AVG(CASE WHEN (r.yes_ans+r.no_ans) > 0 THEN (r.yes_ans/(r.yes_ans+r.no_ans))*100 ELSE NULL END) as avg_score")
                ->groupBy('day')
                ->orderByDesc('day')
                ->limit(30)
                ->get()
                ->reverse()
                ->values();
        }
    );
@endphp

@section('content')
    @parent

    <div class="mb-3 d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-start gap-2">
            <a href="{{ backpack_url('batch/' . $batch->id . '/edit') }}" class="btn btn-sm btn-outline-secondary" title="Back to Batch Edit">
                <i class="la la-arrow-left"></i>
            </a>
            <div>
                <h4 class="mb-0">Course Batch Metrics</h4>
                <div class="text-muted">
                    {{ $course->course_name ?? 'Course' }}
                    @if($centre?->title)
                        • {{ $centre->title }}
                    @endif
                    @if($batch?->title)
                        • {{ $batch->title }}
                    @endif
                </div>
                <div class="text-muted small">
                    <i class="la la-calendar"></i> {{ $dateRangeLabel }}
                    @if($course->duration)
                        • <i class="la la-clock"></i> {{ $course->duration }}
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Status</span>
                @include('admin.status_toggle.status_column', [
                    'entry' => $course,
                    'crud' => $crud ?? null,
                    'column' => [
                        'name' => 'status',
                        'toggle_url' => 'course-batch/{id}/toggle',
                        'toggle_success_message' => 'Course status updated successfully.',
                        'toggle_error_message' => 'Error updating course status.',
                    ],
                ])
            </div>

            <!-- <a href="{{ backpack_url('course/' . $courseId . '/edit') }}" class="btn btn-sm btn-primary">
                <i class="la la-edit"></i> Edit Course
            </a> -->
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Total Registered Users</div>
                        <i class="la la-users text-primary"></i>
                    </div>
                    <div class="metric-value">{{ number_format($totalRegisteredUsers) }}</div>
                    <div class="text-muted small">Users registered for this course.</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Total Shortlisted Users</div>
                        <i class="la la-user-check text-info"></i>
                    </div>
                    <div class="metric-value">{{ number_format($totalShortlistedUsers) }}</div>
                    <div class="text-muted small">Shortlist rate: {{ $shortlistRate }}%</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Total Admitted Users</div>
                        <i class="la la-graduation-cap text-success"></i>
                    </div>
                    <div class="metric-value">{{ number_format($totalAdmittedUsers) }}</div>
                    <div class="text-muted small">Admission rate: {{ $admissionRate }}%</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Total Attendance Students</div>
                        <i class="la la-user-friends text-warning"></i>
                    </div>
                    <div class="metric-value">{{ number_format($attendanceUniqueStudents) }}</div>
                    <div class="text-muted small">Attendance records: {{ number_format($attendanceTotal) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Admissions</div>
                        <i class="la la-users text-primary"></i>
                    </div>
                    <div class="metric-value">{{ number_format($admissionsTotal) }}</div>
                    <div class="text-muted small">
                        Confirmed: {{ number_format($admissionsConfirmed) }} • Pending: {{ number_format($admissionsPending) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Attendance Records</div>
                        <i class="la la-calendar-check text-success"></i>
                    </div>
                    <div class="metric-value">{{ number_format($attendanceTotal) }}</div>
                    <div class="text-muted small">
                        Rate: {{ $attendanceRate }}% • Students: {{ number_format($attendanceUniqueStudents) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Exam Attempts</div>
                        <i class="la la-poll text-warning"></i>
                    </div>
                    <div class="metric-value">{{ number_format($examTotal) }}</div>
                    <div class="text-muted small">
                        <!-- Avg: {{ $avgScore }}% • Pass Rate: {{ $passRate }}% -->
                        Students: {{ number_format($examStudents) }} • Pass: {{ number_format($passedCount) }} • Fail: {{ number_format($failedCount) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Sessions & Instructors</div>
                        <i class="la la-user text-info"></i>
                    </div>
                    <div class="metric-value">{{ number_format($sessions->count()) }}</div>
                    <div class="text-muted small">
                        Instructors assigned: {{ number_format($admins->count()) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Information --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-info-circle"></i> Course Batch Details</strong>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Course</td>
                            <td>{{ $course->course_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Programme</td>
                            <td>
                                @if($programme?->id)
                                    <a href="{{ backpack_url('programme/' . $programme->id . '/show') }}">{{ $programme->title }}</a>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Training Centre</td>
                            <td>
                                @if($centre?->id)
                                    <a href="{{ backpack_url('centre/' . $centre->id . '/show') }}">{{ $centre->title }}</a>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Branch</td>
                            <td>{{ $branch?->title ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Batch</td>
                            <td>
                                @if($batch?->id)
                                    <a href="{{ backpack_url('batch/' . $batch->id . '/edit') }}">{{ $batch->title }}</a>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Duration</td>
                            <td>{{ $course->duration ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Start Date</td>
                            <td>{{ $startDate ? $startDate->format('Y-m-d') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">End Date</td>
                            <td>{{ $endDate ? $endDate->format('Y-m-d') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Days</td>
                            <td>{{ $totalCourseDays ? number_format($totalCourseDays) : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Location</td>
                            <td>{{ $course->location ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-chart-bar"></i> Attendance (Last 7 days)</strong>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="attendanceStackedChart"></canvas>
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <span class="badge bg-success text-dark">Present: {{ number_format($attendanceWindowPresent) }}</span>
                        <span class="badge bg-warning text-dark">Absent: {{ number_format($attendanceWindowAbsent) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-pie-chart"></i> Admissions Distribution</strong>
                </div>
                <div class="card-body">
                    <div class="chart-wrap-sm">
                        <canvas id="admissionsDoughnut"></canvas>
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <span class="badge bg-info text-dark">Total: {{ number_format($admissionsTotal) }}</span>
                        <span class="badge bg-success text-dark">Confirmed: {{ number_format($admissionsConfirmed) }}</span>
                        <span class="badge bg-warning text-dark">Pending: {{ number_format($admissionsPending) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-pie-chart"></i> Attendance Status</strong>
                </div>
                <div class="card-body">
                    <div class="chart-wrap-sm">
                        <canvas id="attendanceStatusDoughnut"></canvas>
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <span class="badge bg-success text-dark">Present: {{ number_format($presentCount) }}</span>
                        <span class="badge bg-info text-dark">Absent: {{ number_format($absentCount) }}</span>
                    </div>
                </div>
            </div>
        </div> -->

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-calendar"></i> Course Sessions</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtSessions" class="table table-striped table-hover table-sm w-100">
                            <thead>
                                <tr>
                                    <th>Session</th>
                                    <th>Limit</th>
                                    <th>Course Time</th>
                                    <th>Slots Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $confirmedBySession = (clone $admissionsBase)
                                        ->whereNotNull('confirmed')
                                        ->select('session', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                                        ->groupBy('session')
                                        ->pluck('count', 'session');
                                @endphp

                                @foreach($sessions as $session)
                                    @php
                                        $confirmedCount = (int) ($confirmedBySession[$session->id] ?? 0);
                                        $limit = (int) ($session->limit ?? 0);
                                        $slotsLeft = $limit > 0 ? max(0, $limit - $confirmedCount) : null;
                                    @endphp
                                    <tr>
                                        <td>{{ $session->name ?? ('Session #' . $session->id) }}</td>
                                        <td>{{ $limit ?: '-' }}</td>
                                        <td>{{ $session->course_time ?? ('Session #' . $session->id) }}</td>
                                        <td>
                                            @if($slotsLeft === null)
                                                -
                                            @else
                                                {{ number_format($slotsLeft) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($sessions->isEmpty())
                        <div class="text-center text-muted py-3">No sessions configured for this course.</div>
                    @endif
                </div>
            </div>
        </div>



    </div>

    {{-- Tables --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <strong><i class="la la-users"></i> Admitted Students</strong>
                    <span class="text-muted small">Total: {{ number_format($admissionsConfirmed) }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtAdmittedStudents" class="table table-striped table-hover table-sm w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Email</th>
                                    <th>Session</th>
                                    <th>Admission</th>
                                    <th>Exam</th>
                                    <th>Score</th>
                                    <th>Result</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    @if($admissionsConfirmed === 0)
                        <div class="text-center text-muted py-3">No admitted students found for this course batch.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>



    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <strong><i class="la la-list"></i> Attendance History</strong>
                    <span class="text-muted small">Total: {{ number_format($attendanceTotal) }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtAttendanceHistory" class="table table-striped table-hover table-sm w-100">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Course</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    @if($attendanceTotal === 0)
                        <div class="text-center text-muted py-3">No attendance records found for this course batch.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-user"></i> Assigned Instuctors</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtAdmins" class="table table-striped table-hover table-sm w-100">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Roles</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($admins as $admin)
                                    <tr>
                                        <td>{{ $admin->name ?? 'N/A' }}</td>
                                        <td>{{ $admin->email ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $roles = method_exists($admin, 'getRoleNames') ? $admin->getRoleNames()->implode(', ') : '';
                                            @endphp
                                            {{ $roles ?: '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($admins->isEmpty())
                        <div class="text-center text-muted py-3">No admins assigned to this course batch.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="examResultModal" tabindex="-1" aria-labelledby="examResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center">
                    <h5 class="modal-title" id="examResultModalLabel">Exam Result</h5>
                    <button type="button" class="close ms-auto ml-auto" style="margin-left:auto" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="examResultFrame" src="about:blank" style="width:100%;height:75vh;border:0;" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <style>
        .metric-card .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.1;
            margin-top: 0.35rem;
        }
        .chart-wrap {
            position: relative;
            height: 320px;
        }
        .chart-wrap-sm {
            position: relative;
            height: 220px;
        }
        .dataTables_wrapper .dataTables_filter input {
            margin-left: .5rem;
        }
        .dataTables_wrapper .dataTables_length select {
            margin: 0 .25rem;
        }
    </style>
@endpush

@push('after_scripts')
    <script src="{{ asset('assets/plugins/chart.js/Chart.min.js') }}"></script>

    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        (function () {
            "use strict";

            function showModal(modalId) {
                const el = document.getElementById(modalId);
                if (!el) return;

                if (el.parentElement !== document.body) {
                    document.body.appendChild(el);
                }

                if (window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(el, { backdrop: true, keyboard: true }).show();
                    return;
                }

                const jq = window.jQuery;
                if (jq && jq.fn && jq.fn.modal) {
                    jq(el).modal('show');
                }
            }

            function openExamResultModal(url) {
                if (!url) return;
                const frame = document.getElementById('examResultFrame');
                if (frame) frame.setAttribute('src', url);
                showModal('examResultModal');
            }

            function safeInitDataTable(selector, options) {
                const $ = window.jQuery;
                if (!$ || !$.fn || !$.fn.DataTable) return;

                const $el = $(selector);
                if (!$el.length) return;
                if ($.fn.DataTable.isDataTable($el[0])) return;

                $el.DataTable(Object.assign({
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    responsive: true,
                    deferRender: true,
                    language: { search: "", searchPlaceholder: "Search..." },
                }, options || {}));
            }

            document.addEventListener('DOMContentLoaded', function () {
                // Open exam result in modal (works for server-side DataTable rows).
                document.addEventListener('click', function (e) {
                    const trigger = e.target.closest('.js-view-result-modal');
                    if (!trigger) return;
                    e.preventDefault();
                    const url = trigger.getAttribute('data-url') || trigger.getAttribute('href');
                    openExamResultModal(url);
                });

                const examModal = document.getElementById('examResultModal');
                if (examModal) {
                    const resetFrame = function () {
                        const frame = document.getElementById('examResultFrame');
                        if (frame) frame.setAttribute('src', 'about:blank');
                    };

                    try {
                        examModal.addEventListener('hidden.bs.modal', resetFrame);
                    } catch (e) {
                        // ignore
                    }

                    const jq = window.jQuery;
                    if (jq && jq.fn && jq.fn.modal) {
                        jq(examModal).on('hidden.bs.modal', resetFrame);
                    }
                }

                safeInitDataTable('#dtAdmittedStudents', {
                    processing: true,
                    serverSide: true,
                    ordering: false,
                    ajax: {
                        url: @json(backpack_url('course-batch/' . $courseId . '/admitted-students-data')),
                        type: 'GET',
                    },
                    columns: [
                        { data: 'index' },
                        { data: 'student' },
                        { data: 'email' },
                        { data: 'session' },
                        { data: 'admission' },
                        { data: 'exam' },
                        { data: 'score' },
                        { data: 'result' },
                        { data: 'actions' },
                    ],
                });

                safeInitDataTable('#dtAttendanceHistory', {
                    processing: true,
                    serverSide: true,
                    ordering: false,
                    ajax: {
                        url: @json(backpack_url('course-batch/' . $courseId . '/attendance-history-data')),
                        type: 'GET',
                    },
                    columns: [
                        { data: 'date' },
                        { data: 'student' },
                        { data: 'course' },
                    ],
                });

                safeInitDataTable('#dtSessions', { paging: false, searching: true, info: false });
                safeInitDataTable('#dtAdmins', { paging: false, searching: true, info: false });

                if (typeof Chart !== 'function') return;

                // Attendance stacked bar chart
                const attendanceLabels = @json($attendanceChartLabels);
                const attendanceLabelsFull = @json($attendanceChartLabelsFull);
                const attendancePresent = @json($attendanceByDay->pluck('present')->map(fn($v) => (int) $v)->values());
                const attendanceAbsent = @json($attendanceByDay->pluck('absent')->map(fn($v) => (int) $v)->values());
                const attendanceLate = @json($attendanceByDay->pluck('late')->map(fn($v) => (int) $v)->values());
                const attendanceExcused = @json($attendanceByDay->pluck('excused')->map(fn($v) => (int) $v)->values());

                const stackedCtx = document.getElementById('attendanceStackedChart');
                if (stackedCtx && attendanceLabels.length) {
                    new Chart(stackedCtx, {
                        type: 'bar',
                        data: {
                            labels: attendanceLabels,
                            datasets: [
                                { label: 'Present', data: attendancePresent, backgroundColor: 'rgba(25, 135, 84, 0.8)' },
                                { label: 'Absent', data: attendanceAbsent, backgroundColor: 'rgba(255, 193, 7, 0.85)' },
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                xAxes: [{ stacked: true, ticks: { autoSkip: false, maxRotation: 0, minRotation: 0 } }],
                                yAxes: [{ stacked: true, ticks: { beginAtZero: true } }]
                            },
                            legend: { position: 'bottom' },
                            tooltips: {
                                callbacks: {
                                    title: function (items) {
                                        const idx = items && items.length ? items[0].index : null;
                                        return idx !== null && attendanceLabelsFull[idx] ? attendanceLabelsFull[idx] : '';
                                    }
                                }
                            }
                        }
                    });
                }

                // Admissions doughnut
                const admissionsCtx = document.getElementById('admissionsDoughnut');
                if (admissionsCtx && ({{ $admissionsTotal }} > 0)) {
                    const centerTextPlugin = {
                        beforeDraw: function (chart) {
                            const opts = chart?.config?.options?.centerText;
                            if (!opts) return;

                            const ctx = chart.chart.ctx;
                            const width = chart.chart.width;
                            const height = chart.chart.height;

                            const line1 = opts.line1 || '';
                            const line2 = opts.line2 || '';

                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';

                            ctx.fillStyle = opts.color || '#111';
                            ctx.font = `600 ${opts.valueFontSize || 20}px ${opts.fontFamily || 'system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif'}`;
                            ctx.fillText(line2, width / 2, height / 2 + (opts.valueOffsetY || 6));

                            ctx.fillStyle = opts.labelColor || '#6c757d';
                            ctx.font = `500 ${opts.labelFontSize || 12}px ${opts.fontFamily || 'system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif'}`;
                            ctx.fillText(line1, width / 2, height / 2 - (opts.labelOffsetY || 12));

                            ctx.restore();
                        }
                    };

                    new Chart(admissionsCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Confirmed', 'Pending'],
                            datasets: [{
                                data: [{{ $admissionsConfirmed }}, {{ $admissionsPending }}],
                                backgroundColor: ['rgba(25, 135, 84, 0.85)', 'rgba(255, 193, 7, 0.85)'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { position: 'bottom' },
                            centerText: {
                                line1: 'Total',
                                line2: ({{ $admissionsTotal }}).toLocaleString(),
                            }
                        },
                        plugins: [centerTextPlugin]
                    });
                }

                // Attendance status doughnut
                const attendanceStatusCtx = document.getElementById('attendanceStatusDoughnut');
                if (attendanceStatusCtx && ({{ $attendanceTotal }} > 0)) {
                    new Chart(attendanceStatusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Present', 'Absent', 'Late', 'Excused'],
                            datasets: [{
                                data: [{{ $presentCount }}, {{ $absentCount }}, {{ $lateCount }}, {{ $excusedCount }}],
                                backgroundColor: [
                                    'rgba(25, 135, 84, 0.85)',
                                    'rgba(220, 53, 69, 0.85)',
                                    'rgba(255, 193, 7, 0.85)',
                                    'rgba(13, 202, 240, 0.85)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: { position: 'bottom' }
                        }
                    });
                }

                // Exam trend line chart
                const examTrendLabels = @json($examTrend->pluck('day')->map(fn($d) => (string) $d)->values());
                const examTrendScores = @json($examTrend->pluck('avg_score')->map(fn($v) => $v === null ? null : round((float) $v, 1))->values());

                const examCtx = document.getElementById('examTrendChart');
                if (examCtx && examTrendLabels.length) {
                    new Chart(examCtx, {
                        type: 'line',
                        data: {
                            labels: examTrendLabels,
                            datasets: [{
                                label: 'Avg Score (%)',
                                data: examTrendScores,
                                borderColor: 'rgba(13, 110, 253, 1)',
                                backgroundColor: 'rgba(13, 110, 253, 0.15)',
                                fill: true,
                                lineTension: 0.25,
                                pointRadius: 3,
                                pointHoverRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        max: 100,
                                        callback: function (value) { return value + '%'; }
                                    }
                                }]
                            },
                            legend: { position: 'bottom' }
                        }
                    });
                }
            });
        })();
    </script>
@endpush
