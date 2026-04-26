@extends('crud::show')

@php
    /** @var \App\Models\Centre $entry */
    $centre = $entry;
    $centreId = (int) $centre->getKey();

    $centre->loadMissing(['branch', 'constituency', 'districts']);
    $branch = $centre->branch;
    $constituency = $centre->constituency;
    $districtTitles = $centre->districts?->pluck('title')->filter()->sort()->values() ?? collect();
    $totalDistricts = $districtTitles->count();
    $currentAdmin = backpack_user();
    $isCentreOfficer = $currentAdmin
        && method_exists($currentAdmin, 'hasRole')
        && (
            $currentAdmin->hasRole('Centre Ofiicer')
            || $currentAdmin->hasRole('Centre Officer')
            || $currentAdmin->hasRole('centre-officer')
            || $currentAdmin->hasRole('centre-manager')
        );

    $today = now()->toDateString();

    $courseIdsArray = \Illuminate\Support\Facades\DB::table('courses')
        ->where('centre_id', $centreId)
        ->pluck('id')
        ->map(fn($id) => (int) $id)
        ->all();
    $admissionBatchDateRange = \Illuminate\Support\Facades\DB::table('courses as c')
        ->join('admission_batches as ab', 'ab.id', '=', 'c.batch_id')
        ->where('c.centre_id', $centreId)
        ->selectRaw('MIN(DATE(ab.start_date)) as min_start_date, MAX(DATE(ab.end_date)) as max_end_date')
        ->first();
    $admissionFilterMinDate = $admissionBatchDateRange->min_start_date ?? null;
    $admissionFilterMaxDate = $admissionBatchDateRange->max_end_date ?? null;

    $centreSessions = \App\Models\MasterSession::active()
        ->orderByRaw(
            "
            CASE LOWER(TRIM(COALESCE(session_type, '')))
                WHEN 'morning' THEN 1
                WHEN 'afternoon' THEN 2
                WHEN 'evening' THEN 3
                WHEN 'fullday' THEN 4
                WHEN 'online' THEN 5
                ELSE 6
            END
        ",
        )
        ->orderBy('id')
        ->get();
    $centreSessionIds = $centreSessions->pluck('id')->map(fn($id) => (int) $id)->values()->all();
    $centreShortSessionLimit = $centre->short_slots_per_day !== null ? (int) $centre->short_slots_per_day : null;
    $centreLongSessionLimit = $centre->long_slots_per_day !== null ? (int) $centre->long_slots_per_day : null;
    $centreSeatCountLimit = $centre->seat_count !== null ? (int) $centre->seat_count : null;
    $centreSessionConfirmed = collect();
    if (!empty($centreSessionIds)) {
        $centreSessionConfirmed = \Illuminate\Support\Facades\DB::table('user_admission as ua')
            ->join('users as u', 'u.userId', '=', 'ua.user_id')
            ->join('courses as assigned_course', 'assigned_course.id', '=', 'u.registered_course')
            ->where('assigned_course.centre_id', $centreId)
            ->whereIn('ua.session', $centreSessionIds)
            ->whereNotNull('ua.confirmed')
            ->selectRaw('ua.session, COUNT(*) as count')
            ->groupBy('ua.session')
            ->pluck('count', 'ua.session');
    }
    $centreSessionChartLabels = $centreSessions
        ->map(function ($session) {
            $sessionLabel = trim((string) ($session->session_type ?? $session->master_name ?? 'Session #' . $session->id));
            $courseTime = trim((string) ($session->time ?? ''));

            return $courseTime !== '' ? $sessionLabel . ' (' . $courseTime . ')' : $sessionLabel;
        })
        ->values();
    $centreSessionChartValues = $centreSessions
        ->map(fn($session) => (int) ($centreSessionConfirmed[$session->id] ?? 0))
        ->values();

    $centreAdmittedStudents = collect();
    $centreSessionFilterOptions = collect();
    $centreCourseFilterOptions = collect();
    if (!empty($centreSessionIds)) {
        $centreAdmittedStudents = \Illuminate\Support\Facades\DB::table('user_admission as ua')
            ->join('users as u', 'u.userId', '=', 'ua.user_id')
            ->join('courses as assigned_course', 'assigned_course.id', '=', 'u.registered_course')
            ->leftJoin('courses as admission_course', 'admission_course.id', '=', 'ua.course_id')
            ->leftJoin('programme_batches as pb', 'pb.id', '=', 'ua.programme_batch_id')
            ->join('master_sessions as ms', 'ms.id', '=', 'ua.session')
            ->where('assigned_course.centre_id', $centreId)
            ->whereIn('ua.session', $centreSessionIds)
            ->whereNotNull('ua.confirmed')
            ->whereNotNull('ua.session')
            ->select([
                'u.userId as user_id',
                'u.name as user_name',
                'u.email as user_email',
                \Illuminate\Support\Facades\DB::raw('COALESCE(admission_course.course_name, assigned_course.course_name) as course_name'),
                'ms.session_type as session_label',
                'ms.time as session_time',
                'ms.master_name as session_name',
                \Illuminate\Support\Facades\DB::raw('DATE(pb.start_date) as programme_batch_start_date'),
                \Illuminate\Support\Facades\DB::raw('DATE(pb.end_date) as programme_batch_end_date'),
            ])
            ->orderByDesc('ua.confirmed')
            ->get()
            ->map(function ($student) {
                $sessionLabel = trim((string) ($student->session_label ?? ''));
                $sessionTime = trim((string) ($student->session_time ?? ''));

                $student->session_filter_label = $sessionLabel !== ''
                    ? $sessionLabel . ($sessionTime !== '' ? ' (' . $sessionTime . ')' : '')
                    : null;

                return $student;
            });

        $centreSessionFilterOptions = $centreAdmittedStudents
            ->pluck('session_filter_label')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $centreCourseFilterOptions = $centreAdmittedStudents
            ->pluck('course_name')
            ->map(fn ($courseName) => trim((string) $courseName))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    $activeCourseIdsArray = \Illuminate\Support\Facades\DB::table('courses as c')
        ->join('admission_batches as ab', 'c.batch_id', '=', 'ab.id')
        ->where('c.centre_id', $centreId)
        ->where('ab.completed', 0)
        ->where('ab.status', 1)
        ->pluck('c.id')
        ->map(fn($id) => (int) $id)
        ->unique()
        ->values()
        ->all();

    $totalCourses = 0;
    if (!empty($activeCourseIdsArray)) {
        $totalCourses = \Illuminate\Support\Facades\DB::table('admission_batches as ab')
            ->join('courses as c2', 'c2.batch_id', '=', 'ab.id')
            ->where('c2.centre_id', $centreId)
            ->where('ab.completed', 0)
            ->where('ab.status', 1)
            ->select('ab.id')
            ->selectRaw('COUNT(DISTINCT c2.programme_id) as courses_count')
            ->groupBy('ab.id')
            ->get()
            ->sum('courses_count');
    }
    $ongoingCourses = 0;
    $totalRegisteredUsers = 0;
    $totalShortlistedUsers = 0;
    $admissionsTotal = 0;
    $admissionsConfirmed = 0;
    $admissionsPending = 0;
    $totalAdmittedUsers = 0;
    $admissionRate = 0;
    $shortlistRate = 0;
    $coursesWithRegistrations = 0;
    $coursesWithoutRegistrations = 0;
    $courseRegistrationCoverageRate = 0;
    $onlineCourses = 0;
    $inPersonCourses = 0;
    $otherDeliveryCourses = 0;
    $supportYes = 0;
    $supportNo = 0;
    $supportUnknown = 0;
    $deliveryLabels = collect();
    $deliveryValues = collect();
    $supportLabels = collect();
    $supportValues = collect();

    $genderLabels = collect(['Male', 'Female']);
    $genderValues = collect([0, 0]);

    $topCourses = collect();

    if (!empty($courseIdsArray)) {
        $ongoingCourses = (int) \Illuminate\Support\Facades\DB::table('courses')
            ->whereIn('id', $activeCourseIdsArray)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count('id');

        $totalRegisteredUsers = (int) \Illuminate\Support\Facades\DB::table('users')
            ->whereIn('registered_course', $activeCourseIdsArray)
            ->count('id');

        $totalShortlistedUsers = (int) \Illuminate\Support\Facades\DB::table('users')
            ->whereIn('registered_course', $activeCourseIdsArray)
            ->where(function ($query) {
                $query->where('shortlist', 1)->orWhere('shortlist', true);
            })
            ->count('id');

        $admissionsAgg = \Illuminate\Support\Facades\DB::table('user_admission')
            ->whereIn('course_id', $activeCourseIdsArray)
            ->selectRaw(
                '
                COUNT(*) as total_count,
                SUM(CASE WHEN confirmed IS NOT NULL THEN 1 ELSE 0 END) as confirmed_count,
                SUM(CASE WHEN confirmed IS NULL THEN 1 ELSE 0 END) as pending_count
            ',
            )
            ->first();
        $activeAdmittedUsers = \Illuminate\Support\Facades\DB::table('user_admission')
            ->whereIn('course_id', $activeCourseIdsArray)
            ->whereNotNull('confirmed')
            ->select('user_id')
            ->distinct()
            ->count('user_id');

        $admissionsTotal = (int) ($admissionsAgg->total_count ?? 0);
        $admissionsConfirmed = (int) ($admissionsAgg->confirmed_count ?? 0);
        $admissionsPending = (int) ($admissionsAgg->pending_count ?? 0);
        $totalAdmittedUsers = (int) $activeAdmittedUsers;

        $admissionRate = $totalRegisteredUsers > 0 ? round(($totalAdmittedUsers / $totalRegisteredUsers) * 100, 1) : 0;

        $shortlistRate =
            $totalRegisteredUsers > 0 ? round(($totalShortlistedUsers / $totalRegisteredUsers) * 100, 1) : 0;

        $genderCounts = \Illuminate\Support\Facades\DB::table('users as u')
            ->whereIn('u.registered_course', $courseIdsArray)
            ->selectRaw(
                "
                CASE
                    WHEN LOWER(TRIM(COALESCE(u.gender, ''))) IN ('male', 'm') THEN 'Male'
                    WHEN LOWER(TRIM(COALESCE(u.gender, ''))) IN ('female', 'f') THEN 'Female'
                    WHEN TRIM(COALESCE(u.gender, '')) = '' THEN 'Unspecified'
                    ELSE 'Other'
                END as gender_label,
                COUNT(*) as total
            ",
            )
            ->groupBy('gender_label')
            ->pluck('total', 'gender_label');

        $genderValues = $genderLabels->map(fn($label) => (int) ($genderCounts[$label] ?? 0))->values();

        $registeredByCourse = \Illuminate\Support\Facades\DB::table('users')
            ->whereIn('registered_course', $activeCourseIdsArray)
            ->selectRaw('registered_course as course_id, COUNT(id) as total')
            ->groupBy('registered_course')
            ->pluck('total', 'course_id');

        $shortlistedByCourse = \Illuminate\Support\Facades\DB::table('users')
            ->whereIn('registered_course', $activeCourseIdsArray)
            ->where(function ($query) {
                $query->where('shortlist', 1)->orWhere('shortlist', true);
            })
            ->selectRaw('registered_course as course_id, COUNT(id) as total')
            ->groupBy('registered_course')
            ->pluck('total', 'course_id');

        $supportByCourse = \Illuminate\Support\Facades\DB::table('user_admission as ua')
            ->join('users as u', 'u.userId', '=', 'ua.user_id')
            ->whereIn('ua.course_id', $activeCourseIdsArray)
            ->whereNotNull('ua.confirmed')
            ->selectRaw(
                '
                ua.course_id,
                COUNT(DISTINCT CASE WHEN u.support = 1 THEN ua.user_id END) as support_yes
            ',
            )
            ->groupBy('ua.course_id')
            ->get()
            ->keyBy('course_id');

        $coursesWithRegistrations = (int) $registeredByCourse->count();
        $coursesWithoutRegistrations = max($totalCourses - $coursesWithRegistrations, 0);
        $courseRegistrationCoverageRate =
            $totalCourses > 0 ? round(($coursesWithRegistrations / $totalCourses) * 100, 1) : 0;

        $deliveryCounts = \Illuminate\Support\Facades\DB::table('courses as c')
            ->join('programmes as p', 'c.programme_id', '=', 'p.id')
            ->whereIn('c.id', $activeCourseIdsArray)
            ->selectRaw(
                "
                CASE
                    WHEN LOWER(TRIM(COALESCE(p.mode_of_delivery, ''))) IN ('online', 'online for all') THEN 'online'
                    WHEN LOWER(TRIM(COALESCE(p.mode_of_delivery, ''))) IN ('in person', 'in-person', 'in_person') THEN 'in_person'
                    ELSE 'other'
                END as delivery_label,
                COUNT(*) as total
            ",
            )
            ->groupBy('delivery_label')
            ->pluck('total', 'delivery_label');

        $onlineCourses = (int) ($deliveryCounts['online'] ?? 0);
        $inPersonCourses = (int) ($deliveryCounts['in_person'] ?? 0);
        $otherDeliveryCourses = (int) ($deliveryCounts['other'] ?? 0);

        $supportCounts = \Illuminate\Support\Facades\DB::table('user_admission as ua')
            ->join('users as u', 'u.userId', '=', 'ua.user_id')
            ->whereIn('ua.course_id', $activeCourseIdsArray)
            ->whereNotNull('ua.confirmed')
            ->selectRaw(
                '
                COUNT(DISTINCT CASE WHEN u.support = 1 THEN ua.user_id END) as support_yes
            ',
            )
            ->first();

        $supportYes = (int) ($supportCounts->support_yes ?? 0);
        $supportUnknown = max($totalAdmittedUsers - $supportYes, 0);
        $supportNo = $supportUnknown;

        $deliveryLabels = collect(['Online', 'In Person']);
        $deliveryValues = collect([$onlineCourses, $inPersonCourses]);
        if ($otherDeliveryCourses > 0) {
            $deliveryLabels->push('Other/Unspecified');
            $deliveryValues->push($otherDeliveryCourses);
        }

        $supportLabels = collect(['Needs Support', 'No Support']);
        $supportValues = collect([$supportYes, $supportNo]);

        $admittedByCourse = \Illuminate\Support\Facades\DB::table('user_admission')
            ->whereIn('course_id', $activeCourseIdsArray)
            ->whereNotNull('confirmed')
            ->selectRaw('course_id, COUNT(DISTINCT user_id) as total')
            ->groupBy('course_id')
            ->pluck('total', 'course_id');

        $topCourses = \Illuminate\Support\Facades\DB::table('courses as c')
            ->where('c.centre_id', $centreId)
            ->whereIn('c.id', $activeCourseIdsArray)
            ->leftJoin('programmes as p', 'c.programme_id', '=', 'p.id')
            ->select(['c.id', 'c.course_name', 'p.mode_of_delivery'])
            ->get()
            ->map(function ($course) use (
                $registeredByCourse,
                $shortlistedByCourse,
                $admittedByCourse,
                $supportByCourse,
            ) {
                $courseId = (int) $course->id;
                $supportRow = $supportByCourse->get($courseId);
                $admittedCount = (int) ($admittedByCourse[$courseId] ?? 0);
                $supportYesCount = (int) ($supportRow->support_yes ?? 0);
                $supportNoCount = max($admittedCount - $supportYesCount, 0);
                $delivery = strtolower(trim((string) ($course->mode_of_delivery ?? '')));
                $isOnline = in_array($delivery, ['online', 'online for all'], true);

                return (object) [
                    'id' => $courseId,
                    'course_name' => $course->course_name,
                    'mode_of_delivery' => $course->mode_of_delivery,
                    'registered_users_count' => (int) ($registeredByCourse[$courseId] ?? 0),
                    'shortlisted_users_count' => (int) ($shortlistedByCourse[$courseId] ?? 0),
                    'admitted_users_count' => $admittedCount,
                    'support_yes_count' => $supportYesCount,
                    'support_no_count' => $supportNoCount,
                    'is_online_delivery' => $isOnline,
                ];
            })
            ->sortByDesc('registered_users_count')
            ->values();

        $deliveryFilterOptions = $topCourses
            ->map(function ($course) {
                $mode = trim((string) ($course->mode_of_delivery ?? ''));
                return $mode !== '' ? $mode : 'Unspecified';
            })
            ->unique()
            ->sort()
            ->values();
    }
@endphp

@section('content')
    {{-- @parent --}}

    <div>
        <div class="text-muted text-center" style="font-size: 44px; color: black">
            {{ $centre->title ?? 'Centre' }}
        </div>
        <div class="text-muted text-center">
            @if ($constituency?->title)
                {{ $constituency->title }}
            @endif
            @if ($branch?->title)
                - {{ $branch->title }}
            @endif
        </div>
        @if ($districtTitles->isNotEmpty())
            <div class="text-muted text-center small">
                Districts: {{ $districtTitles->implode(', ') }}
            </div>
        @endif
    </div>

    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Total Registered Users</div>
                        <i class="la la-users text-primary"></i>
                    </div>
                    <div class="metric-value">{{ number_format($totalRegisteredUsers) }}</div>
                    <div class="text-muted small">Users mapped by active course batches in this centre.</div>
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
                    <div class="text-muted small">Active batch shortlist rate: {{ $shortlistRate }}%</div>
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
                    <div class="text-muted small">Active batch admission rate: {{ $admissionRate }}%</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Total Courses</div>
                        <i class="la la-book text-warning"></i>
                    </div>
                    <div class="metric-value">{{ number_format($totalCourses) }}</div>
                    <!-- <div class="text-muted small">Ongoing now: {{ number_format($ongoingCourses) }}</div> -->
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Online Delivery Courses</div>
                        <i class="la la-wifi text-primary"></i>
                    </div>
                    <div class="metric-value">{{ number_format($onlineCourses) }}</div>
                    <div class="text-muted small">Courses in this centre with online programmes.</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">In-Person Delivery Courses</div>
                        <i class="la la-map-marker text-success"></i>
                    </div>
                    <div class="metric-value">{{ number_format($inPersonCourses) }}</div>
                    <div class="text-muted small">Courses in this centre with in-person programmes.</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Users Needing Support For Online Course</div>
                        <i class="la la-hands-helping text-warning"></i>
                    </div>
                    <div class="metric-value">{{ number_format($supportYes) }}</div>
                    <div class="text-muted small">Registered users marked as needing support.</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Users Not Needing Support For Online Course</div>
                        <i class="la la-user text-secondary"></i>
                    </div>
                    <div class="metric-value">{{ number_format($supportUnknown) }}</div>
                    <div class="text-muted small">Derived as admitted users not needing support.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-pie-chart"></i> Gender Distribution</strong>
                </div>
                <div class="card-body">
                    <div class="chart-wrap-sm">
                        <canvas id="genderPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div> -->

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-bar-chart"></i> Admitted Students Session Distribution</strong>
                </div>
                <div class="card-body">
                    <div class="chart-wrap-session">
                        <canvas id="admittedStudentsSessionBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-pie-chart"></i> Admissions Distribution</strong>
                </div>
                <div class="card-body">
                    <div class="chart-wrap-sm">
                        <canvas id="admissionsDoughnutChart"></canvas>
                    </div>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <span class="badge bg-info text-dark">Total: {{ number_format($admissionsTotal) }}</span>
                        <span class="badge bg-success text-dark">Confirmed:
                            {{ number_format($admissionsConfirmed) }}</span>
                        <span class="badge bg-warning text-dark">Pending: {{ number_format($admissionsPending) }}</span>
                    </div>
                    <div class="text-muted small mt-2">Active course batches only.</div>
                </div>
            </div>
        </div>
    </div>



    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-users"></i> Admitted Students by Session</strong>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-lg-3 col-md-6">
                            <label for="centreCourseFilter" class="form-label text-muted small mb-1">Filter Course</label>
                            <select id="centreCourseFilter" class="form-select form-select-sm">
                                <option value="">All</option>
                                @foreach ($centreCourseFilterOptions as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="centreSessionFilter" class="form-label text-muted small mb-1">Filter Session</label>
                            <select id="centreSessionFilter" class="form-select form-select-sm">
                                <option value="">All</option>
                                @foreach ($centreSessionFilterOptions as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="centreProgrammeBatchDate" class="form-label text-muted small mb-1">Date Filter</label>
                            <input id="centreProgrammeBatchDate" type="date" class="form-control form-control-sm"
                                @if ($admissionFilterMinDate) min="{{ $admissionFilterMinDate }}" @endif
                                @if ($admissionFilterMaxDate) max="{{ $admissionFilterMaxDate }}" @endif>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <button id="clearCentreAdmittedFilters" type="button"
                                class="btn btn-sm btn-outline-secondary w-100">Clear Filters</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="dtCentreAdmittedStudents" class="table table-striped table-hover table-sm w-100">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Email</th>
                                    <th>Course</th>
                                    <th>Session</th>
                                    <th>Batch Start Date</th>
                                    <th>Batch End Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($centreAdmittedStudents as $student)
                                    <tr
                                        data-programme-batch-start="{{ $student->programme_batch_start_date ?? '' }}"
                                        data-programme-batch-end="{{ $student->programme_batch_end_date ?? '' }}">
                                        <td>{{ $student->user_name ?? 'N/A' }}</td>
                                        <td>{{ $student->user_email ?? 'N/A' }}</td>
                                        <td>{{ $student->course_name ?? 'N/A' }}</td>
                                        <td data-search="{{ $student->session_filter_label ?? $student->session_label ?? 'N/A' }}">
                                            <div>
                                                {{ !empty($student->session_filter_label)
                                                    ? $student->session_filter_label
                                                    : 'N/A' }}
                                            </div>
                                            @if (!empty($student->session_name))
                                                <div class="text-muted small">{{ $student->session_name }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $batchStartDate = null;
                                                try {
                                                    $batchStartDate = !empty($student->programme_batch_start_date)
                                                        ? \Carbon\Carbon::parse($student->programme_batch_start_date)
                                                        : null;
                                                } catch (\Throwable $e) {
                                                    $batchStartDate = null;
                                                }
                                            @endphp
                                            {{ $batchStartDate ? $batchStartDate->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td>
                                            @php
                                                $batchEndDate = null;
                                                try {
                                                    $batchEndDate = !empty($student->programme_batch_end_date)
                                                        ? \Carbon\Carbon::parse($student->programme_batch_end_date)
                                                        : null;
                                                } catch (\Throwable $e) {
                                                    $batchEndDate = null;
                                                }
                                            @endphp
                                            {{ $batchEndDate ? $batchEndDate->format('M d, Y') : 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No admitted students with sessions found for this centre.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($centreAdmittedStudents->isEmpty())
                        <div class="text-center text-muted py-2">
                            Filter by course, session, or programme-batch date once admissions are assigned.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <strong><i class="la la-calendar"></i> Centre Sessions</strong>
                    <span class="text-muted small">Total: {{ number_format($centreSessions->count()) }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtCentreSessions" class="table table-striped table-hover table-sm w-100">
                            <thead>
                                <tr>
                                    <th>Session</th>
                                    <th>Limit</th>
                                    <th>Course Time</th>
                                    <th>Slots Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($centreSessions as $session)
                                    @php
                                        $confirmedCount = (int) ($centreSessionConfirmed[$session->id] ?? 0);
                                        $sessionCourseType = strtolower(trim((string) ($session->course_type ?? '')));
                                        if ($sessionCourseType === 'short') {
                                            $limit = $centreShortSessionLimit;
                                        } elseif ($sessionCourseType === 'long') {
                                            $limit = $centreLongSessionLimit;
                                        } else {
                                            $limit = $centreSeatCountLimit;
                                        }
                                        $slotsLeft = $limit !== null ? max(0, $limit - $confirmedCount) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $session->session_type ?? $session->master_name ?? 'Session #' . $session->id }}
                                            @if (!empty($session->master_name) && $session->master_name !== ($session->session_type ?? ''))
                                                <div class="text-muted small">{{ $session->master_name }}</div>
                                            @endif
                                            @if (!empty($session->course_type))
                                                <!-- <div class="text-muted small text-uppercase">{{ $session->course_type }}</div> -->
                                            @endif
                                        </td>
                                        <td>
                                            @if ($limit === null)
                                                -
                                            @else
                                                {{ number_format($limit) }}
                                            @endif
                                        </td>
                                        <td>{{ $session->time ?? '-' }}</td>
                                        <td>
                                            @if ($slotsLeft === null)
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

                    @if ($centreSessions->isEmpty())
                        <div class="text-center text-muted py-3">No active master sessions configured.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <strong><i class="la la-book"></i> Courses by Registrations</strong>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Mode of Delivery</span>
                        <select id="deliveryFilter" class="form-select form-select-sm" style="min-width: 180px;">
                            <option value="">All</option>
                            @foreach ($deliveryFilterOptions ?? [] as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                            @if (($deliveryFilterOptions ?? collect())->isEmpty())
                                <option value="Unspecified">Unspecified</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtTopCourses" class="table table-sm table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Registered Users</th>
                                    <th>Shortlisted Users</th>
                                    <th>Admitted Users</th>
                                    <th>Mode of Delivery</th>
                                    <th>Support (Yes/No)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCourses as $course)
                                    <tr>
                                        <td>
                                            @if (!empty($course->id) && !$isCentreOfficer)
                                                <a
                                                    href="{{ backpack_url('course-batch/' . $course->id . '/show') }}">{{ $course->course_name ?? 'Course #' . $course->id }}</a>
                                            @else
                                                {{ $course->course_name ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ number_format((int) ($course->registered_users_count ?? 0)) }}</td>
                                        <td>{{ number_format((int) ($course->shortlisted_users_count ?? 0)) }}</td>
                                        <td>{{ number_format((int) ($course->admitted_users_count ?? 0)) }}</td>
                                        <td>{{ $course->mode_of_delivery ?? 'Unspecified' }}</td>
                                        <td>
                                            {{ number_format((int) ($course->support_yes_count ?? 0)) }}
                                            /
                                            {{ number_format((int) ($course->support_no_count ?? 0)) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No course data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
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
            height: 240px;
        }

        .chart-wrap-session {
            position: relative;
            height: 280px;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-top: 0.5rem;
            margin-bottom: 1.25rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            margin-left: .5rem;
        }

        .dataTables_wrapper .dataTables_length select {
            margin: 0 .25rem;
        }

        .dataTables_wrapper .dt-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-bottom: .75rem;
        }

        .dataTables_wrapper .dt-buttons .btn {
            margin-right: 0;
        }
    </style>
@endpush

@push('after_scripts')
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/chart.js/Chart.min.js') }}"></script>
    <script>
        (function() {
            "use strict";

            function safeInitDataTable(selector, options) {
                const $ = window.jQuery;
                if (!$ || !$.fn || !$.fn.DataTable) return;

                const $el = $(selector);
                if (!$el.length) return;
                if ($.fn.DataTable.isDataTable($el[0])) {
                    return $el.DataTable();
                }

                return $el.DataTable(Object.assign({
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    responsive: true,
                    deferRender: true,
                    ordering: false,
                    language: {
                        search: "",
                        searchPlaceholder: "Search..."
                    },
                }, options || {}));
            }

            function normalizeIsoDate(value) {
                const date = (value || '').trim();
                return /^\d{4}-\d{2}-\d{2}$/.test(date) ? date : '';
            }

            document.addEventListener('DOMContentLoaded', function() {
                const topCoursesTable = safeInitDataTable('#dtTopCourses');
                const centreExportTitle = @json($centre->title ?? 'Centre');
                const deliveryFilter = document.getElementById('deliveryFilter');
                if (deliveryFilter && topCoursesTable) {
                    deliveryFilter.addEventListener('change', function() {
                        const value = (this.value || '').trim();
                        if (!value) {
                            topCoursesTable.column(4).search('').draw();
                            return;
                        }
                        const escaped = value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        topCoursesTable.column(4).search('^' + escaped + '$', true, false).draw();
                    });
                }

                safeInitDataTable('#dtCentreSessions', {
                    paging: false,
                    searching: true,
                    info: false
                });

                const centreAdmittedTable = safeInitDataTable('#dtCentreAdmittedStudents', {
                    ordering: false,
                    pageLength: 10,
                    dom: "<'d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2'Bf>rt<'d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2'lip>",
                    buttons: [{
                            extend: 'csvHtml5',
                            text: 'Export CSV',
                            className: 'btn btn-sm btn-outline-secondary',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5],
                                modifier: {
                                    search: 'applied'
                                }
                            }
                        },
                        {
                            extend: 'excelHtml5',
                            text: 'Export Excel',
                            className: 'btn btn-sm btn-outline-success',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5],
                                modifier: {
                                    search: 'applied'
                                }
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            text: 'Export PDF',
                            className: 'btn btn-sm btn-outline-danger',
                            title: centreExportTitle,
                            orientation: 'landscape',
                            pageSize: 'A4',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5],
                                modifier: {
                                    search: 'applied'
                                }
                            }
                        }
                    ]
                });

                const centreCourseFilter = document.getElementById('centreCourseFilter');
                if (centreCourseFilter && centreAdmittedTable) {
                    centreCourseFilter.addEventListener('change', function() {
                        const value = (this.value || '').trim();
                        if (!value) {
                            centreAdmittedTable.column(2).search('').draw();
                            return;
                        }
                        const escaped = value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        centreAdmittedTable.column(2).search('^' + escaped + '$', true, false).draw();
                    });
                }

                const centreSessionFilter = document.getElementById('centreSessionFilter');
                if (centreSessionFilter && centreAdmittedTable) {
                    centreSessionFilter.addEventListener('change', function() {
                        const value = (this.value || '').trim();
                        if (!value) {
                            centreAdmittedTable.column(3).search('').draw();
                            return;
                        }
                        const escaped = value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        centreAdmittedTable.column(3).search('^' + escaped + '$', true, false).draw();
                    });
                }

                const centreProgrammeBatchDate = document.getElementById('centreProgrammeBatchDate');
                const clearCentreAdmittedFilters = document.getElementById('clearCentreAdmittedFilters');

                const $ = window.jQuery;
                if ($ && $.fn && $.fn.dataTable && centreAdmittedTable) {
                    const admittedTableNode = centreAdmittedTable.table().node();
                    const rangeFilterFn = function(settings, data, dataIndex) {
                        if (settings.nTable !== admittedTableNode) {
                            return true;
                        }

                        const selectedDate = normalizeIsoDate(centreProgrammeBatchDate?.value || '');
                        if (!selectedDate) {
                            return true;
                        }

                        const rowNode = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
                        if (!rowNode) {
                            return true;
                        }

                        const batchStart = normalizeIsoDate(rowNode.getAttribute('data-programme-batch-start') || '');
                        const batchEnd = normalizeIsoDate(rowNode.getAttribute('data-programme-batch-end') || '');
                        if (!batchStart || !batchEnd) {
                            return false;
                        }

                        return batchStart <= selectedDate && batchEnd >= selectedDate;
                    };

                    $.fn.dataTable.ext.search.push(rangeFilterFn);
                }

                if (centreProgrammeBatchDate && centreAdmittedTable) {
                    centreProgrammeBatchDate.addEventListener('change', function() {
                        centreAdmittedTable.draw();
                    });
                }

                if (clearCentreAdmittedFilters && centreAdmittedTable) {
                    clearCentreAdmittedFilters.addEventListener('click', function() {
                        if (centreCourseFilter) {
                            centreCourseFilter.value = '';
                        }
                        if (centreSessionFilter) {
                            centreSessionFilter.value = '';
                        }
                        if (centreProgrammeBatchDate) {
                            centreProgrammeBatchDate.value = '';
                        }

                        centreAdmittedTable.column(2).search('');
                        centreAdmittedTable.column(3).search('');
                        centreAdmittedTable.draw();
                    });
                }
                if (typeof Chart !== 'function') return;

                const genderLabels = @json($genderLabels->values());
                const genderValues = @json($genderValues->map(fn($v) => (int) $v)->values());
                const centreSessionChartLabels = @json($centreSessionChartLabels->values());
                const centreSessionChartValues = @json($centreSessionChartValues->map(fn($v) => (int) $v)->values());
                const centreSessionChartColors = centreSessionChartLabels.map(function(_, index) {
                    const total = Math.max(centreSessionChartLabels.length, 1);
                    const hue = Math.round((index * 360) / total);

                    return {
                        background: 'hsla(' + hue + ', 68%, 55%, 0.82)',
                        border: 'hsl(' + hue + ', 68%, 42%)'
                    };
                });

                const funnelValues = [
                    {{ (int) $totalRegisteredUsers }},
                    {{ (int) $totalShortlistedUsers }},
                    {{ (int) $totalAdmittedUsers }}
                ];

                const deliveryLabels = @json($deliveryLabels->values());
                const deliveryValues = @json($deliveryValues->values());
                const supportLabels = @json($supportLabels->values());
                const supportValues = @json($supportValues->values());

                const genderCtx = document.getElementById('genderPieChart');
                if (genderCtx) {
                    new Chart(genderCtx, {
                        type: 'pie',
                        data: {
                            labels: genderLabels,
                            datasets: [{
                                data: genderValues,
                                backgroundColor: [
                                    'rgba(13, 110, 253, 0.85)',
                                    'rgba(220, 53, 69, 0.85)',
                                    'rgba(108, 117, 125, 0.85)',
                                    'rgba(255, 193, 7, 0.85)',
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: {
                                position: 'bottom'
                            }
                        }
                    });
                }

                const admittedStudentsSessionCtx = document.getElementById('admittedStudentsSessionBarChart');
                if (admittedStudentsSessionCtx) {
                    new Chart(admittedStudentsSessionCtx, {
                        type: 'bar',
                        data: {
                            labels: centreSessionChartLabels,
                            datasets: [{
                                label: 'Admitted Students',
                                data: centreSessionChartValues,
                                backgroundColor: centreSessionChartColors.map(function(color) {
                                    return color.background;
                                }),
                                borderColor: centreSessionChartColors.map(function(color) {
                                    return color.border;
                                }),
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
                                xAxes: [{
                                    ticks: {
                                        autoSkip: false,
                                        maxRotation: 35,
                                        minRotation: 0
                                    }
                                }],
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        precision: 0
                                    }
                                }]
                            }
                        }
                    });
                }

                const admissionsCtx = document.getElementById('admissionsDoughnutChart');
                if (admissionsCtx) {
                    const centerTextPlugin = {
                        beforeDraw: function(chart) {
                            const opts = chart?.config?.options?.centerText;
                            if (!opts) return;

                            const ctx = chart.chart.ctx;
                            const width = chart.chart.width;
                            const height = chart.chart.height;

                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';

                            ctx.fillStyle = '#6c757d';
                            ctx.font =
                                '500 12px system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';
                            ctx.fillText(opts.line1 || '', width / 2, height / 2 - 12);

                            ctx.fillStyle = '#111';
                            ctx.font =
                                '600 20px system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';
                            ctx.fillText(opts.line2 || '', width / 2, height / 2 + 6);
                            ctx.restore();
                        }
                    };

                    new Chart(admissionsCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Confirmed', 'Pending'],
                            datasets: [{
                                data: [{{ (int) $admissionsConfirmed }},
                                    {{ (int) $admissionsPending }}
                                ],
                                backgroundColor: ['rgba(25, 135, 84, 0.85)',
                                    'rgba(255, 193, 7, 0.85)'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: {
                                position: 'bottom'
                            },
                            centerText: {
                                line1: 'Total',
                                line2: ({{ (int) $admissionsTotal }}).toLocaleString()
                            }
                        },
                        plugins: [centerTextPlugin]
                    });
                }

                const funnelCtx = document.getElementById('funnelBarChart');
                if (funnelCtx) {
                    new Chart(funnelCtx, {
                        type: 'bar',
                        data: {
                            labels: ['Registered', 'Shortlisted', 'Admitted'],
                            datasets: [{
                                label: 'Users',
                                data: funnelValues,
                                backgroundColor: [
                                    'rgba(13, 110, 253, 0.85)',
                                    'rgba(13, 202, 240, 0.85)',
                                    'rgba(25, 135, 84, 0.85)',
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
                                        precision: 0
                                    }
                                }]
                            }
                        }
                    });
                }

                const deliveryCtx = document.getElementById('deliveryPieChart');
                if (deliveryCtx) {
                    new Chart(deliveryCtx, {
                        type: 'pie',
                        data: {
                            labels: deliveryLabels,
                            datasets: [{
                                data: deliveryValues,
                                backgroundColor: [
                                    'rgba(13, 110, 253, 0.85)',
                                    'rgba(25, 135, 84, 0.85)',
                                    'rgba(108, 117, 125, 0.85)',
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: {
                                position: 'bottom'
                            }
                        }
                    });
                }

                const supportCtx = document.getElementById('supportPieChart');
                if (supportCtx) {
                    new Chart(supportCtx, {
                        type: 'pie',
                        data: {
                            labels: supportLabels,
                            datasets: [{
                                data: supportValues,
                                backgroundColor: [
                                    'rgba(255, 193, 7, 0.85)',
                                    'rgba(108, 117, 125, 0.85)',
                                    'rgba(13, 110, 253, 0.85)',
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: {
                                position: 'bottom'
                            }
                        }
                    });
                }
            });
        })();
    </script>
@endpush
