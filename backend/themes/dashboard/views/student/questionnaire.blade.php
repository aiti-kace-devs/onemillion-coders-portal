@extends('layouts.student')
@section('title', 'Questionnaire')
@section('content')


    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Questionnaire</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Questionnaire</li>
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
                                    <table class="table table-striped table-bordered table-hover datatable mw-100">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($questionnaires as $questionnaire)
                                                <tr>
                                                    <td>{{ $questionnaire['title'] }}</td>
                                                    <td>
                                                        @if ($questionnaire['is_submitted'])
                                                            <span class="badge badge-success">Complete</span>
                                                        @else
                                                            <span class="badge badge-danger">Incomplete</span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <x-can-take-questionnaire :submitted="$questionnaire['is_submitted']">
                                                            @if (!$questionnaire['is_submitted'])
                                                                <a href="{{ route('student.questionnaire.take_questionnaire', $questionnaire->code) }}"
                                                                    class="btn btn-primary btn-sm">Assess Now</a>
                                                            @endif
                                                        </x-can-take-questionnaire>
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
