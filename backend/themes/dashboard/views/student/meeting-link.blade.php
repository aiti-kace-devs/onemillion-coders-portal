@extends('layouts.student')
@section('title', 'Meetig Link')
@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Meeting Link</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active">Meeting Link</li>
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
                <h3>Meeting Link For: {{ $session->name }}</h3>
                @if ($session->link)
                    <a class="btn btn-info btn-lg" href="{{ $session->link }}" target="_blank">Join Meeting</a>
                    <br>
                    <br>
                    <p>If button is not working copy this link: {{ $session->link }}</p>
                @else
                    <p class="text-danger">No Meeting Link Available at this momnet, Please try again later. (30 mins to
                        the start of the
                        class)</p>
                @endif


                <!-- /.row -->
                <!-- Main row -->

                <!-- /.row (main row) -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
@endsection
