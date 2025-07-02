@extends('layouts.app', [
    'activePage' => 'manageCourse',
])
@section('title', 'Edit Course')
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
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
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
                                    <form id="manage_form">
                                        @csrf
                                        @method('PUT')
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="branch">Branch</label>
                                                    <select name="branch_id" class="form-control" id="branch_id">
                                                        <option value="" disabled selected>-- Select Branch --
                                                        </option>
                                                        @foreach ($branches as $branch)
                                                            <option value="{{ $branch->id }}"
                                                                @if ($course->centre->branch->id === $branch->id) selected @endif>
                                                                {{ $branch->title }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="branch_id_error font-weight-bold invalid-feedback block"
                                                        role="alert"></span>
                                                </div>
                                            </div>

                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="centre">Centre</label>
                                                    <select name="centre_id" class="form-control" id="centre_id">
                                                        <option value="" disabled selected>-- Select Centre --
                                                        </option>
                                                        @foreach ($centres as $centre)
                                                            <option value="{{ $centre->id }}"
                                                                @if ($course->centre->id === $centre->id) selected @endif>
                                                                {{ $centre->title }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="centre_id_error font-weight-bold invalid-feedback block"
                                                        role="alert"></span>
                                                </div>
                                            </div>

                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="programme">Programme</label>
                                                    <select name="programme_id" class="form-control" id="programme_id">
                                                        <option value="" disabled selected>-- Select Programme --
                                                        </option>
                                                        @foreach ($programmes as $programme)
                                                            <option value="{{ $programme->id }}"
                                                                @if ($course->programme->id === $programme->id) selected @endif>
                                                                {{ $programme->title }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="programme_id_error font-weight-bold invalid-feedback block"
                                                        role="alert"></span>
                                                </div>
                                            </div>



                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="duration">Duration</label>
                                                    <input type="text" value="{{ $course->duration }}" name="duration"
                                                        placeholder="Enter duration" class="form-control" id="duration">
                                                    <span class="duration_id_error font-weight-bold invalid-feedback block"
                                                        role="alert"></span>
                                                </div>
                                            </div>


                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="start_date">Start Date</label>
                                                    <input type="date" value="{{ $course->start_date }}"
                                                        name="start_date" placeholder="Enter start_date"
                                                        class="form-control" id="start_date">
                                                    <span
                                                        class="start_date_id_error font-weight-bold invalid-feedback block"
                                                        role="alert"></span>
                                                </div>
                                            </div>



                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="end_date">End Date</label>
                                                    <input type="date" value="{{ $course->end_date }}" name="end_date"
                                                        placeholder="Enter end_date" class="form-control" id="end_date">
                                                    <span class="end_date_id_error font-weight-bold invalid-feedback block"
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
            $(document).ready(function() {
                $(document).on('change', '#branch_id', function(e) {
                    e.preventDefault();

                    const branchId = $(this).val(); // Get the selected branch ID
                    $.ajax({
                        type: 'GET',
                        url: "{{ route('admin.course.fetch.centre') }}",
                        data: {
                            branch_id: branchId
                        }, // Send branch_id in the request
                        success: function(response) {
                            // Assuming response contains a list of centres, populate the #centre_id dropdown
                            $('#centre_id').empty(); // Clear existing options
                            $('#centre_id').append(
                                '<option value="" disabled selected>-- Select Centre --</option>'
                            );

                            // Loop through response data and append options
                            response.centres.forEach(function(centre) {
                                $('#centre_id').append(
                                    `<option value="${centre.id}">${centre.title}</option>`
                                );
                            });
                        },
                        error: function(xhr) {
                            console.error("An error occurred: ", xhr.responseText);
                            alert("An unexpected error occurred. Please try again.");
                        }
                    });
                });

            })

            const manageAction = "{{ route('admin.course.update', $course) }}";
            const method = 'PUT';
        </script>
    @endsection
