@extends('layouts.app')
@section('title', 'Courses and Students')
@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <form action="{{ route('admin.verify-details') }}" method="GET">
                    <div class="row">
                        <div class="mb-4 col-md-4">
                            <label for="course">Select a Course</label>
                            <select name="course_id" id="course" class="form-control" onchange="this.form.submit()">
                                <option value="">Select a Course</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}"
                                        {{ $selectedCourse && $selectedCourse->id == $course->id ? 'selected' : '' }}>
                                        {{ $course->course_name }} - {{ $course->location }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4 col-md-2">
                </form>

            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Small boxes (Stat box) -->

                <div class="col-12">
                    <!-- Default box -->
                    <div class="card">
                        @if ($selectedCourse)
                            <div class="card-body">
                                <table class="table table-striped table-bordered table-hover datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Ghana Card</th>
                                            {{-- <th>Verification Date</th> --}}
                                            <th>Action</th>
                                        </tr>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($students as $key => $student)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $student->name }}</td>
                                                <td>{{ $student->ghcard }}</td>
                                                {{-- <td>{{ $student->verification_date }}</td> --}}
                                                <td>
                                                    @if (!$student->verification_date && !$student->verified_by)
                                                        <form method="POST"
                                                            action="{{ route('admin.verify-student', $student['id']) }}">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn btn-success btn-sm">Verify</button>
                                                            <a href="{{ route('admin.reset-verify', $student['id']) }}"
                                                                class="btn btn-danger btn-sm">Reset</a>
                                                        </form>
                                                    @else
                                                        <span class="badge badge-primary">Verified on
                                                            {{ $student->verification_date }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                        @endif
                        <!-- /.row -->
                        <!-- Main row -->

                        <!-- /.row (main row) -->
                    </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
@endsection
