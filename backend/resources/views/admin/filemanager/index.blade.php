@extends(backpack_view('blank'))

<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    $breadcrumbs = [
        trans('backpack::crud.admin') => backpack_url(),
        'File Manager' => false,
    ];
@endphp

@section('header')
    <section class="container-fluid" bp-section="page-header">
        <h1 bp-section="page-heading" class="text-capitalize">File Manager</h1>
    </section>
@endsection

@section('content')
    <div id="fm" style="height: 600px;"></div>
@endsection

@section('after_styles')
    @basset('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css')
    @basset('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/fonts/bootstrap-icons.woff2')
    @basset('vendor/file-manager/css/file-manager.css')
@endsection

@section('after_scripts')
    @basset('vendor/file-manager/js/file-manager.js')
@endsection
