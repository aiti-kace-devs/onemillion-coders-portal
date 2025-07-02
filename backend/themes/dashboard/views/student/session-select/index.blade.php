@extends('layouts.student')
@section('title', 'Select Session')
@php
    $noSide = true;
@endphp
@section('content')
    <style @nonce>
        body {
            background-color: #f8f9fa;
            font-family: sans-serif;
        }

        .container {
            margin-top: 50px;
        }

        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            border-radius: 0.25rem;
        }

        .card-header {
            background-color: #007bff;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            padding: 1rem 1.5rem;
            border-bottom: none;
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0.25rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        label {
            font-weight: bold;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            border-radius: 0.2rem;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1.5rem;
        }

        .decline-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
    </style>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->

        <!-- /.content-header -->

        <!-- /.content-header -->
        {{-- "name" => $user->name,
        "sessions" => $sessions,
        "course" => $courseDetails, --}}
        {{--
        @dump($sessions) --}}
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="container">
                    <div class="card">
                        <div class="card-header">
                            Select Your Session or Revoke Admission
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info" role="alert">
                                Congratulations,{{ $user->name }}! @if ($admission->confirmed)
                                    You have already selected:
                                    <strong> {{ $session->name }} - {{ $session->course_time }}</strong>
                                @else
                                    Please select a session for <strong>{{ $course->course_name }}</strong>.
                                @endif
                            </div>
                            <form action="{{ route('student.select-session', $user->userId) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="sessionSelect">Available Sessions:</label>
                                    <select class="form-control" name="session_id">
                                        <option value="" disabled selected>Select a session</option>
                                        @foreach ($sessions as $s)
                                            @if ($s->slotLeft() > 0)
                                                @if ($s->id != $session?->id ?? '')
                                                    <option value="{{ $s->id }}">{{ $s->name }} (
                                                        {{ $s->slotLeft() }} slots left )
                                                        - {{ $s->course_time }}
                                                    </option>
                                                @endif
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">
                                    @if ($admission->confirmed)
                                        Change Selection
                                    @else
                                        Confirm Selection
                                    @endif
                                </button>
                            </form>
                            <div class="decline-section text-center">
                                <p>If the terms of this admission are unfavorable and you wish to decline,
                                    please click the button below.</p>
                                <h4 class="font-bold text-danger">Important: Revoking your current admission is a required
                                    step
                                    if you
                                    want to switch to a different course or be eligible for future admission opportunities.
                                </h4>
                                <button id="revoke-admission-button" type="button" class="btn btn-danger">
                                    @if ($session?->id ?? false)
                                        Revoke
                                    @else
                                        Decline
                                    @endif
                                    Admission
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- @endif --}}
                {{-- <div class="text-center mb-4">
                    Slots left: 100
                </div> --}}

                <!-- /.row -->
                <!-- Main row -->

                <!-- /.row (main row) -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->





@endsection
@push('scripts')
    @include('student.decline-admission-js', [
        'id' => $user->userId,
        'returnUrl' => route('student.application-status'),
    ])
@endpush
