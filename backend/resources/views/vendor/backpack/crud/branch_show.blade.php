@extends('crud::show')

@php
    /** @var \App\Models\Branch $entry */
    $branch = $entry;
    $branchId = (int) $branch->getKey();

    $today = now()->toDateString();

    $centreIdsArray = \Illuminate\Support\Facades\DB::table('centres')
        ->where('branch_id', $branchId)
        ->pluck('id')
        ->map(fn($id) => (int) $id)
        ->all();

    $totalCentres = count($centreIdsArray);
    $totalConstituencies = (int) \Illuminate\Support\Facades\DB::table('constituencies')
        ->where('branch_id', $branchId)
        ->count('id');
    $totalDistricts = (int) \Illuminate\Support\Facades\DB::table('districts')
        ->where('branch_id', $branchId)
        ->count('id');

    $constituencies = \Illuminate\Support\Facades\DB::table('constituencies as co')
        ->leftJoin('centres as ce', 'ce.constituency_id', '=', 'co.id')
        ->where('co.branch_id', $branchId)
        ->select('co.id', 'co.title')
        ->selectRaw('COUNT(ce.id) as centres_count')
        ->groupBy('co.id', 'co.title')
        ->orderBy('co.title')
        ->get();

    $districts = \Illuminate\Support\Facades\DB::table('districts as d')
        ->leftJoin('district_centre as dc', 'dc.district_id', '=', 'd.id')
        ->where('d.branch_id', $branchId)
        ->select('d.id', 'd.title')
        ->selectRaw('COUNT(dc.centre_id) as centres_count')
        ->groupBy('d.id', 'd.title')
        ->orderBy('d.title')
        ->get();

    $courseIdsArray = [];
    $totalCourses = 0;
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

    $genderLabels = collect(['Male', 'Female']);
    $genderValues = collect([0, 0]);
    $ageLabels = collect();
    $ageValues = collect();

    $topCentres = collect();
    $topCourses = collect();

    if (!empty($centreIdsArray)) {
        $courseIdsArray = \Illuminate\Support\Facades\DB::table('courses')
            ->whereIn('centre_id', $centreIdsArray)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $totalCourses = count($courseIdsArray);

        if (!empty($courseIdsArray)) {
            $ongoingCourses = (int) \Illuminate\Support\Facades\DB::table('courses')
                ->whereIn('id', $courseIdsArray)
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->count('id');

            $totalRegisteredUsers = (int) \Illuminate\Support\Facades\DB::table('users')
                ->whereIn('registered_course', $courseIdsArray)
                ->count('id');

            $totalShortlistedUsers = (int) \Illuminate\Support\Facades\DB::table('users')
                ->whereIn('registered_course', $courseIdsArray)
                ->where(function ($query) {
                    $query->where('shortlist', 1)->orWhere('shortlist', true);
                })
                ->count('id');

            $admissionsAgg = \Illuminate\Support\Facades\DB::table('user_admission')
                ->whereIn('course_id', $courseIdsArray)
                ->selectRaw(
                    '
                    COUNT(*) as total_count,
                    SUM(CASE WHEN confirmed IS NOT NULL THEN 1 ELSE 0 END) as confirmed_count,
                    SUM(CASE WHEN confirmed IS NULL THEN 1 ELSE 0 END) as pending_count,
                    COUNT(DISTINCT CASE WHEN confirmed IS NOT NULL THEN user_id END) as admitted_students_count
                ',
                )
                ->first();

            $admissionsTotal = (int) ($admissionsAgg->total_count ?? 0);
            $admissionsConfirmed = (int) ($admissionsAgg->confirmed_count ?? 0);
            $admissionsPending = (int) ($admissionsAgg->pending_count ?? 0);
            $totalAdmittedUsers = (int) ($admissionsAgg->admitted_students_count ?? 0);

            $admissionRate =
                $totalRegisteredUsers > 0 ? round(($totalAdmittedUsers / $totalRegisteredUsers) * 100, 1) : 0;

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

            $ageCounts = \Illuminate\Support\Facades\DB::table('users as u')
                ->whereIn('u.registered_course', $courseIdsArray)
                ->selectRaw(
                    "
                    CASE
                        WHEN u.age IS NULL OR u.age = '' THEN 'Unknown'
                        WHEN u.age LIKE '%-%' OR u.age LIKE '%–%' OR u.age LIKE '%—%' THEN u.age
                        WHEN u.age LIKE '%+%' THEN u.age
                        WHEN u.age REGEXP '^[0-9]+$' THEN
                            CONCAT(
                                FLOOR(CAST(u.age AS UNSIGNED) / 10) * 10,
                                '-',
                                FLOOR(CAST(u.age AS UNSIGNED) / 10) * 10 + 9
                            )
                        ELSE 'Unknown'
                    END AS age_range,
                    COUNT(*) AS total,
                    CASE
                        WHEN u.age IS NULL OR u.age = '' THEN 9999
                        WHEN u.age LIKE '%-%' OR u.age LIKE '%–%' OR u.age LIKE '%—%' THEN
                            CAST(SUBSTRING_INDEX(u.age, '-', 1) AS UNSIGNED)
                        WHEN u.age LIKE '%+%' THEN
                            CAST(SUBSTRING_INDEX(u.age, '+', 1) AS UNSIGNED)
                        WHEN u.age REGEXP '^[0-9]+$' THEN
                            FLOOR(CAST(u.age AS UNSIGNED) / 10)
                        ELSE 9999
                    END AS bucket_order
                ",
                )
                ->groupBy('age_range', 'bucket_order')
                ->orderBy('bucket_order')
                ->get();

            $ageLabels = $ageCounts->pluck('age_range')->values();
            $ageValues = $ageCounts->pluck('total')->map(fn($v) => (int) $v)->values();

            $districtLinksByCentre = \Illuminate\Support\Facades\DB::table('district_centre as dc')
                ->join('districts as d', 'd.id', '=', 'dc.district_id')
                ->whereIn('dc.centre_id', $centreIdsArray)
                ->select('dc.centre_id', 'd.id', 'd.title')
                ->orderBy('d.title')
                ->get()
                ->groupBy('centre_id')
                ->map(function ($rows) {
                    return $rows
                        ->map(function ($row) {
                            $districtId = (int) $row->id;
                            $url = backpack_url('district/' . $districtId . '/show');
                            return '<a href="' . $url . '">' . e($row->title) . '</a>';
                        })
                        ->implode(', ');
                });

            $coursesByCentre = \Illuminate\Support\Facades\DB::table('courses')
                ->whereIn('centre_id', $centreIdsArray)
                ->selectRaw('centre_id, COUNT(*) as total')
                ->groupBy('centre_id')
                ->pluck('total', 'centre_id');

            $registeredByCentre = \Illuminate\Support\Facades\DB::table('users as u')
                ->join('courses as c', 'c.id', '=', 'u.registered_course')
                ->whereIn('c.centre_id', $centreIdsArray)
                ->selectRaw('c.centre_id as centre_id, COUNT(u.id) as total')
                ->groupBy('c.centre_id')
                ->pluck('total', 'centre_id');

            $admittedByCentre = \Illuminate\Support\Facades\DB::table('user_admission as ua')
                ->join('courses as c', 'c.id', '=', 'ua.course_id')
                ->whereIn('c.centre_id', $centreIdsArray)
                ->whereNotNull('ua.confirmed')
                ->selectRaw('c.centre_id as centre_id, COUNT(DISTINCT ua.user_id) as total')
                ->groupBy('c.centre_id')
                ->pluck('total', 'centre_id');

            $registeredByCourse = \Illuminate\Support\Facades\DB::table('users')
                ->whereIn('registered_course', $courseIdsArray)
                ->selectRaw('registered_course as course_id, COUNT(id) as total')
                ->groupBy('registered_course')
                ->pluck('total', 'course_id');

            $coursesWithRegistrations = (int) $registeredByCourse->count();
            $coursesWithoutRegistrations = max($totalCourses - $coursesWithRegistrations, 0);
            $courseRegistrationCoverageRate =
                $totalCourses > 0 ? round(($coursesWithRegistrations / $totalCourses) * 100, 1) : 0;

            $admittedByCourse = \Illuminate\Support\Facades\DB::table('user_admission')
                ->whereIn('course_id', $courseIdsArray)
                ->whereNotNull('confirmed')
                ->selectRaw('course_id, COUNT(DISTINCT user_id) as total')
                ->groupBy('course_id')
                ->pluck('total', 'course_id');

            $topCentres = \Illuminate\Support\Facades\DB::table('centres')
                ->whereIn('id', $centreIdsArray)
                ->select(['id', 'title'])
                ->get()
                ->map(function ($centre) use (
                    $coursesByCentre,
                    $registeredByCentre,
                    $admittedByCentre,
                    $districtLinksByCentre,
                ) {
                    $centreId = (int) $centre->id;

                    return (object) [
                        'id' => $centreId,
                        'title' => $centre->title,
                        'district_links' => $districtLinksByCentre[$centreId] ?? null,
                        'courses_count' => (int) ($coursesByCentre[$centreId] ?? 0),
                        'registered_users_count' => (int) ($registeredByCentre[$centreId] ?? 0),
                        'admitted_users_count' => (int) ($admittedByCentre[$centreId] ?? 0),
                    ];
                })
                ->sortByDesc('registered_users_count')
                ->values();

            $topCourses = \Illuminate\Support\Facades\DB::table('courses as c')
                ->join('centres as ce', 'ce.id', '=', 'c.centre_id')
                ->whereIn('c.id', $courseIdsArray)
                ->select(['c.id', 'c.course_name', 'c.centre_id', 'ce.title as centre_title'])
                ->get()
                ->map(function ($course) use ($registeredByCourse, $admittedByCourse, $districtLinksByCentre) {
                    $courseId = (int) $course->id;
                    $centreId = (int) ($course->centre_id ?? 0);

                    return (object) [
                        'id' => $courseId,
                        'course_name' => $course->course_name,
                        'centre_title' => $course->centre_title,
                        'district_links' => $districtLinksByCentre[$centreId] ?? null,
                        'registered_users_count' => (int) ($registeredByCourse[$courseId] ?? 0),
                        'admitted_users_count' => (int) ($admittedByCourse[$courseId] ?? 0),
                    ];
                })
                ->sortByDesc('registered_users_count')
                ->values();
        }
    }
