@extends('layouts.app')
@section('title', 'View Attendance')
@section('content')

    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">View Attendance List</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">View Attendance List</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <div class="row">
                                <x-course-selector :groupedCourses="$groupedCourses" :sessions="$sessions" :selectedSessions="$selectedSessions"
                                    :selectedCourse="$selectedCourse"></x-course-selector>
                                <div class="mb-4 col-md-4">
                                    <label for="date">Select Date</label>
                                    <input type="date" name="date" id="selected_date" class="form-control" required
                                        value="{{ old('date', $selectedDate) }}">
                                </div>
                            </div>

                            <div class="card-body">
                                <table class="table table-striped table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Student Name</th>
                                            <th>Email</th>
                                            <th>Session</th>

                                            @can('attendance.delete')
                                                <td>Action</td>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($attendance as $key => $record)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $record->name }}</td>
                                                <td>{{ $record->email }}</td>
                                                <td>{{ $record->session }}</td>

                                                @can('attendance.delete')
                                                    <form action="{{ route('admin.remove-attendance', $record->id) }}"
                                                        name="remove-attendance-{{ $record->id }}">
                                                        <td>
                                                            <button onclick="removeAttendance()" data-id="{{ $record->id }}"
                                                                class="btn btn-sm btn-danger">Remove</button>
                                                        </td>
                                                    </form>
                                                @endcan
                                            </tr>
                                        @endforeach
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
    <script @nonce>
        $(document).ready(function() {
            function callOnce(func, within = 300, timerId = null) {
                window.callOnceTimers = window.callOnceTimers || {};
                if (timerId == null)
                    timerId = func;
                var timer = window.callOnceTimers[timerId];
                clearTimeout(timer);
                timer = setTimeout(() => func(), within);
                window.callOnceTimers[timerId] = timer;
            }

            function reloadPage() {
                const course_id = $('#course_id').val();
                const selected_date = $('#selected_date').val();
                const selected_sessions = $('#session_id').val();


                if (course_id && selected_date) {
                    window.location.href =
                        `{{ route('admin.viewAttendanceByDate') }}?course_id=${course_id}&date=${selected_date}${ selected_sessions != "" ?  `&session_ids=${selected_sessions}` : ''}`;
                }
            }
            $('#course_id').on('change', function() {
                $('#session_id').multiselect('deselectAll');

                reloadPage();
            });

            $('#selected_date').on('change', function() {
                reloadPage();
            });

            $('#session_id').multiselect('refresh');
            $('#session_id').on('change', function() {
                callOnce(function() {
                    reloadPage()
                }, 800);
            });




            function removeAttendance(id) {
                Swal.fire({
                    title: 'Remove Attendance',
                    text: `Are you sure you want to remove attendance?`,
                    icon: 'info',
                    backdrop: `rgba(0,0,0,0.95)`,
                    confirmButtonText: 'Yes, Submit',
                    cancelButtonText: 'No, Cancel',
                    showCancelButton: true,
                    allowOutsideClick: false,
                    preConfirm: async () => {
                        const form = $(`[name="remove-attendance-${id}"]`);
                        form.submit();
                    }
                })
            }
        });
    </script>
@endpush
