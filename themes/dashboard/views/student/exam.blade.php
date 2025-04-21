@extends('layouts.student')
@section('title', 'Exams')
@section('content')


    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Aptitude Test</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Aptitude Test</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->

            <section class="content">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <div class="card">

                                <div class="card-body">
                                    <table class="table table-striped table-bordered table-hover datatable"
                                        style="width: max-100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Test title</th>
                                                <th>Test deadline</th>
                                                <th>Status</th>
                                                {{-- <th>Result</th> --}}
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($student_info as $std_info)
                                                <tr>
                                                    <td>1</td>
                                                    <td>{{ $std_info['title'] }}</td>
                                                    @php
                                                        $left = Carbon\Carbon::now()->diffInHours(
                                                            new Carbon\Carbon($std_info['exam_date']),
                                                        );

                                                        $studentTimeAllowed = Carbon\Carbon::now()->diffInHours(
                                                            (new Carbon\Carbon($std_info['registered']))->addDays(
                                                                config(EXAM_DEADLINE_AFTER_REGISTRATION, 2),
                                                            ),
                                                        );

                                                        // ->diffInHours(new Carbon\Carbon($std_info['exam_date']));

                                                    @endphp
                                                    <td>
                                                        <x-exam-deadline :date="$std_info['exam_date']"></x-exam-deadline>
                                                    </td>
                                                    <td>
                                                        @if ($std_info['submitted'])
                                                            <span class="badge badge-success">Submitted</span>
                                                        @else
                                                            <span class="badge badge-danger">Not Submitted</span>
                                                        @endif
                                                    </td>
                                                    {{-- <td>

                                                        @if ($std_info['submitted'] != null)
                                                            <a href="{{ url('student/view_result/' . $std_info['exam_id']) }}"
                                                                class="btn btn-info btn-sm">View Result</a>
                                                        @endif
                                                    </td> --}}


                                                    <td>
                                                        <x-can-take-exam :date="$std_info['exam_date']">
                                                            @if ($std_info['exam_joined'] == 0)
                                                                <a href="{{ url('student/join_exam/' . $std_info['exam_id']) }}"
                                                                    class="btn btn-primary btn-sm">Take Test Now</a>
                                                                {{-- @else
                                                          <a href="{{ url('student/view_answer/' . $std_info['exam_id']) }}"
                                                          class="btn btn-primary btn-sm">View Answers</a> --}}
                                                            @endif
                                                        </x-can-take-exam>
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
    @endsection
