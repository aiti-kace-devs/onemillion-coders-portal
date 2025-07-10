@extends('layouts.app', [
    'activePage' => 'manageClassSchedule',
])
@section('title', 'Manage Class Schedule')
@section('content')

    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manage Class Schedule</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Manage Class Schedule</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Title</h3>

                                    <div class="card-tools">
                                        <a class="btn btn-info btn-sm" href="javascript:;" data-toggle="modal"
                                            data-target="#manageModal">Add new</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped table-bordered table-hover datatable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Course</th>
                                                <th>Session</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($schedules as $key => $schedule)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $schedule->title }}</td>
                                                    <td class="d-flex">
                                                        <a href="{{ route('admin.course.edit', $schedule->id) }}"
                                                            class="btn btn-info">Edit</a>
                                                        <a href="{{ route('admin.course.destroy', $schedule->id) }}"
                                                            class="btn btn-danger ml-2">Delete</a>
                                                    </td>
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

        <!-- Modal -->

        <div class="modal fade" id="manageModal" role="dialog">
            <div class="modal-dialog">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Schedule Class</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="manage_form">
                            @csrf
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="course">Course</label>
                                        <select name="course_id" class="form-control" id="course_id">
                                            <option value="" disabled selected>-- Select Course --</option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                                            @endforeach
                                        </select>
                                        <span class="course_id_error block font-weight-bold invalid-feedback" @nonce
                                            role="alert"></span>
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="session">Session</label>
                                        <select name="session_id" class="form-control" id="session_id">
                                            <option value="" disabled selected>-- Select Session --</option>
                                            @foreach ($sessions as $session)
                                                <option value="{{ $session->id }}">{{ $session->title }}</option>
                                            @endforeach
                                        </select>
                                        <span class="session_id_error block font-weight-bold invalid-feedback"
                                            role="alert"></span>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Add</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>


            <script @nonce>
                const manageAction = "{{ route('admin.class.schedule.store') }}";
                const method = 'POST';
            </script>
        @endsection
