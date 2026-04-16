@extends('crud::show')

@php
    $userId = $entry->getKey();
    $hasAdmission = $entry->admissions()->whereNotNull('session')->exists();
    $hasPendingAdmission = $entry->admissions()->whereNull('session')->exists();
    $hasAnyAdmission = $hasAdmission || $hasPendingAdmission;
    $hasExamResults = $entry->examResults && $entry->examResults->count() > 0;
    $latestResult = $hasExamResults ? $entry->examResults()->latest()->first() : null;
    $examId = $latestResult ? $latestResult->exam_id : null;

    $currentAdmission = $entry->admissions()->latest()->first();
    $currentCourseId = $currentAdmission?->course_id;
    $currentSessionId = $currentAdmission?->session;

    $course = null;
    $courseStartDate = null;
    $courseEndDate = null;
    $courseId = $currentAdmission?->course_id;

    if ($courseId) {
        $course = \App\Models\Course::with(['batch'])->find($courseId);

        $courseStartDate = $course?->start_date;
        $courseEndDate = $course?->end_date;

        if (empty($courseStartDate) && $course?->batch?->start_date) {
            $courseStartDate = $course->batch->start_date;
        }
        if (empty($courseEndDate) && $course?->batch?->end_date) {
            $courseEndDate = $course->batch->end_date;
        }
    }

    $attendanceRecords = \collect();
    $totalCourseDays = 0;
    $presentCount = 0;
    $absentCount = 0;
    $totalSessions = 0;
    $lateCount = 0;
    $excusedCount = 0;

    $startDateValid = !empty($courseStartDate) && $courseStartDate !== 'NULL';
    $endDateValid = !empty($courseEndDate) && $courseEndDate !== 'NULL';

    if ($startDateValid && $endDateValid) {
        $startDateCarbon = null;
        $endDateCarbon = null;

        try {
            $startDateCarbon =
                $courseStartDate instanceof \Carbon\Carbon ? $courseStartDate : \Carbon\Carbon::parse($courseStartDate);
        } catch (\Throwable $e) {
            $startDateCarbon = null;
        }

        try {
            $endDateCarbon =
                $courseEndDate instanceof \Carbon\Carbon ? $courseEndDate : \Carbon\Carbon::parse($courseEndDate);
        } catch (\Throwable $e) {
            $endDateCarbon = null;
        }

        if ($startDateCarbon && $endDateCarbon && $startDateCarbon->gt($endDateCarbon)) {
            [$startDateCarbon, $endDateCarbon] = [$endDateCarbon, $startDateCarbon];
        }

        if ($startDateCarbon && $endDateCarbon) {
            $totalCourseDays = $startDateCarbon->diffInDays($endDateCarbon) + 1;

            $startDateStr = $startDateCarbon->format('Y-m-d');
            $endDateStr = $endDateCarbon->format('Y-m-d');

            $attendanceQuery = $entry
                ->attendances()
                ->whereRaw('DATE(date) >= ?', [$startDateStr])
                ->whereRaw('DATE(date) <= ?', [$endDateStr])
                ->with('course');

            if (!empty($courseId)) {
                $attendanceQuery->where('course_id', $courseId);
            }

            $attendanceRecords = $attendanceQuery->get();

            $attendanceDates = $attendanceRecords
                ->pluck('date')
                ->map(function ($item) {
                    if ($item instanceof \Carbon\Carbon) {
                        return $item->format('Y-m-d');
                    }
                    if (is_string($item)) {
                        return substr($item, 0, 10);
                    }
                    return $item;
                })
                ->filter()
                ->unique();

            $totalSessions = $attendanceDates->count();
            if ($totalCourseDays > 0) {
                $totalSessions = min($totalSessions, $totalCourseDays);
            }
            $presentCount = $totalSessions;
            $absentCount = max(0, $totalCourseDays - $totalSessions);

            $statusCounts = $attendanceRecords
                ->map(fn($r) => strtolower(trim((string) ($r->status ?? ''))))
                ->filter()
                ->countBy();

            $lateCount = (int) ($statusCounts['late'] ?? 0);
            $excusedCount = (int) ($statusCounts['excused'] ?? 0);
        }
    }

    $attendanceRate = $totalCourseDays > 0 ? round(($totalSessions / $totalCourseDays) * 100, 1) : 0;

    $passFailStatus = 'N/A';
    $passFailClass = 'bg-secondary';
    if ($hasExamResults && $latestResult) {
        $total = $latestResult->yes_ans + $latestResult->no_ans;
        $percentage = $total > 0 ? round(($latestResult->yes_ans / $total) * 100, 1) : 0;
        $passFailStatus = $percentage >= 50 ? 'Pass' : 'Fail';
        $passFailClass = $percentage >= 50 ? 'bg-success' : 'bg-danger';
    }

    $overallPerformance = 0;
    if ($hasExamResults && $entry->examResults->count() > 0) {
        $totalPercentage = 0;
        foreach ($entry->examResults as $result) {
            $total = $result->yes_ans + $result->no_ans;
            $totalPercentage += $total > 0 ? ($result->yes_ans / $total) * 100 : 0;
        }
        $overallPerformance = round($totalPercentage / $entry->examResults->count(), 1);
    }

    $correctAnswers = $latestResult ? $latestResult->yes_ans : 0;
    $wrongAnswers = $latestResult ? $latestResult->no_ans : 0;
    $totalAnswers = $correctAnswers + $wrongAnswers;

    $currentAdmin = backpack_user();
    $isSuperAdmin = $currentAdmin && method_exists($currentAdmin, 'isSuper') && $currentAdmin->isSuper();
    $verificationStatus = $verificationStatus ?? [
        'blocked' => (bool) ($entry->is_verification_blocked ?? false),
        'block' => ['reason_label' => null, 'message' => null],
        'attempts' => ['used' => 0, 'max' => 5, 'remaining' => 5],
    ];
    $verificationAttempts = $verificationStatus['attempts'] ?? ['used' => 0, 'max' => 5, 'remaining' => 5];
    $verificationBlock = $verificationStatus['block'] ?? ['reason_label' => null, 'message' => null];
