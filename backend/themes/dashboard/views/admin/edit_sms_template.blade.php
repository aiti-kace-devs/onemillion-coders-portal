@extends('layouts.app', [
    'activePage' => 'manageSms',
])
@section('title', 'Edit SMS Template')
@section('content')

    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Edit SMS Template</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit SMS Template</li>
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
                                                    <label for="">Enter Sms Name</label>

                                                    <input type="text" value="{{ $template->name }}" name="name"
                                                        placeholder="Enter sms name" class="form-control" id="name">
                                                    <span class="name_error font-weight-bold invalid-feedback block"
                                                        role="alert"></span>
                                                </div>
                                            </div>

                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="">Enter Content</label>

                                                    <textarea name="content" id="content" class="form-control" rows="5" required>{{ $template->content }}</textarea>
                                                    <small class="form-text text-muted">
                                                        Use placeholders like {name}, {date} etc. that can be replaced when
                                                        sending.
                                                    </small>
                                                    <span class="content_error font-weight-bold invalid-feedback block"
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
            const manageAction = "{{ route('admin.sms.template.update', $template) }}";
            const method = 'PUT';
        </script>
    @endsection
