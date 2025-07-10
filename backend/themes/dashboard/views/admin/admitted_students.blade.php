@extends('layouts.app')
@section('title', 'Admitted Students')
@section('content')

    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Admitted Students</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Admitted Students</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <div class="card-header">

                                <p>Select Course Session</p>
                                <select name="session_id" id="session_id" class="form-control">
                                    <option value="">Select Course</option>

                                    @foreach ($sessions as $session)
                                        <option value="{{ $session->id }}"
                                            @if ($session->id == $selectedSession?->id) selected @endif>
                                            {{ $session->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="card-body">
                            <table class="table table-striped table-bordered table-hover datatable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>Email</th>
                                        <th>Email Sent On</th>
                                        <th>Confirmed On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($students as $key => $student)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $student->name }}</td>
                                            <td>{{ $student->email }}</td>
                                            <td>{{ $student->admission_email_sent }}</td>
                                            <td>{{ $student->admission_confirmed }}</td>
                                            <td>
                                                @if (is_null($student->admission_email_sent))
                                                    <button type="submit" class="btn btn-success btn-sm">Admit
                                                        Student</button>
                                                @endif
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
@endsection
@push('scripts')
    <script @nonce>
        $('#session_id').on('change', function(e) {
            const session_id = $('#session_id').val()
            window.location.href = `{{ route('admin.admittedStudents') }}?session_id=${session_id}`;
        })
    </script>
@endpush
