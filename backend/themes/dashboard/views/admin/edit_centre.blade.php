@extends('layouts.app', [
    'activePage' => 'manageCentre',
])
@section('title', 'Edit Centre')
@section('content')

    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Edit Centre</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit Centre</li>
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
                                                                @if ($centre->branch->id === $branch->id) selected @endif>
                                                                {{ $branch->title }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="branch_id_error font-weight-bold invalid-feedback block"
                                                        role="alert"></span>
                                                </div>
                                            </div>

                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="">Enter centre title</label>

                                                    <input type="text" value="{{ $centre->title }}" name="title"
                                                        placeholder="Enter centre title" class="form-control"
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
            const manageAction = "{{ route('admin.centre.update', $centre) }}";
            const method = 'PUT';
        </script>
    @endsection
