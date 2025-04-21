@extends('layouts.app', [
    'activePage' => 'manageClassSchedule',
])
@section('title', 'Edit Class Schedule')
@section('content')

    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Edit Course</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href=p"#">Home</a></li>
                            <li class="breadcrumb-item active">Edit Course</li>
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

                                <div class="card-body">
                                    <form id="course_form">
                                        @csrf
                                        @method('PUT')
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="">Enter course title</label>

                                                    <input type="text" value="{{ $course->title }}" name="title"
                                                        placeholder="Enter course title" class="form-control"
                                                        id="title">
                                                    <span class="title_error font-weight-bold invalid-feedback block"
                                                        role="alert"></span>
                                                </div>
                                            </div>
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <button class="btn btn-primary">Update</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
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
        <script @nonce>
            const manageCourseAction = "{{ route('admin.course.update', $course) }}";
            const method = 'PUT';
        </script>
    @endsection
