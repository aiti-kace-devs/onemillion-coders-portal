@extends('layouts.app')
@section('title', 'View Attendance')
@section('content')
    {{-- <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" /> --}}
    <style @nonce>
        .multi-select-container {
            display: inline-block;
            position: relative;
        }

        .multi-select-menu {
            position: absolute;
            left: 0;
            top: 0.8em;
            z-index: 1;
            float: left;
            min-width: 100%;
            background: #fff;
            margin: 1em 0;
            border: 1px solid #aaa;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            display: none;
        }

        .multi-select-menuitem {
            display: block;
            font-size: 0.875em;
            padding: 0.6em 1em 0.6em 30px;
            white-space: nowrap;
        }

        .multi-select-legend {
            font-size: 0.875em;
            font-weight: bold;
            padding-left: 10px;
        }

        .multi-select-legend+.multi-select-menuitem {
            padding-top: 0.25rem;
        }

        .multi-select-menuitem+.multi-select-menuitem {
            padding-top: 0;
        }

        .multi-select-presets {
            border-bottom: 1px solid #ddd;
        }

        .multi-select-menuitem input {
            position: absolute;
            margin-top: 0.25em;
            margin-left: -20px;
        }

        .multi-select-button {
            display: inline-block;
            font-size: 0.875em;
            padding: 0.2em 0.6em;
            max-width: 16em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: -0.5em;
            background-color: #fff;
            border: 1px solid #aaa;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            cursor: default;
        }

        .multi-select-button:after {
            content: "";
            display: inline-block;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0.4em 0.4em 0 0.4em;
            border-color: #999 transparent transparent transparent;
            margin-left: 0.4em;
            vertical-align: 0.1em;
        }

        .multi-select-container--open .multi-select-menu {
            display: block;
        }

        .multi-select-container--open .multi-select-button:after {
            border-width: 0 0.4em 0.4em 0.4em;
            border-color: transparent transparent #999 transparent;
        }

        .multi-select-container--positioned .multi-select-menu {
            /* Avoid border/padding on menu messing with JavaScript width calculation */
            box-sizing: border-box;
        }

        .multi-select-container--positioned .multi-select-menu label {
            /* Allow labels to line wrap when menu is artificially narrowed */
            white-space: normal;
        }

        .multi-select-container,
        .multi-select-button {
            display: block;
        }

        .multi-select-button {
            width: 100% !important;
            font-size: inherit !important;
            padding: 6px 12px;
        }
    </style>
    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">View Attendance Report</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">View Attendance Report</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <form class="row d-flex align-items-center mb-4" method="POST"
                                action="{{ route('admin.generateReport') }}">
                                @csrf
                                <div class="col-12 col-md-10 d-flex row gap-8">
                                    <div class=" col-md-4 col-12 mb-3">
                                        <label for="report_type" class="form-label">Report Type </label>
                                        <select name="report_type" id="report_type" class="form-control">
                                            <option value="course_summary"
                                                @if ($report_type == 'course_summary') selected @endif>
                                                Course Attendance Summary</option>
                                            <option value="student_summary"
                                                @if ($report_type == 'student_summary') selected @endif>
                                                Student Attendance Summary</option>

                                        </select>
                                    </div>
                                    <div class="col-md-4 col-12 mb-3">
                                        <label for="daily" class="form-label">Daily?</label>
                                        <select name="daily" id="daily" class="form-control" aria-hidden="true">
                                            <option value="no" @if ('no' == ($selectedDailyOption ?? null)) selected @endif>No
                                            </option>
                                            <option value="yes" @if ('yes' == ($selectedDailyOption ?? null)) selected @endif>Yes
                                            </option>
                                        </select>
                                    </div>
                                    <div id="course_dropdown" class="col-md-4 col-12 mb-3 none">
                                        <label for="course" class="form-label">Select Course</label>
                                        <select multiple name="course_id[]" id="course_id" class="form-control"
                                            aria-hidden="true">
                                            <option value="0" @if ('0' == $selectedCourse) selected @endif>All
                                                Courses
                                            </option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}"
                                                    @if ($course->id == ($selectedCourse['id'] ?? null)) selected @endif>
                                                    {{ $course->location }} -
                                                    {{ $course->course_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-12 mb-3">
                                        <label for="dates">Select Date</label>
                                        <input type="text" name="dates" id="selected_date" class="form-control"
                                            value="{{ $dates }}" required>
                                    </div>
                                    <div class="col-md-4 col-12 mb-3">
                                        <label for="dates">Virtual Weeks</label>
                                        <select multiple name="virtual_week[]" id="virtual_week" class="form-control"
                                            aria-hidden="true">
                                            @php
                                                use Carbon\CarbonImmutable;

                                                $en = CarbonImmutable::now()->locale('en_UK');
                                                $weeks = $en->weeksInYear();
                                                $format = 'd M';

                                            @endphp
                                            @for ($i = 1; $i <= $weeks; $i++)
                                                <option value="{{ $i }}"
                                                    @if (in_array($i, $virtual_week)) selected @endif> Week
                                                    {{ $i }} -
                                                    ({{ $en->week($i)->startOfWeek()->format($format) }} -
                                                    {{ $en->week($i)->endOfWeek()->format($format) }})
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-12 mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="showEmoji" checked
                                            onchange="toggleEmoji()">
                                        <label class="form-check-label" for="showEmoji">
                                            Show Emoji
                                        </label>

                                    </div>
                                </div>
                                <div class="col-12 col-md-2">
                                    <input type="submit" class="btn btn-success mt-2" value="Generate Report" />
                                </div>


                            </form>

                            <div class="card-body">
                                @if ($report_type)
                                    <h4 class="text-uppercase mb-2 text-primary" id="reportHeading">
                                        {{ $selectedCourse->location ?? '' }}
                                        {{ $selectedCourse['course_name'] ?? '' }}
                                        {{ str_replace('_', ' ', $report_type) }}
                                        Report For
                                        {{ $dates }}</h4>
                                @endif
                                <table class="table table-striped table-bordered table-hover datatable">
                                    <thead>
                                        {{-- <tr>
                                            <th colspan="1"></th>
                                            <th colspan="{{ count($dates_array) }}">Dates</th>
                                            <th colspan="2">Statistics</th>

                                        </tr> --}}
                                        <tr>
                                            @if ($report_type == 'course_summary')
                                                <th>Course Name</th>
                                                <th>Average</th>
                                                <th>Total</th>
                                            @else
                                                <th>Student Name</th>
                                                <th>Course Name</th>
                                                @if ($virtualQuery)
                                                    <th>Virtual</th>
                                                    <th>In-Person</th>
                                                @endif
                                                <th>Total</th>
                                                <th>Gender</th>
                                                <th>Network Type</th>
                                                <th>Phone Number</th>
                                            @endif
                                            @if ($selectedDailyOption == 'yes')
                                                @foreach ($dates_array as $date)
                                                    <th>{{ $date }}</th>
                                                @endforeach
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($report_type == 'course_summary')
                                            @foreach ($attendanceData as $course => $record)
                                                <tr>
                                                    <td>{{ $course }}</td>
                                                    <td>{{ floor($record->first()->values()[0]->average ?? 0) }}</td>
                                                    <td>{{ $record->first()->values()[0]->attendance_total }}</td>
                                                    @if ($selectedDailyOption == 'yes')
                                                        @foreach ($dates_array as $date)
                                                            <th>{{ $record->get($date)?->values()[0]->total ?? 0 }}</th>
                                                        @endforeach
                                                    @endif

                                                </tr>
                                            @endforeach
                                        @endif

                                        @if ($report_type == 'student_summary')
                                            @foreach ($studentAttendanceData as $record)
                                                <tr>
                                                    <td class="text-lowercase">
                                                        <span class="text-uppercase">
                                                            {{ $record->first()[0]->user_name }}</span>

                                                        ({{ $record->first()[0]->email }})
                                                    </td>
                                                    <td>{{ $record->first()[0]->course_name }}
                                                        ({{ $record->first()[0]->course_location }})
                                                    </td>
                                                    @if ($virtualQuery)
                                                        <td>{{ $record->first()->values()[0]->virtual_attendance ?? 0 }}
                                                        <td>{{ $record->first()->values()[0]->in_person }}</td>
                                                    @endif
                                                    <td>{{ $record->first()->values()[0]->attendance_total ?? 0 }}
                                                    <td>{{ $record->first()[0]->user_gender ?? 'N/A' }}
                                                    </td>
                                                    <td>{{ $record->first()[0]->user_network_type ?? 'N/A' }}
                                                    </td>
                                                    <td>{{ $record->first()[0]->user_contact ?? 'N/A' }}
                                                    </td>
                                                    @if ($selectedDailyOption == 'yes')
                                                        @foreach ($dates_array as $date)
                                                            @php
                                                                $attended = $record->get($date)?->values()[0]
                                                                    ->attendance_date;
                                                            @endphp
                                                            <td @class([
                                                                'attendance-style' => true,
                                                                'yes' => $attended,
                                                                'no' => !$attended,
                                                            ])>
                                                                <span class="content">
                                                                    {{ $attended ? 'YES' : 'NO' }}
                                                                </span>
                                                            </td>
                                                            {{-- <th>{{ dump($record) }}</th> --}}
                                                        @endforeach
                                                    @endif

                                                </tr>
                                            @endforeach
                                        @endif

                                    </tbody>
                                    <tfoot>

                                    </tfoot>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
        </div>
        </section>
    </div>
    <!-- /.content-header -->
@endsection
@push('scripts')
    {{-- <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ url('assets/js/jquery-multiselect.min.js') }}"></script> --}}

    <script @nonce>
        $(document).ready(function() {
            $('input[name="dates"]').daterangepicker({
                showWeekNumbers: true,
                locale: {
                    format: 'MMMM D, YYYY'
                },
                ranges: {
                    'Start to Date': [moment('2024-10-14'), moment()],
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')]
                }
            });

            toggleEmoji();
        });

        function toggleCourseDropdown() {
            const reportType = document.getElementById('report_type').value;
            const courseDropdown = document.getElementById('course_dropdown');
            if (reportType === 'student_summary') {
                courseDropdown.style.display = 'block';
            } else {
                courseDropdown.style.display = 'none';
            }
        }
        document.getElementById('report_type').addEventListener('change', toggleCourseDropdown);
        document.querySelector('form').addEventListener('submit', function(event) {
            toggleCourseDropdown();
        });
        toggleCourseDropdown();

        $(document).prop('title', $('#reportHeading').text());
        $('#course_id').multiSelect();
        $('#virtual_week').multiSelect();

        function toggleEmoji() {
            const showEmoji = $('#showEmoji').is(':checked');
            if (showEmoji) {
                $('td.attendance-style').addClass('emoji');
                $('td.attendance-style.yes > .content').text('✅');
                $('td.attendance-style.no > .content').text('❌');
                setTimeout(() => {
                    $('.dtr-data > .content').each(function(i, el) {
                        const ele = $(el);
                        if (ele.text().trim().toLowerCase() == 'yes') {
                            ele.text('✅');
                        } else {
                            ele.text('❌');
                        }
                    });

                }, 100);

            } else {
                $('td.attendance-style').removeClass('emoji');
                $('td.attendance-style.yes > .content').text('YES');
                $('td.attendance-style.no > .content').text('NO');
                setTimeout(() => {
                    $('.dtr-data > .content').each(function(i, el) {
                        const ele = $(el);
                        if (ele.text().trim().toLowerCase() == '✅') {
                            ele.text('YES');
                        } else {
                            ele.text('NO');
                        }
                    });
                }, 100);

            }

            $('.datatable').DataTable().responsive.rebuild();
            $('.datatable').DataTable().responsive.recalc();

        }

        $('body').on('click', '.dtr-control', function() {
            const showEmoji = $('#showEmoji').is(':checked');
            if (showEmoji) {
                $('.dtr-data > .content').each(function(i, el) {
                    const ele = $(el);
                    if (ele.text().trim().toLowerCase() == 'yes') {
                        ele.text('✅');
                    } else {
                        ele.text('❌');
                    }
                });
            } else {
                $('.dtr-data > .content').each(function(i, el) {
                    const ele = $(el);
                    if (ele.text().trim().toLowerCase() == '✅') {
                        ele.text('YES');
                    } else {
                        ele.text('NO');
                    }
                });
            }
        });
    </script>
@endpush
