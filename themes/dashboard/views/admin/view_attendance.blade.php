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
                                <div class="mb-4 col-md-4">
                                    <label for="course_id" class="form-label">Select Course</label>
                                    <select name="course_id" id="course_id" class="form-control">
                                        <option value="">Select Course</option>

                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}"
                                                @if ($course->id == $selectedCourse?->id) selected @endif>
                                                {{ $course->location }} -
                                                {{ $course->course_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
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
                                            @if (Auth::user()->isSuper())
                                                <td>Action</td>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($attendance as $key => $record)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $record->name }}</td>
                                                <td>{{ $record->email }}</td>
                                                @if (Auth::user()->isSuper())
                                                    <form action="{{ route('admin.remove-attendance', $record->id) }}"
                                                        name="remove-attendance-{{ $record->id }}">
                                                        <td>
                                                            <button onclick="removeAttendance()"
                                                                data-id="{{ $record->id }}"
                                                                class="btn btn-sm btn-danger">Remove</button>
                                                        </td>
                                                    </form>
                                                @endif
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
            function reloadPage() {
                const course_id = $('#course_id').val();
                const selected_date = $('#selected_date').val();

                if (course_id && selected_date) {
                    window.location.href =
                        `{{ route('admin.viewAttendanceByDate') }}?course_id=${course_id}&date=${selected_date}`;
                }
            }
            $('#course_id').on('change', function() {
                reloadPage();
            });

            $('#selected_date').on('change', function() {
                reloadPage();
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
