@extends('layouts.student')
@section('title', 'My Profile')
@section('content')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">{{ $user->name }}'s Profile</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ url('student/dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active">Profile</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

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

                <!-- Personal Information Card -->
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title">Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- <div class="col-md-3 text-center mb-4">
                                {{-- <img src="{{ asset('assets/images/user-profile.png') }}" alt="User profile picture" class="img-circle elevation-2" style="width: 150px; height: 150px;"> --}}
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-primary">
                                        <i class="fas fa-camera"></i> Change Photo
                                    </button>
                                </div>
                            </div> -->
                            <div class="col-md-9">
                                <div class="row mb-3">
                                    <div class="col-md-3 font-weight-bold">Full Name:</div>
                                    <div class="col-md-9">{{ $user->name }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3 font-weight-bold">Email:</div>
                                    <div class="col-md-9">{{ $user->email }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3 font-weight-bold">Mobile Number:</div>
                                    <div class="col-md-9">{{ $user->mobile_no }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-3 font-weight-bold">Status:</div>
                                    <div class="col-md-9">
                                        <span class="badge badge-warning">Pending</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Information Card -->
                <!-- Course Information Card -->
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title">Course Information</h3>
                    </div>
                    <div class="card-body">
                        @if (!empty($user->exam) && $course)
                            <div class="row mb-3">
                                <div class="col-md-3 font-weight-bold">Course Name:</div>
                                <div class="col-md-9">{{ $course->course_name }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3 font-weight-bold">Location:</div>
                                <div class="col-md-9">{{ $course->location }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3 font-weight-bold">Course Duration:</div>
                                <div class="col-md-9">{{ $course->duration }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3 font-weight-bold">Start Date:</div>
                                <div class="col-md-9">{{ $course->start_date }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3 font-weight-bold">End Date:</div>
                                <div class="col-md-9">{{ $course->end_date }}</div>
                            </div>

                            <!-- <div class="row mb-3">
                    <div class="col-md-3 font-weight-bold">Session:</div>
                    <div class="col-md-9">{{ $user->status }}</div>
                </div> -->
                            @unless ($user->admission)
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <a href="{{ route('student.change-course') }}" class="btn btn-primary">
                                            <i class="fas fa-exchange-alt"></i> Change Course
                                        </a>
                                    </div>
                                </div>
                            @endunless
                        @else
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                <h5>Oops! No course selected</h5>
                            </div>
                            <!-- <div class="row mt-3">
                    <div class="col-12 text-center">
                        <a href="{{ route('student.change-course') }}" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Select a Course
                        </a>
                    </div>
                </div> -->
                        @endif
                    </div>
                </div>

            @endsection
