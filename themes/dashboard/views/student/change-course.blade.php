@extends('layouts.student')
@section('title', 'Change Course')
@section('content')

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Change Course</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ url('student/dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active">Change Course</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        {{ session('error') }}
                    </div>
                @endif

                <div class="row">
                    <div class="col-lg-8 col-12 mx-auto">
                        <div class="card">
                            <div class="card-header bg-info">
                                <h3 class="card-title">Select New Course</h3>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('student.update-course') }}" method="POST" id="courseForm">
                                    @csrf

                                    <!-- Course Selection -->
                                    <div class="form-group">
                                        <label for="course_id">Course <span class="text-danger">*</span></label>
                                        <select class="form-control @error('course_id') is-invalid @enderror" id="course_id"
                                            name="course_id" required>
                                            <option value="">-- Select Course --</option>
                                            @foreach ($courses as $course)
                                                <option value="{{ $course->id }}"
                                                    {{ $user->exam == $course->id ? 'selected' : '' }}>
                                                    {{ $course->course_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('course_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>


                                    <div class="form-group">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <strong>Please Note:</strong> Changing your course may affect your exam schedule
                                            and progress. Make sure this is the right decision.
                                        </div>
                                    </div>

                                    <div class="form-group text-center">
                                        <a href="{{ url('student/dashboard') }}" class="btn btn-secondary mr-2">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Confirm Course Change
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <script @nonce>
        $(document).ready(function() {
            $('#courseForm').submit(function(e) {
                var courseId = $('#course_id').val();

                if (!courseId) {
                    e.preventDefault();
                    $('#course_id').addClass('is-invalid');
                    $('#course_id').after(
                        '<span class="invalid-feedback d-block">Please select a course.</span>');

                    return false;
                }

                return true;
            });

            $('.form-control').on('change keyup', function() {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            });
        });
    </script>

@endsection
