@extends('layouts.app', [
    'activePage' => 'manageCourse',
])
@section('title', 'Manage Course')
@section('content')

    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manage Course</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Manage Course</li>
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
                                                <th>Title</th>
                                                <th>Centre</th>
                                                <th>Duration</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($courses as $key => $course)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>{{ $course->programme?->title }}</td>
                                                    <td>{{ $course->centre?->title }}, {{ $course->centre?->branch->title }}
                                                    <td>{{ $course['duration'] }}</td>
                                                    <td>{{ $course['start_date'] }}</td>
                                                    <td>{{ $course['end_date'] }}</td>
                                                    <td><input class="course_status" data-id="<?php echo $course['id']; ?>"
                                                            <?php if ($course['status'] == 1) {
                                                                echo 'checked';
                                                            } ?> type="checkbox" name="status"></td>
                                                    </td>
                                                    <td class="d-flex">
                                                        <a href="{{ route('admin.course.edit', $course->id) }}"
                                                            class="btn btn-info">Edit</a>
                                                        <a href="{{ route('admin.course.destroy', $course->id) }}"
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
                        <h4 class="modal-title">Add new Course</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="manage_form">
                            @csrf
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="branch">Branch</label>
                                        <select name="branch_id" class="form-control" id="branch_id">
                                            <option value="" disabled selected>-- Select Branch --</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->title }}</option>
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
                                            <option value="" disabled selected>-- Select Centre --</option>
                                        </select>
                                        <span class="centre_id_error font-weight-bold invalid-feedback block"
                                            role="alert"></span>
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="programme">Programme</label>
                                        <select name="programme_id" class="form-control" id="programme_id">
                                            <option value="" disabled selected>-- Select Programme --</option>
                                            @foreach ($programmes as $programme)
                                                <option value="{{ $programme->id }}">{{ $programme->title }}</option>
                                            @endforeach
                                        </select>
                                        <span class="programme_id_error font-weight-bold invalid-feedback block"
                                            role="alert"></span>
                                    </div>
                                </div>



                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="">Duration</label>
                                        <input type="text" name="duration" placeholder="Enter duration"
                                            class="form-control" id="duration">
                                        <span class="duration_error font-weight-bold invalid-feedback block"
                                            role="alert"></span>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="">Start Date</label>
                                        <input type="date" name="start_date" placeholder="Enter start_date"
                                            class="form-control" id="start_date">
                                        <span class="start_date_error font-weight-bold invalid-feedback block"
                                            role="alert"></span>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="">End Date</label>
                                        <input type="date" name="end_date" placeholder="Enter end_date"
                                            class="form-control" id="end_date">
                                        <span class="end_date_error font-weight-bold invalid-feedback block"
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

                const manageAction = "{{ route('admin.course.store') }}";
                const method = 'POST';
            </script>



            <script @nonce>
                $(document).ready(function() {
                    $(document).on('change', '#programme_id', function() {
                        const programmeId = $(this).val();

                        if (programmeId) {
                            $.ajax({
                                type: 'GET',
                                url: "{{ route('admin.course.fetch.programme') }}",
                                data: {
                                    programme_id: programmeId
                                },
                                success: function(response) {
                                    // Populate the form fields
                                    $('#duration').val(response.duration);
                                    $('#start_date').val(response.start_date);
                                    $('#end_date').val(response.end_date);
                                },
                                error: function(xhr) {
                                    console.error("An error occurred: ", xhr.responseText);
                                    alert("Failed to fetch programme details. Please try again.");
                                }
                            });
                        }
                    });
                });
            </script>



        @endsection