@endphp

@section('content')
    {{-- @parent --}}

    <div class="mb-3 d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-start gap-2">
            {{-- <a href="{{ backpack_url('manage-student') }}" class="btn btn-sm btn-outline-secondary" title="Back to Students">
                <i class="la la-arrow-left"></i>
            </a> --}}
            <div>
                {{-- <h4 class="mb-0">Student Metrics</h4> --}}
                <div class="text-muted">
                    {{ $entry->name ?? 'Student' }}
                    @if (!empty($entry->email))
                        • {{ $entry->email }}
                    @endif
                </div>
                <!-- <div class="text-muted small">
                                                                User ID: {{ $entry->userId ?? 'N/A' }}
                                                            </div> -->
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if (!empty($courseId))
                <a href="{{ backpack_url('course-batch/' . $courseId . '/show') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="la la-chart-bar"></i> Course Batch Metrics
                </a>
            @endif
            <!-- <a href="{{ backpack_url('user/' . $userId . '/edit') }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="la la-edit"></i> Edit Student
                                                        </a> -->
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-id-card"></i> Basic Information</strong>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Name</td>
                            <td>{{ $entry->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>{{ $entry->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Mobile</td>
                            <td>{{ $entry->mobile_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Network Type</td>
                            <td>{{ $entry->network_type ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gender</td>
                            <td>{{ $entry->gender ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Age</td>
                            <td>{{ $entry->age ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Ghana Card</td>
                            <td>{{ $entry->ghcard ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Registered</td>
                            <td>{{ $entry->created_at ? $entry->created_at->format('Y-m-d') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Verification Blocked</td>
                            <td>
                                @if ($verificationStatus['blocked'] ?? false)
                                    <span class="badge bg-danger">Yes</span>
                                @else
                                    <span class="badge bg-success">No</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Block Reason</td>
                            <td>{{ $verificationBlock['reason_label'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Attempts (Used / Allowed)</td>
                            <td>{{ (int) ($verificationAttempts['used'] ?? 0) }} / {{ (int) ($verificationAttempts['max'] ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Attempts Remaining</td>
                            <td>{{ (int) ($verificationAttempts['remaining'] ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Shortlisted</td>
                            <td>
                                @if ($entry->shortlist)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge text-dark">No</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Login</td>
                                    <td>{{ $entry->last_login ? \Carbon\Carbon::parse($entry->last_login)->format('Y-m-d H:i:s') : 'Never' }}</td>
                        </tr>
                    </table>
                    <hr>
                    <div>
                        <div class="text-muted small mb-2">Ghana Card Verification Controls</div>
                        @if (!empty($verificationBlock['message']))
                            <div class="alert alert-warning py-2 px-3 small mb-2">{{ $verificationBlock['message'] }}</div>
                        @endif
                        <form id="verificationAttemptsForm" class="d-flex align-items-end gap-2">
                            <div class="flex-grow-1">
                                <label for="verification_attempts_to_add" class="form-label mb-1">Add Attempts</label>
                                <input id="verification_attempts_to_add" type="number" min="1" max="20" value="1" class="form-control form-control-sm" />
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Add</button>
                        </form>
                        <div class="small text-muted mt-2">
                            Adds extra attempts for this user without deleting previous verification history.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-graduation-cap"></i> Admission Status</strong>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-3">
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                @if ($hasAnyAdmission)
                                    <span class="badge bg-success">Admitted</span>
                                @else
                                    <span class="badge text-dark">Not Admitted</span>
                                @endif
                            </td>
                        </tr>
                        @if ($hasAnyAdmission)
                            <tr>
                                <td class="text-muted">Pass/Fail</td>
                                <td><span class="badge bg-warning {{ $passFailClass }}">{{ $passFailStatus }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Course</td>
                                <td>{{ $course?->course_name ?? ($currentAdmission?->course?->course_name ?? ($currentAdmission?->course?->name ?? 'N/A')) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Session</td>
                                <td>{{ $currentAdmission->courseSession->name ?? 'Not Set' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Location</td>
                                <td>{{ $currentAdmission->location ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Batch</td>
                                <td>{{ $course?->batch?->title ?? ($currentAdmission?->batch?->title ?? 'N/A') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email Sent</td>
                                <td>{{ $currentAdmission->email_sent ? $currentAdmission->email_sent->format('Y-m-d') : 'Pending' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Admitted On</td>
                                <td>{{ $currentAdmission->confirmed ? $currentAdmission->confirmed->format('Y-m-d') : 'Pending' }}
                                </td>
                            </tr>
                        @endif
                    </table>

                    @if ($isSuperAdmin)
                        @if (!$hasAnyAdmission)
                            <button type="button" class="btn btn-sm btn-primary"
                                onclick="if (window.openAdmitStudentModal) window.openAdmitStudentModal({ change: false })">
                                <i class="la la-user-plus"></i> Admit
                            </button>
                        @else
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="if (window.openAdmitStudentModal) window.openAdmitStudentModal({ change: true })">
                                    <i class="la la-user-edit"></i> Change Admission
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                    onclick="if (window.openChooseSessionModal) window.openChooseSessionModal()">
                                    <i class="la la-calendar"></i> Change Session
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="if (window.deleteStudentAdmission) window.deleteStudentAdmission()">
                                    <i class="la la-trash"></i> Delete Admission
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-poll"></i> Exam Performance</strong>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-3">
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                @if ($hasExamResults)
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge text-dark">Not Taken</span>
                                @endif
                            </td>
                        </tr>
                        @if ($hasExamResults)
                            <tr>
                                <td class="text-muted">Total Exams</td>
                                <td>{{ $entry->examResults->count() }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Latest Exam</td>
                                <td>{{ $latestResult->exam->title ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Score</td>
                                <td>
                                    @php
                                        $total = $latestResult->yes_ans + $latestResult->no_ans;
                                        $percentage =
                                            $total > 0 ? round(($latestResult->yes_ans / $total) * 100, 1) : 0;
                                    @endphp
                                    <span
                                        class="badge {{ $percentage >= 50 ? 'bg-success' : 'bg-danger' }}">{{ $percentage }}%</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Correct Answers</td>
                                <td>{{ $correctAnswers }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Wrong Answers</td>
                                <td>{{ $wrongAnswers }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Overall Performance</td>
                                <td>
                                    <span class="badge {{ $overallPerformance >= 50 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $overallPerformance }}%
                                    </span>
                                </td>
                            </tr>
                        @endif
                    </table>

                    @if ($hasExamResults)
                        <div class="d-flex gap-2 flex-wrap">
                            {{-- <a href="{{ url('admin/admin_view_result/' . $userId) }}"
                                class="btn btn-sm btn-outline-info js-view-result-modal"
                                data-url="{{ url('admin/admin_view_result/' . $userId) }}">
                                <i class="la la-poll"></i> View Results
                            </a> --}}
                            @if ($examId && $isSuperAdmin)
                                <a href="{{ route('results.reset', [$examId, $userId]) }}"
                                    class="btn btn-sm btn-outline-warning js-reset-results">
                                    <i class="la la-redo"></i> Reset Results
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Course Days</div>
                        <i class="la la-clock text-primary"></i>
                    </div>
                    @if ($startDateValid && $endDateValid)
                        @php
                            $displayStartDate =
                                $courseStartDate instanceof \Carbon\Carbon
                                    ? $courseStartDate->format('Y-m-d')
                                    : (is_string($courseStartDate)
                                        ? $courseStartDate
                                        : 'N/A');
                            $displayEndDate =
                                $courseEndDate instanceof \Carbon\Carbon
                                    ? $courseEndDate->format('Y-m-d')
                                    : (is_string($courseEndDate)
                                        ? $courseEndDate
                                        : 'N/A');
                        @endphp
                        <div class="text-muted small mt-1">{{ $displayStartDate }} to {{ $displayEndDate }}</div>
                    @endif
                    <div class="metric-value">{{ number_format($totalCourseDays) }}</div>
                    <div class="text-muted small">days</div>
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
                    <div class="metric-value">{{ number_format($totalSessions) }}</div>
                    <div class="text-muted small">records</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Absent Days</div>
                        <i class="la la-times-circle text-danger"></i>
                    </div>
                    <div class="metric-value">{{ number_format($absentCount) }}</div>
                    <div class="text-muted small">days</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Attendance Rate</div>
                        <i class="la la-percentage text-info"></i>
                    </div>
                    <div class="metric-value">{{ $attendanceRate }}%</div>
                    <div class="text-muted small">Present: {{ number_format($presentCount) }} /
                        {{ number_format($totalCourseDays) }} days</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-chart-bar"></i> Attendance Distribution</strong>
                </div>
                <div class="card-body">
                    <div class="chart-wrap-sm">
                        <canvas id="attendanceBarChart"></canvas>
                    </div>
                    <div class="mt-3 text-center">
                        <span class="badge bg-info text-dark">Total Days: {{ number_format($totalCourseDays) }}</span>
                        <span class="badge bg-success text-dark">Present: {{ number_format($presentCount) }}</span>
                        <span class="badge bg-warning text-dark">Absent: {{ number_format($absentCount) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-chart-line"></i> Performance Trend</strong>
                </div>
                <div class="card-body">
                    @if ($hasExamResults && $entry->examResults->count() > 1)
                        <div class="chart-wrap">
                            <canvas id="performanceTrendChart"></canvas>
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="la la-chart-line fa-3x mb-3"></i>
                            <p class="mb-1">Need multiple exams for trend analysis</p>
                            @if ($hasExamResults && $entry->examResults->count() == 1)
                                <p class="small mb-0">Latest score: {{ $overallPerformance }}%</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong><i class="la la-list"></i> Attendance History</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtAttendanceHistory" class="table table-sm table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Check In</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <!-- <th>Check-in</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attendanceRecords->sortByDesc('date') as $record)
                                    <tr>
                                        <td>{{ $record->date ? \Carbon\Carbon::parse($record->date)->format('Y-m-d') : '-' }}
                                        </td>
                                        <td>{{ $record->course->course_name ?? 'N/A' }}</td>
                                        <td>
                                            @switch($record->status)
                                                @case('present')
                                                    <span class="badge bg-success">Present</span>
                                                @break

                                                @case('absent')
                                                    <span class="badge bg-danger">Absent</span>
                                                @break

                                                @case('late')
                                                    <span class="badge bg-warning text-dark">Late</span>
                                                @break

                                                @case('excused')
                                                    <span class="badge bg-info">Excused</span>
                                                @break

                                                @default
                                                    <span class="badge bg-success">{{ $record->status ?? 'N/A' }}</span>
                                            @endswitch
                                        </td>
                                        <!-- <td>{{ $record->check_in_time ?? '-' }}</td> -->
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong><i class="la la-users"></i> Batch Information</strong>
                </div>
                <div class="card-body">
                    @if ($course && $course->batch)
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Batch</td>
                                    <td>
                                        @if (!empty($course->batch->id))
                                            <a
                                                href="{{ backpack_url('batch/' . $course->batch->id . '/edit') }}">{{ $course->batch->title ?? 'N/A' }}</a>
                                        @else
                                            {{ $course->batch->title ?? 'N/A' }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Year</td>
                                    <td>{{ $course->batch->year ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Start Date</td>
                                    <td>{{ $course->batch->start_date ? ($course->batch->start_date instanceof \Carbon\Carbon ? $course->batch->start_date->format('Y-m-d') : \Carbon\Carbon::parse($course->batch->start_date)->format('Y-m-d')) : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">End Date</td>
                                    <td>{{ $course->batch->end_date ? ($course->batch->end_date instanceof \Carbon\Carbon ? $course->batch->end_date->format('Y-m-d') : \Carbon\Carbon::parse($course->batch->end_date)->format('Y-m-d')) : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Status</td>
                                    <td>
                                        @if ($course->batch->completed)
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Ongoing</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <p>No batch information available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong><i class="la la-poll"></i> Exam Results</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtExamResults" class="table table-sm table-striped table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Score</th>
                                    <th>Result</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entry->examResults->sortByDesc('created_at') as $result)
                                    <tr>
                                        <td>{{ $result->exam->title ?? 'N/A' }}</td>
                                        @php
                                            $totalAns = $result->yes_ans + $result->no_ans;
                                            $percentage =
                                                $totalAns > 0 ? round(($result->yes_ans / $totalAns) * 100, 1) : 0;
                                        @endphp
                                        <td>{{ $percentage }}% <small
                                                class="text-muted">({{ $result->yes_ans }}/{{ $totalAns }})</small>
                                        </td>
                                        <td>
                                            @if ($percentage >= 50)
                                                <span class="badge bg-success">Pass</span>
                                            @else
                                                <span class="badge bg-danger">Fail</span>
                                            @endif
                                        </td>
                                        <td>{{ $result->created_at ? $result->created_at->format('Y-m-d') : 'N/A' }}</td>
                                        <td>
                                            @if ($result->exam)
                                                <a href="{{ url('admin/admin_view_result/' . $userId) }}"
                                                    class="btn btn-sm btn-outline-primary js-view-result-modal"
                                                    data-url="{{ url('admin/admin_view_result/' . $userId) }}">
                                                    <i class="la la-eye"></i> View
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($entry->examResults->isEmpty())
                        <div class="text-center text-muted py-2">No exam results available</div>
                    @endif
                </div>
            </div>
        </div> --}}
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <strong><i class="la la-history"></i> Activity Logs</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtActivityLogs" class="table table-sm table-striped table-hover w-100 mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 200px;">Date</th>
                                    <th>Description</th>
                                    <th>Properties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activities as $activity)
                                    <tr>
                                        <td class="text-nowrap">{{ $activity->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>{{ $activity->description }}</td>
                                        <td>{{ $activity->properties }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                border: 1px solid #dee2e6;
                padding: .25rem .5rem;
                border-radius: .25rem;
            }

            .dataTables_wrapper .dataTables_length select {
                margin: 0 .25rem;
                border: 1px solid #dee2e6;
                padding: .2rem .4rem;
                border-radius: .25rem;
            }

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                margin-top: 0.5rem;
                margin-bottom: 1.25rem;
            }

            .table thead th {
                text-transform: uppercase;
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.025em;
                color: #6c757d;
                border-top: none;
                background-color: #f8f9fa;
            }

            #dtActivityLogs td {
                vertical-align: middle !important;
                padding-top: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }

            .table-hover tbody tr:hover {
                background-color: rgba(13, 110, 253, 0.04);
            }

            .card {
                border: none;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                border-radius: 0.5rem;
            }

            .card-header {
                background-color: transparent;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                padding: 1rem 1.25rem;
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
            (function() {
                "use strict";

                const USER_ID = {{ (int) $userId }};
                const CURRENT_COURSE_ID = {{ (int) ($currentCourseId ?? 0) }};
                const CURRENT_SESSION_ID = {{ (int) ($currentSessionId ?? 0) }};
                const CHANGE_ADMISSION_URL = @json(route('manage-student.change-admission', ['user' => $userId]));
                const CHOOSE_SESSION_URL = @json(route('manage-student.choose-session', ['user' => $userId]));
                const ADD_VERIFICATION_ATTEMPTS_URL = @json(route('manage-student.add-verification-attempts', ['user' => $userId]));
                const DELETE_ADMISSION_URL = @json(route('manage-student.delete-admission', ['user_id' => $userId]));

                function getCsrfToken() {
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    return meta ? meta.getAttribute('content') : '';
                }

                function cleanupModalBackdrops() {
                    document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('padding-right');
                    document.body.style.removeProperty('overflow');
                }

                function confirmWithSweetAlert(options) {
                    const opts = options || {};
                    const title = opts.title || 'Are you sure?';
                    const text = opts.text || '';
                    const icon = opts.icon || 'warning';
                    const confirmText = opts.confirmText || 'Yes, continue';
                    const cancelText = opts.cancelText || 'Cancel';

                    const swal2 =
                        (window.Swal && typeof window.Swal.fire === 'function') ? window.Swal :
                        (window.swal && typeof window.swal.fire === 'function') ? window.swal :
                        null;

                    if (swal2) {
                        return swal2.fire({
                            title: title,
                            text: text,
                            icon: icon,
                            showCancelButton: true,
                            confirmButtonText: confirmText,
                            cancelButtonText: cancelText,
                            reverseButtons: true,
                        }).then((result) => Boolean(result && result.isConfirmed));
                    }

                    if (window.swal && typeof window.swal === 'function') {
                        return window.swal({
                            title: title,
                            text: text,
                            icon: icon,
                            buttons: [cancelText, confirmText],
                            dangerMode: icon === 'warning' || icon === 'error',
                        }).then((ok) => Boolean(ok));
                    }

                    return Promise.resolve(confirm(text || title));
                }

                function showModal(modalId) {
                    // Prevent stacked backdrops which can "lock" the page.
                    cleanupModalBackdrops();
                    const el = document.getElementById(modalId);
                    if (!el) return;
                    // Ensure the modal is a direct child of <body> to avoid stacking-context issues
                    // where the backdrop ends up above the modal (Tabler/Backpack layouts can create
                    // stacking contexts that trap fixed-position elements).
                    if (el.parentElement !== document.body) {
                        document.body.appendChild(el);
                    }
                    if (window.bootstrap && window.bootstrap.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(el, {
                            backdrop: true,
                            keyboard: true
                        }).show();
                        return;
                    }
                    const jq = window.jQuery;
                    if (jq && jq.fn && jq.fn.modal) {
                        jq(el).modal('show');
                    }
                }

                function hideModal(modalId) {
                    const el = document.getElementById(modalId);
                    if (!el) return;
                    if (window.bootstrap && window.bootstrap.Modal) {
                        const instance = window.bootstrap.Modal.getInstance(el);
                        if (instance) instance.hide();
                        return;
                    }
                    const jq = window.jQuery;
                    if (jq && jq.fn && jq.fn.modal) {
                        jq(el).modal('hide');
                    }
                }

                function openExamResultModal(url) {
                    if (!url) return;
                    const frame = document.getElementById('examResultFrame');
                    if (frame) frame.setAttribute('src', url);
                    showModal('examResultModal');
                }

                function filterSessionsByCourse(selectEl, courseId) {
                    if (!selectEl) return;
                    const course = String(courseId || '');
                    Array.from(selectEl.options || []).forEach((opt) => {
                        if (!opt || opt.value === '') return;
                        const optCourse = opt.getAttribute('data-course');
                        if (!optCourse) return;
                        const match = String(optCourse) === course;
                        opt.disabled = !match;
                        opt.hidden = !match;
                    });

                    // If current selection becomes invalid, reset it.
                    const selected = selectEl.selectedOptions && selectEl.selectedOptions.length ? selectEl.selectedOptions[
                        0] : null;
                    if (selected && (selected.disabled || selected.hidden)) {
                        selectEl.value = '';
                    }
                }

                function safeInitDataTable(selector, options) {
                    const $ = window.jQuery;
                    if (!$ || !$.fn || !$.fn.DataTable) return;

                    const $el = $(selector);
                    if (!$el.length) return;
                    if ($.fn.DataTable.isDataTable($el[0])) return;

                    // Guard against invalid table markup (eg. colspan rows) that can crash DataTables.
                    try {
                        const tableEl = $el[0];
                        const expectedCols = tableEl && tableEl.tHead && tableEl.tHead.rows && tableEl.tHead.rows.length ?
                            tableEl.tHead.rows[0].cells.length :
                            0;

                        if (expectedCols && tableEl.tBodies && tableEl.tBodies.length) {
                            Array.from(tableEl.tBodies[0].rows || []).forEach((row) => {
                                const cells = row ? row.cells : null;
                                if (!cells || cells.length !== expectedCols) {
                                    row && row.remove();
                                    return;
                                }
                                for (const cell of Array.from(cells)) {
                                    const colspan = parseInt(cell.getAttribute('colspan') || '1', 10);
                                    const rowspan = parseInt(cell.getAttribute('rowspan') || '1', 10);
                                    if (colspan > 1 || rowspan > 1) {
                                        row && row.remove();
                                        return;
                                    }
                                }
                            });
                        }
                    } catch (e) {
                        // ignore and attempt init
                    }

                    try {
                        $el.DataTable(Object.assign({
                            pageLength: 10,
                            lengthMenu: [
                                [10, 25, 50, 100],
                                [10, 25, 50, 100]
                            ],
                            // Responsive extension is optional and can throw on some DOM edge-cases.
                            // We keep tables simple here (search + pagination).
                            responsive: false,
                            deferRender: true,
                            language: {
                                search: "",
                                searchPlaceholder: "Search...",
                                emptyTable: options.emptyTable || "No data available in table",
                                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                                infoEmpty: "No entries to show",
                                paginate: {
                                    next: '<i class="la la-angle-right"></i>',
                                    previous: '<i class="la la-angle-left"></i>'
                                }
                            },
                        }, options || {}));
                    } catch (e) {
                        console.warn('Failed to initialize DataTable for', selector, e);
                    }
                }

                // Ensure we always cleanup any stuck backdrop (works for both BS4/jQuery and BS5/native)
                (function bindModalCleanup() {
                    const ids = ['admitModal', 'chooseSessionModal', 'examResultModal'];
                    ids.forEach((id) => {
                        const el = document.getElementById(id);
                        if (!el || el.__cleanupBound) return;
                        el.__cleanupBound = true;

                        try {
                            el.addEventListener('hidden.bs.modal', function() {
                                if (id === 'examResultModal') {
                                    const frame = document.getElementById('examResultFrame');
                                    if (frame) frame.setAttribute('src', 'about:blank');
                                }
                                cleanupModalBackdrops();
                            });
                        } catch (e) {
                            // ignore
                        }

                        const jq = window.jQuery;
                        if (jq && jq.fn && typeof jq.fn.on === 'function') {
                            jq(el).on('hidden.bs.modal', function() {
                                if (id === 'examResultModal') {
                                    const frame = document.getElementById('examResultFrame');
                                    if (frame) frame.setAttribute('src', 'about:blank');
                                }
                                cleanupModalBackdrops();
                            });
                        }
                    });
                })();

                window.openAdmitStudentModal = function(opts) {
                    const change = Boolean(opts && opts.change);

                    const userInput = document.getElementById('admit_user_id');
                    if (userInput) userInput.value = String(USER_ID);

                    const changeInput = document.getElementById('admit_change');
                    if (changeInput) changeInput.value = change ? 'true' : 'false';

                    const title = document.getElementById('admitModalLabel');
                    if (title) title.textContent = change ? 'Change Admission' : 'Admit Student';

                    const submitBtn = document.getElementById('admitSubmitBtn');
                    if (submitBtn) submitBtn.textContent = change ? 'Save Changes' : 'Admit Student';

                    const courseSelect = document.getElementById('course_id');
                    const sessionSelect = document.getElementById('session_id');

                    if (courseSelect && change && CURRENT_COURSE_ID) {
                        courseSelect.value = String(CURRENT_COURSE_ID);
                    }

                    if (sessionSelect && courseSelect) {
                        filterSessionsByCourse(sessionSelect, courseSelect.value || '');

                        if (change && CURRENT_SESSION_ID) {
                            sessionSelect.value = String(CURRENT_SESSION_ID);
                        }
                    }

                    showModal('admitModal');
                };

                window.openChooseSessionModal = function() {
                    const title = document.getElementById('chooseSessionModalLabel');
                    if (title) title.textContent = 'Choose Session';

                    const form = document.getElementById('chooseSessionForm');
                    if (form) form.setAttribute('action', CHOOSE_SESSION_URL);

                    const sessionSelect = document.getElementById('choose_session_id');
                    if (sessionSelect && CURRENT_COURSE_ID) {
                        filterSessionsByCourse(sessionSelect, CURRENT_COURSE_ID);
                        if (CURRENT_SESSION_ID) sessionSelect.value = String(CURRENT_SESSION_ID);
                    }

                    showModal('chooseSessionModal');
                };

                window.deleteStudentAdmission = function() {
                    const doDelete = function() {
                        const jq = window.jQuery;
                        const csrf = getCsrfToken();

                        if (jq && typeof jq.ajax === 'function') {
                            jq.ajax({
                                url: DELETE_ADMISSION_URL,
                                type: 'DELETE',
                                headers: Object.assign({
                                        'Accept': 'application/json'
                                    },
                                    csrf ? {
                                        'X-CSRF-TOKEN': csrf
                                    } : {}
                                ),
                                success: function(resp) {
                                    const message = resp && resp.message ? resp.message :
                                        'Admission deleted successfully.';
                                    if (window.Noty) new Noty({
                                        type: "success",
                                        text: message
                                    }).show();
                                    window.location.reload();
                                },
                                error: function(xhr) {
                                    const message = (xhr && xhr.responseJSON && xhr.responseJSON
                                            .message) ? xhr.responseJSON.message :
                                        'Failed to delete admission.';
                                    if (window.Noty) new Noty({
                                        type: "error",
                                        text: message
                                    }).show();
                                }
                            });
                            return;
                        }

                        if (window.fetch) {
                            fetch(DELETE_ADMISSION_URL, {
                                method: 'DELETE',
                                headers: Object.assign({
                                    'Accept': 'application/json'
                                }, csrf ? {
                                    'X-CSRF-TOKEN': csrf
                                } : {}),
                            }).then((r) => r.json()).then((resp) => {
                                const message = resp && resp.message ? resp.message :
                                    'Admission deleted successfully.';
                                alert(message);
                                window.location.reload();
                            }).catch(() => alert('Failed to delete admission.'));
                        }
                    };

                    confirmWithSweetAlert({
                        title: "Are you sure?",
                        text: "Are you sure you want to delete this student’s admission?",
                        icon: "warning",
                        confirmText: "Yes, delete it!",
                        cancelText: "Cancel",
                    }).then((ok) => {
                        if (ok) doDelete();
                    });
                };

                document.addEventListener('DOMContentLoaded', function() {
                    // Open exam result in modal (works for static rows and future dynamic buttons).
                    document.addEventListener('click', function(e) {
                        const trigger = e.target.closest('.js-view-result-modal');
                        if (!trigger) return;
                        e.preventDefault();
                        const url = trigger.getAttribute('data-url') || trigger.getAttribute('href');
                        openExamResultModal(url);
                    });

                    // Datatables
                    safeInitDataTable('#dtAttendanceHistory', {
                        order: [
                            [0, 'desc']
                        ]
                    });
                    safeInitDataTable('#dtExamResults', {
                        order: [
                            [3, 'desc']
                        ]
                    });
                    safeInitDataTable('#dtActivityLogs', {
                        order: [
                            [0, 'desc']
                        ]
                    });

                    // Reset results confirm (SweetAlert)
                    document.querySelectorAll('.js-reset-results').forEach((btn) => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            const url = this.getAttribute('href');
                            if (!url) return;

                            confirmWithSweetAlert({
                                title: "Reset Results?",
                                text: "This will reset this student’s exam result. Continue?",
                                icon: "warning",
                                confirmText: "Yes, reset it!",
                                cancelText: "Cancel",
                            }).then((ok) => {
                                if (!ok) return;
                                window.location.href = url;
                            });
                        });
                    });

                    const jq = window.jQuery;
                    if (jq && jq.fn && jq.fn.modal) {
                        jq('#admitModal, #chooseSessionModal, #examResultModal').on('hidden.bs.modal',
                            cleanupModalBackdrops);
                    }

                    // Admit modal: filter sessions by selected course
                    const courseSelect = document.getElementById('course_id');
                    const sessionSelect = document.getElementById('session_id');
                    if (courseSelect && sessionSelect) {
                        courseSelect.addEventListener('change', function() {
                            filterSessionsByCourse(sessionSelect, this.value || '');
                        });
                    }

                    // Admit / Change Admission submit via AJAX
                    const admitForm = document.getElementById('admitForm');
                    if (admitForm) {
                        admitForm.addEventListener('submit', function(e) {
                            e.preventDefault();

                            const courseId = document.getElementById('course_id') ? document.getElementById(
                                'course_id').value : '';
                            const sessionId = document.getElementById('session_id') ? document
                                .getElementById('session_id').value : '';
                            const change = document.getElementById('admit_change') ? document
                                .getElementById('admit_change').value === 'true' : false;

                            if (!courseId) {
                                if (window.Noty) new Noty({
                                    type: "error",
                                    text: "Please select a course."
                                }).show();
                                return;
                            }
                            if (!sessionId) {
                                if (window.Noty) new Noty({
                                    type: "error",
                                    text: "Please choose a session."
                                }).show();
                                return;
                            }

                            const submitBtn = document.getElementById('admitSubmitBtn');
                            if (submitBtn) submitBtn.disabled = true;

                            confirmWithSweetAlert({
                                title: change ? "Change Admission?" : "Admit Student?",
                                text: change ?
                                    "This will update this student’s admission details. Continue?" :
                                    "This will admit this student and assign a session. Continue?",
                                icon: "warning",
                                confirmText: change ? "Yes, change it!" : "Yes, admit!",
                                cancelText: "Cancel",
                            }).then((ok) => {
                                if (!ok) {
                                    if (submitBtn) submitBtn.disabled = false;
                                    return;
                                }

                                const jq = window.jQuery;
                                const csrf = getCsrfToken();
                                const url = admitForm.getAttribute('action') ||
                                    CHANGE_ADMISSION_URL;

                                if (jq && typeof jq.ajax === 'function') {
                                    jq.ajax({
                                        url: url,
                                        type: 'POST',
                                        data: {
                                            course_id: courseId,
                                            session_id: sessionId
                                        },
                                        headers: Object.assign({
                                                'Accept': 'application/json'
                                            },
                                            csrf ? {
                                                'X-CSRF-TOKEN': csrf
                                            } : {}
                                        ),
                                        success: function(resp) {
                                            const message = resp && resp.message ? resp
                                                .message : (change ?
                                                    'Admission updated successfully.' :
                                                    'Student admitted successfully.');
                                            if (window.Noty) new Noty({
                                                type: "success",
                                                text: message
                                            }).show();
                                            hideModal('admitModal');
                                            window.location.reload();
                                        },
                                        error: function(xhr) {
                                            let message = 'Failed to save admission.';
                                            if (xhr && xhr.responseJSON) {
                                                if (xhr.responseJSON.message) message =
                                                    xhr.responseJSON.message;
                                                if (xhr.responseJSON.errors) {
                                                    const firstKey = Object.keys(xhr
                                                        .responseJSON.errors)[0];
                                                    if (firstKey && xhr.responseJSON
                                                        .errors[firstKey] && xhr
                                                        .responseJSON.errors[firstKey][
                                                            0
                                                        ]) {
                                                        message = xhr.responseJSON
                                                            .errors[firstKey][0];
                                                    }
                                                }
                                            }
                                            if (window.Noty) new Noty({
                                                type: "error",
                                                text: message
                                            }).show();
                                        },
                                        complete: function() {
                                            if (submitBtn) submitBtn.disabled = false;
                                        }
                                    });
                                    return;
                                }

                                if (window.fetch) {
                                    fetch(url, {
                                        method: 'POST',
                                        headers: Object.assign({
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json'
                                            },
                                            csrf ? {
                                                'X-CSRF-TOKEN': csrf
                                            } : {}
                                        ),
                                        body: JSON.stringify({
                                            course_id: courseId,
                                            session_id: sessionId
                                        }),
                                    }).then((r) => r.json()).then((resp) => {
                                        const message = resp && resp.message ? resp
                                            .message : (change ?
                                                'Admission updated successfully.' :
                                                'Student admitted successfully.');
                                        if (window.Noty) new Noty({
                                            type: "success",
                                            text: message
                                        }).show();
                                        hideModal('admitModal');
                                        window.location.reload();
                                    }).catch(() => {
                                        if (window.Noty) new Noty({
                                            type: "error",
                                            text: "Failed to save admission."
                                        }).show();
                                    }).finally(() => {
                                        if (submitBtn) submitBtn.disabled = false;
                                    });
                                }
                            });
                        });
                    }

                    // Choose session modal submit via AJAX (controller returns JSON)
                    const chooseForm = document.getElementById('chooseSessionForm');
                    if (chooseForm) {
                        chooseForm.addEventListener('submit', function(e) {
                            e.preventDefault();

                            const sessionId = document.getElementById('choose_session_id') ? document
                                .getElementById('choose_session_id').value : '';
                            if (!sessionId) {
                                alert('Please choose a session.');
                                return;
                            }

                            const confirmTitle = "Change Session?";
                            const confirmText = "This will change the student’s course session. Continue?";

                            const jq = window.jQuery;
                            const csrf = getCsrfToken();
                            const url = chooseForm.getAttribute('action') || CHOOSE_SESSION_URL;

                            const doUpdate = function() {
                                if (jq && typeof jq.ajax === 'function') {
                                    jq.ajax({
                                        url: url,
                                        type: 'POST',
                                        data: {
                                            session_id: sessionId
                                        },
                                        headers: Object.assign({
                                                'Accept': 'application/json'
                                            },
                                            csrf ? {
                                                'X-CSRF-TOKEN': csrf
                                            } : {}
                                        ),
                                        success: function(resp) {
                                            const message = resp && resp.message ? resp
                                                .message : 'Session updated successfully.';
                                            if (window.Noty) new Noty({
                                                type: "success",
                                                text: message
                                            }).show();
                                            hideModal('chooseSessionModal');
                                            window.location.reload();
                                        },
                                        error: function(xhr) {
                                            const message = (xhr && xhr.responseJSON && xhr
                                                    .responseJSON.message) ? xhr
                                                .responseJSON.message :
                                                'Failed to update session.';
                                            if (window.Noty) new Noty({
                                                type: "error",
                                                text: message
                                            }).show();
                                        }
                                    });
                                    return;
                                }

                                if (window.fetch) {
                                    fetch(url, {
                                        method: 'POST',
                                        headers: Object.assign({
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json'
                                        }, csrf ? {
                                            'X-CSRF-TOKEN': csrf
                                        } : {}),
                                        body: JSON.stringify({
                                            session_id: sessionId
                                        }),
                                    }).then((r) => r.json()).then((resp) => {
                                        const message = resp && resp.message ? resp.message :
                                            'Session updated successfully.';
                                        alert(message);
                                        hideModal('chooseSessionModal');
                                        window.location.reload();
                                    }).catch(() => alert('Failed to update session.'));
                                }
                            };

                            confirmWithSweetAlert({
                                title: confirmTitle,
                                text: confirmText,
                                icon: "warning",
                                confirmText: "Yes, change it!",
                                cancelText: "Cancel",
                            }).then((ok) => {
                                if (ok) doUpdate();
                            });
                        });
                    }

                    const verificationAttemptsForm = document.getElementById('verificationAttemptsForm');
                    if (verificationAttemptsForm) {
                        verificationAttemptsForm.addEventListener('submit', function(e) {
                            e.preventDefault();

                            const input = document.getElementById('verification_attempts_to_add');
                            const attemptsToAdd = parseInt(input ? input.value : '0', 10);
                            if (!Number.isInteger(attemptsToAdd) || attemptsToAdd < 1) {
                                if (window.Noty) new Noty({
                                    type: "error",
                                    text: "Enter a valid number of attempts."
                                }).show();
                                return;
                            }

                            const jq = window.jQuery;
                            const csrf = getCsrfToken();
                            const payload = {
                                attempts: attemptsToAdd
                            };

                            const onSuccess = function(resp) {
                                const message = resp && resp.message ? resp.message :
                                    'Additional verification attempts added successfully.';
                                if (window.Noty) new Noty({
                                    type: "success",
                                    text: message
                                }).show();
                                window.location.reload();
                            };

                            const onError = function(message) {
                                if (window.Noty) new Noty({
                                    type: "error",
                                    text: message || 'Failed to add verification attempts.'
                                }).show();
                            };

                            if (jq && typeof jq.ajax === 'function') {
                                jq.ajax({
                                    url: ADD_VERIFICATION_ATTEMPTS_URL,
                                    type: 'POST',
                                    data: payload,
                                    headers: Object.assign({
                                        'Accept': 'application/json'
                                    }, csrf ? {
                                        'X-CSRF-TOKEN': csrf
                                    } : {}),
                                    success: onSuccess,
                                    error: function(xhr) {
                                        const message = (xhr && xhr.responseJSON && xhr.responseJSON.message) ?
                                            xhr.responseJSON.message : 'Failed to add verification attempts.';
                                        onError(message);
                                    }
                                });
                                return;
                            }

                            if (window.fetch) {
                                fetch(ADD_VERIFICATION_ATTEMPTS_URL, {
                                    method: 'POST',
                                    headers: Object.assign({
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    }, csrf ? {
                                        'X-CSRF-TOKEN': csrf
                                    } : {}),
                                    body: JSON.stringify(payload),
                                }).then((r) => r.json())
                                .then((resp) => {
                                    if (resp && resp.success) {
                                        onSuccess(resp);
                                        return;
                                    }
                                    onError(resp && resp.message ? resp.message : 'Failed to add verification attempts.');
                                }).catch(() => onError('Failed to add verification attempts.'));
                            }
                        });
                    }

                    // Charts (Chart.js v2)
                    if (typeof Chart !== 'function') return;

                    const attendanceCtx = document.getElementById('attendanceBarChart');
                    if (attendanceCtx) {
                        const totalDays = {{ (int) $totalCourseDays }};
                        const presentDays = {{ (int) $presentCount }};
                        const absentDays = {{ (int) $absentCount }};

                        new Chart(attendanceCtx, {
                            type: 'bar',
                            data: {
                                labels: ['Total Days', 'Present', 'Absent'],
                                datasets: [{
                                    label: 'Days',
                                    data: [totalDays, presentDays, absentDays],
                                    backgroundColor: [
                                        'rgba(13, 110, 253, 0.35)',
                                        'rgba(25, 135, 84, 0.8)',
                                        'rgba(218, 149, 22, 0.91)'

                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                legend: {
                                    display: false
                                },
                                scales: {
                                    yAxes: [{
                                        ticks: {
                                            beginAtZero: true,
                                            stepSize: 1
                                        }
                                    }]
                                }
                            }
                        });
                    }

                    const performanceCtx = document.getElementById('performanceTrendChart');
                    if (performanceCtx) {
                        const examResults = @json($entry->examResults->sortBy('created_at')->values());
                        const labels = examResults.map((result, index) => 'Exam ' + (index + 1));
                        const scores = examResults.map((result) => {
                            const total = (result.yes_ans || 0) + (result.no_ans || 0);
                            return total > 0 ? Math.round(((result.yes_ans || 0) / total) * 100) : 0;
                        });

                        new Chart(performanceCtx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Score (%)',
                                    data: scores,
                                    borderColor: 'rgba(13, 110, 253, 1)',
                                    backgroundColor: 'rgba(13, 110, 253, 0.15)',
                                    borderWidth: 2,
                                    fill: true,
                                    lineTension: 0.25,
                                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                                    pointBorderColor: '#fff',
                                    pointBorderWidth: 2,
                                    pointRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                scales: {
                                    yAxes: [{
                                        ticks: {
                                            beginAtZero: true,
                                            max: 100,
                                            stepSize: 10,
                                            callback: function(value) {
                                                return value + '%';
                                            }
                                        }
                                    }]
                                }
                            }
                        });
                    }
                });
            })();
        </script>
    @endpush

    @include('vendor.backpack.crud.modals.admit', [
        'form_action' => route('manage-student.change-admission', ['user' => $userId]),
        'form_id' => 'admitForm',
        'user_id_input_id' => 'admit_user_id',
        'change_input_id' => 'admit_change',
        'submit_btn_id' => 'admitSubmitBtn',
        'submit_text' => __('Admit Student'),
        'show_cancel' => true,
    ])

    <div class="modal fade" id="chooseSessionModal" tabindex="-1" aria-labelledby="chooseSessionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center">
                    <h5 class="modal-title" id="chooseSessionModalLabel">Choose Session</h5>
                    <button type="button" class="close ms-auto ml-auto" style="margin-left:auto" data-dismiss="modal"
                        data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="chooseSessionForm"
                        action="{{ route('manage-student.choose-session', ['user' => $userId]) }}"
                        name="choose_session_form" method="POST">
                        {{ csrf_field() }}
                        <div class="mb-3">
                            <label for="choose_session_id" class="form-label">Choose Session</label>
                            <select id="choose_session_id" name="session_id" class="form-select"
                                @if (empty($sessions ?? null)) disabled @endif>
                                @if (empty($sessions ?? null))
                                    <option value="">No sessions available</option>
                                @else
                                    <option value="">Choose One Session</option>
                                    @foreach ($sessions ?? [] as $session)
                                        <option data-course="{{ $session->course_id }}" value="{{ $session->id }}">
                                            {{ $session->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary flex-grow-1" data-dismiss="modal"
                                data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-success flex-grow-1" type="submit"
                                @if (empty($sessions ?? null)) disabled @endif>Save Session</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="examResultModal" tabindex="-1" aria-labelledby="examResultModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center">
                    <h5 class="modal-title" id="examResultModalLabel">Exam Result</h5>
                    <button type="button" class="close ms-auto ml-auto" style="margin-left:auto" data-dismiss="modal"
                        data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="examResultFrame" src="about:blank" style="width:100%;height:75vh;border:0;"
                        loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection
