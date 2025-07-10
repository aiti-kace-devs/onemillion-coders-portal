@extends('layouts.student')
@section('title', 'Portal dashboard')
@section('content')

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
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Small boxes (Stat box) -->

                <div class="row">
                    @foreach ($portal_exams as $key => $exam)
                        <?php
                        
                        if (strtotime(date('Y-m-d')) > strtotime($exam['exam_date'])) {
                            $cls = 'bg-danger';
                        } else {
                            $val = $key + 1;
                            if ($val % 2 == 0) {
                                $cls = 'bg-info';
                            } else {
                                $cls = 'bg-info';
                            }
                        }
                        
                        ?>
                        {{-- <div class="col-lg-8 col-12 mx-auto">
                            <div class="small-box {{ $cls }} text-center">
                                <div class="inner">
                                    <h3>{{ $exam['title'] }}</h3>
                                    <p>{{ $exam['category_name'] }}</p>
                                    <p>Exam date : {{ $exam['exam_date'] }}</p>
                                    <p>Duration : {{ $exam['exam_duration'] }} mins</p>
                                    <p>Pass Mark : {{ $exam['passmark'] }}</p>
                                    <p>Total Questions : {{ $exam['question_count'] }}</p>

                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                @if (strtotime(date('Y-m-d')) <= strtotime($exam['exam_date']))
                                    <a href="{{ url('student/join_exam/' . $exam['exam_id']) }}"
                                    class="btn btn-warning mt-3 mb-3">Take Test &nbsp;<i
                                            class="fas fa-arrow-circle-right"></i></a>
                                @endif

                            </div>
                        </div> --}}

                        <div class="col-lg-8 col-12 mx-auto">
                            <div class="small-box text-center custom-card p-4">
                                <div class="inner">
                                    <!-- Exam Title -->
                                    <h2 class="exam-title">{{ $exam['title'] }}</h2>

                                    <!-- Exam Category -->
                                    <p class="exam-category">Category: {{ $exam['category_name'] }}</p>

                                    <!-- Exam Details -->
                                    <div class="exam-details py-3">
                                        @if ($exam['submitted'] == null)
                                            <p class="exam-detail"><strong>Test Deadline:</strong><x-exam-deadline
                                                    :date="$exam['exam_date']"></x-exam-deadline></p>
                                        @else
                                            <p class="exam-detail"><strong>Test Submitted
                                                    On:</strong>{{ $exam['submitted'] }}</p>
                                        @endif
                                        <p class="exam-detail"><strong>Duration:</strong> {{ $exam['exam_duration'] }} mins
                                        </p>
                                        {{-- <p class="exam-detail"><strong>Pass Mark:</strong> {{ $exam['passmark'] }}</p> --}}
                                        <p class="exam-detail"><strong>Total Questions:</strong>
                                            {{-- {{ $exam['question_count'] }} --}}
                                            30
                                        </p>
                                    </div>
                                </div>

                                <!-- Icon -->
                                <div class="icon my-3">
                                    <i class="fas fa-book exam-icon"></i>
                                </div>

                                <!-- Call to Action Button -->
                                <x-can-take-exam :date="$exam['exam_date']">
                                    @if ($exam['submitted'] == null)
                                        <a href="{{ url('student/join_exam/' . $exam['exam_id']) }}"
                                            class="btn custom-btn mt-3">
                                            Take Test &nbsp;<i class="fas fa-arrow-circle-right"></i>
                                        </a>
                                    @endif
                                </x-can-take-exam>
                            </div>
                        </div>
                    @endforeach

                </div>
                <!-- /.row -->
                <!-- Main row -->

                <!-- /.row (main row) -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->



@endsection
