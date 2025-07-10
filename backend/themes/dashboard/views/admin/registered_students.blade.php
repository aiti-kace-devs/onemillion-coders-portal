@extends('layouts.app')
@section('title', 'Manage Portal')
@section('content')


    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manage Portal</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Manage Exam</li>
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

                                    {{-- <div class="card-tools">
                        <a class="btn btn-info btn-sm" href="javascript:;" data-toggle="modal" data-target="#myModal">Add new</a>
                  </div> --}}
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped table-bordered table-hover datatable">
                                        <thead>
                                            <tr>
                                                <th>Name (Email)</th>
                                                <th>Admitted</th>
                                                <th>Course</th>
                                                <th>Session</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($users as $key => $p)
                                                <tr>
                                                    <td>{{ $p['name'] }} ({{ $p['email'] }})</td>
                                                    <td>
                                                        @if ($p['admitted'])
                                                            <span class="badge badge-primary">Admitted</span>
                                                        @else
                                                            <span class="badge badge-danger">Not Admitted</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $p['course_name'] }}</td>
                                                    <td>{{ $p['session_name'] }}</td>


                                                    <td>
                                                        @if (!$p['admitted'])
                                                            <button class="btn btn-primary btn-sm"
                                                                onclick="openModal('{{ $p['userId'] }}')">Admit</button>
                                                        @else
                                                            <button class="btn btn-info btn-sm"
                                                                onclick="openModal('{{ $p['userId'] }}', '{{ $p['course_id'] }}', '{{ $p['session_id'] }}')">Change
                                                                Admission</button>
                                                        @endif
                                                        @if ($p['admitted'] && !$p['session_name'])
                                                            <a href="{{ url('/student/select-session/' . $p['userId']) }}"
                                                                target="_blank" class="btn btn-primary btn-sm">Choose
                                                                Session</a>
                                                        @endif
                                                        @if (Auth::user()->isSuper())
                                                            <a href="{{ url('admin/delete_registered_students/' . $p['id']) }}"
                                                                class="btn btn-danger btn-sm">Delete</a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            {{ $users->links() }}
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
        <div class="modal fade" id="myModal" role="dialog">
            <div class="modal-dialog">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Admit Student</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ url('/admin/admit') }}" name="admit_form" method="POST">
                            <div class="row">
                                {{ csrf_field() }}
                                <div class="col-sm-12">
                                    <input id="user_id" name="user_id" type="hidden" class="form-control" required>
                                    <input id="change" name="change" value="false" type="hidden" class="form-control"
                                        required>

                                    <div class="form-group">
                                        <label for="course_id" class="form-label">Select Course</label>
                                        <select id="course_id" name="course_id" class="form-control" required>
                                            <option value="">Choose One Course</option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}"> {{ $course->location }} -
                                                    {{ $course->course_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="session_id" class="form-label">Choose Session</label>
                                        <select id="session_id" name="session_id" class="form-control">
                                            <option value="">Choose One Session</option>

                                            @foreach ($sessions as $session)
                                                <option data-course="{{ $session->course_id }}"
                                                    value="{{ $session->id }}">
                                                    {{ $session->name }} </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <button class="btn btn-primary">Admit</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>




        @endsection

        @push('scripts')
            <script @nonce>
                const form = $('[name="admit_form"]')
                let selectedUser = null;

                const courseInput = $('#course_id');
                const sessionInput = $('#session_id');


                courseInput.on('change', function(e) {
                    const courseId = courseInput.val();
                    $('#session_id option').map(function(i, o) {
                        $(o).show()
                        if (courseId != $(o).attr('data-course')) {
                            $(o).hide()
                        }
                    })
                });

                form

                function openModal(id, course = null, session = null) {
                    $('#user_id').val(id);
                    $('#course_id').val(course);
                    $('#session_id').val(session);
                    if (course) {
                        $('#myModal button').text('Change Admission');
                        $('#change').val('true');

                    } else {
                        $('#myModal button').text('Admit');
                        $('#change').val('false');
                    }

                    $('#myModal').modal('show');
                }
                // form.
            </script>
        @endpush
