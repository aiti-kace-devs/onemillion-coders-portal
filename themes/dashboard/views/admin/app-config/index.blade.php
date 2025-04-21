@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">App Settings</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">App Settings</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Application Settings</h3>
                            </div>
                            <form role="form" action="{{ route('admin.config.update') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="card-body">
                                    <div class="row">
                                        @foreach ($configs as $config)
                                            <div class="form-group col-md-4">
                                                @if ($config->type == 'boolean')
                                                    <div class="form-check form-check-inline">
                                                        <input type="checkbox" id="{{ $config->key }}" data-on="Yes"
                                                            data-style="slow" data-off="No" name="{{ $config->key }}"
                                                            {{ $config->value ? 'checked' : '' }} data-toggle="toggle"
                                                            data-onstyle="success">
                                                        <label class="text-sm"
                                                            for="{{ $config->key }}">&nbsp;&nbsp;{{ ucwords(str_replace('_', ' ', $config->key)) }}</label>
                                                    </div>
                                                @elseif ($config->type == 'textarea')
                                                    <label
                                                        for="{{ $config->key }}">{{ ucwords(str_replace('_', ' ', $config->key)) }}</label>
                                                    <textarea class="form-control" id="{{ $config->key }}" name="{{ $config->key }}" rows="3">{{ $config->value }}</textarea>
                                                @else
                                                    <label
                                                        for="{{ $config->key }}">{{ ucwords(str_replace('_', ' ', $config->key)) }}</label>
                                                    <input type="{{ $config->type }}" class="form-control"
                                                        id="{{ $config->key }}" name="{{ $config->key }}"
                                                        value="{{ $config->value }}">
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    @push('styles')
        {{-- <link rel="stylesheet" href="{{ url('/assets/plugins/bootstrap-switch/css/bootstrap3/bootstrap-switch.min.css') }}"> --}}
        <link href="{{ url('assets/plugins/bootstrap4-toogle/bootstrap4-toggle.min.css') }}" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="{{ url('/assets/plugins/bootstrap4-toggle/bootstrap4-toggle.min.js') }}"></script>
        {{-- <script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script> --}}
    @endpush
@endsection
