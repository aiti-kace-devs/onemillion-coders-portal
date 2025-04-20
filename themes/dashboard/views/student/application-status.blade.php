@extends('layouts.student')
@section('title', 'Application Status')
@section('content')
    <style>
        .status-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 28px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .status-number {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 50%;
            background-color: #e0e0e0;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            z-index: 1;
        }

        .status-number.active {
            background-color: #28a745;
            color: white;
        }

        .status-number.pending {
            background-color: #d22222;
            color: white;
        }

        .passed {
            color: #28a745;
        }

        .passed::after {
            content: '- (Completed✅) ';
        }

        .not-passed {
            color: #d22222;
        }

        .not-passed::after {
            content: '- (Pending⏳)';
        }

        .status-details {
            flex-grow: 1;
        }

        .status-details h5 {
            margin-bottom: 5px;
        }


        .arrow {
            margin-left: auto;
            transition: transform 0.2s ease-in-out;
        }

        .arrow.rotated {
            transform: rotate(90deg);
        }

        .collapse-content {
            padding-left: 45px;
            margin-top: 10px;
        }

        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            cursor: pointer;
            position: relative;
            /* Needed for absolute positioning of line */
        }

        .status-line {
            position: absolute;
            left: 14px;
            /* Center with the number */
            width: 3px;
            background-color: #28a745;
            z-index: 0;
        }

        .status-item:not(:first-child) .status-line {
            /* top: -20px; */
            /* Adjust based on the margin-bottom of status-item */
            /* height: calc(100% + 20px); */
            /* Adjust based on the margin-bottom of status-item */
        }

        .status-item:last-child .status-line {
            display: none;
        }

        .text-row {
            display: flex;
            justify-content: space-between;
            align-items: center
        }
    </style>
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Application Status</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active">Application Status</li>
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

                {{-- <h2>Application Status</h2> --}}
                <div class="status-container">

                    <div class="status-item">
                        <div class="status-number active">1</div>
                        <div class="status-details">
                            <h5 class="passed">Application Submitted </h5>
                            {{-- <button class="btn btn-success">Complete my task -></button> --}}
                        </div>
                        <div class="status-line"></div>
                    </div>
                    <div class="collapse-content " id="collapse1">
                        <p>Your application has been successfully received, and all details have been captured.</p>
                    </div>

                    <div class="status-item" data-toggle="collapse" data-target="#collapse2" aria-expanded="true"
                        aria-controls="collapse2">
                        <div class="status-number  @if ($user_exam?->submitted) active @else pending @endif"
                            data-step="2">2</div>
                        <div class="status-details">
                            <span class="text-row">

                                <h5
                                    class="@if ($user_exam?->submitted) passed
                                @else
                                not-passed @endif">
                                    Aptitude Test
                                </h5>

                                @unless ($user_exam?->submitted)
                                    <a href="{{ url('student/join_exam/' . $user_exam->exam_id) }}"
                                        class="btn btn-danger mb-2">Take
                                        Test
                                        Now</a>
                                @endunless
                            </span>
                        </div>
                        {{-- <div class="arrow">></div> --}}
                        <div class="status-line"></div>
                    </div>
                    <div class="collapse-content " id="collapse2">
                        @if ($user_exam?->submitted)
                            <p> Test submitted on {{ Carbon\Carbon::parse($user_exam->submitted)->toDateTimeString() }}</p>
                        @else
                            <p>You must complete the aptitude test to proceed to the next stage. Click “Take Test Now” to
                                begin.
                            </p>
                        @endif

                    </div>

                    <div class="status-item" data-toggle="collapse" data-target="#collapse3" aria-expanded="true"
                        aria-controls="collapse3">
                        <div class="status-number @if ($user_admission) active
                                @else
                                    pending @endif"
                            data-step="3">3</div>
                        <div class="status-details">
                            <h5
                                class="@if ($user_admission) passed
                                @else
                                    not-passed @endif">
                                Shortlisted </h5>
                        </div>
                        {{-- <div class="arrow">></div> --}}
                        <div class="status-line"></div>
                    </div>
                    <div class="collapse-content" id="collapse3">
                        @if ($user_admission)
                            <p>Your application has been reviewed and have been selected for admission. Kindly confirm your
                                session </p>
                        @else
                            <p>Our team will review your application, and if selected, you will receive a notification via
                                email
                                and SMS. Please check your inbox and messages regularly. </p>
                        @endif
                    </div>

                    <div class="status-item" data-toggle="collapse" data-target="#collapse4" aria-expanded="true"
                        aria-controls="collapse4">
                        <div class="status-number  @if ($user_admission?->session) active @else pending @endif"
                            data-step="4">4</div>
                        <div class="status-details">
                            <span class="text-row">
                                <h5
                                    class="@if ($user_admission?->confirmed) passed
                                @else
                                not-passed @endif">
                                    Confirm Admission</h5>
                                @if ($user_admission && !$user_admission?->confirmed)
                                    <a href="{{ url('student/select-session/' . auth()->user()->userId) }}"
                                        class="btn btn-primary mb-2">
                                        Choose A Session</a>
                                @endif
                            </span>
                        </div>

                        {{-- <div class="arrow">></div> --}}
                    </div>

                    <div class="collapse-content" id="collapse4">
                        @if ($user_admission && $user_admission->confirmed)
                            Congratulations, you have been admitted <br>
                            <button id="revoke-admission-button" class="btn btn-danger mb-2">Revoke Admission Now</button>
                        @else
                            <p>If shortlisted, you must select a session to confirm your admission. Further instructions
                                will be
                                provided upon selection.</p>
                        @endif
                    </div>
                </div>
        </section>

    @endsection

    @push('scripts')
        @include('student.decline-admission-js', ['id' => auth()->user()->userId])
    @endpush