@endphp

@section('content')
    {{-- @parent --}}

    <div>
        <div class="text-muted text-center" style="font-size: 50px; color: black">
            {{ $branch->title ?? 'Region' }}
        </div>
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
                    <div class="text-muted small">Users mapped by registered course in this region.</div>
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
                        <div class="text-muted">Total Courses</div>
                        <i class="la la-book text-warning"></i>
                    </div>
                    <div class="metric-value">{{ number_format($totalCourses) }}</div>
                    <div class="text-muted small">Ongoing now: {{ number_format($ongoingCourses) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Total Centres</div>
                        <i class="la la-building text-secondary"></i>
                    </div>
                    <div class="metric-value">{{ number_format($totalCentres) }}</div>
                    <div class="text-muted small">Centres assigned to this region.</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Total Constituencies</div>
                        <i class="la la-map text-secondary"></i>
                    </div>
                    <div class="metric-value">{{ number_format($totalConstituencies) }}</div>
                    <div class="text-muted small">Constituencies in this region.</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Total Districts</div>
                        <i class="la la-map-marker text-secondary"></i>
                    </div>
                    <div class="metric-value">{{ number_format($totalDistricts) }}</div>
                    <div class="text-muted small">Districts in this region.</div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">Courses Without Registrations</div>
                        <i class="la la-exclamation-circle text-dark"></i>
                    </div>
                    <div class="metric-value">{{ number_format($coursesWithoutRegistrations) }}</div>
                    <div class="text-muted small">Coverage: {{ $courseRegistrationCoverageRate }}% of courses have at least
                        one registration.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
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
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-bar-chart"></i> Age Group Distribution</strong>
                </div>
                <div class="card-body">
                    <div class="chart-wrap-sm">
                        <canvas id="ageGroupBarChart"></canvas>
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
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-signal"></i> Registration Funnel</strong>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="funnelBarChart"></canvas>
                    </div>
                    <div class="text-muted small mt-2">
                        Suggested KPI: track drop-off from Registered to Shortlisted to Admitted.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-building"></i> Top Centres by Registrations</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtTopCentres" class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Centre</th>
                                    <th>District</th>
                                    <th>Courses</th>
                                    <th>Registered</th>
                                    <th>Admitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCentres as $centre)
                                    <tr>
                                        <td>
                                            @if (!empty($centre->id))
                                                <a
                                                    href="{{ backpack_url('centre/' . $centre->id . '/show') }}">{{ $centre->title }}</a>
                                            @else
                                                {{ $centre->title ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (!empty($centre->district_links))
                                                {!! $centre->district_links !!}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ number_format((int) ($centre->courses_count ?? 0)) }}</td>
                                        <td>{{ number_format((int) ($centre->registered_users_count ?? 0)) }}</td>
                                        <td>{{ number_format((int) ($centre->admitted_users_count ?? 0)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No centre data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-book"></i> Courses by Registrations</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtTopCourses" class="table table-sm table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Centre</th>
                                    <th>District</th>
                                    <th>Registered Users</th>
                                    <th>Admitted Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCourses as $course)
                                    <tr>
                                        <td>
                                            @if (!empty($course->id))
                                                <a
                                                    href="{{ backpack_url('course-batch/' . $course->id . '/show') }}">{{ $course->course_name ?? 'Course #' . $course->id }}</a>
                                            @else
                                                {{ $course->course_name ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ $course->centre_title ?? 'N/A' }}</td>
                                        <td>
                                            @if (!empty($course->district_links))
                                                {!! $course->district_links !!}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ number_format((int) ($course->registered_users_count ?? 0)) }}</td>
                                        <td>{{ number_format((int) ($course->admitted_users_count ?? 0)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No course data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-map"></i> Constituencies in the Region</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtConstituencies" class="table table-sm table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Constituency</th>
                                    <th>Centres</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($constituencies as $constituency)
                                    <tr>
                                        <td>
                                            @if (!empty($constituency->id))
                                                <a
                                                    href="{{ backpack_url('constituency/' . $constituency->id . '/show') }}">{{ $constituency->title ?? 'Constituency #' . $constituency->id }}</a>
                                            @else
                                                {{ $constituency->title ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ number_format((int) ($constituency->centres_count ?? 0)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No constituency data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong><i class="la la-map-marker"></i> Districts in the Region</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dtDistricts" class="table table-sm table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>District</th>
                                    <th>Centres</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($districts as $district)
                                    <tr>
                                        <td>
                                            @if (!empty($district->id))
                                                <a
                                                    href="{{ backpack_url('district/' . $district->id . '/show') }}">{{ $district->title ?? 'District #' . $district->id }}</a>
                                            @else
                                                {{ $district->title ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ number_format((int) ($district->centres_count ?? 0)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No district data found.</td>
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

        .dataTables_wrapper .dataTables_filter input {
            margin-left: .5rem;
        }

        .dataTables_wrapper .dataTables_length select {
            margin: 0 .25rem;
        }
    </style>
@endpush

@push('after_scripts')
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/chart.js/Chart.min.js') }}"></script>
    <script>
        (function() {
            "use strict";

            function safeInitDataTable(selector, options) {
                const $ = window.jQuery;
                if (!$ || !$.fn || !$.fn.DataTable) return;

                const $el = $(selector);
                if (!$el.length) return;
                if ($.fn.DataTable.isDataTable($el[0])) return;

                $el.DataTable(Object.assign({
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

            document.addEventListener('DOMContentLoaded', function() {
                safeInitDataTable('#dtTopCentres');
                safeInitDataTable('#dtTopCourses');
                safeInitDataTable('#dtConstituencies');
                safeInitDataTable('#dtDistricts');
                if (typeof Chart !== 'function') return;

                const genderLabels = @json($genderLabels->values());
                const genderValues = @json($genderValues->map(fn($v) => (int) $v)->values());
                const ageLabels = @json($ageLabels->values());
                const ageValues = @json($ageValues->map(fn($v) => (int) $v)->values());

                const funnelValues = [
                    {{ (int) $totalRegisteredUsers }},
                    {{ (int) $totalShortlistedUsers }},
                    {{ (int) $totalAdmittedUsers }}
                ];

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

                const ageCtx = document.getElementById('ageGroupBarChart');
                if (ageCtx) {
                    new Chart(ageCtx, {
                        type: 'bar',
                        data: {
                            labels: ageLabels,
                            datasets: [{
                                label: 'Users',
                                data: ageValues,
                                backgroundColor: 'rgba(25, 135, 84, 0.8)',
                                borderColor: 'rgba(25, 135, 84, 1)',
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
            });
        })();
    </script>
@endpush
