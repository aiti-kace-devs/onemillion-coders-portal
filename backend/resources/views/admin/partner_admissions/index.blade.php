@extends(backpack_view('blank'))

@php
  $breadcrumbs = [
    trans('backpack::crud.admin') => backpack_url('dashboard'),
    'Partner Admissions' => false,
  ];
@endphp

@section('header')
    <section class="container-fluid">
      <h2>
        <span class="text-capitalize">{{ $title ?? 'Partner Admissions Dashboard' }}</span>
        <small>Monitor and manage student enrollment into partner platforms.</small>
      </h2>
    </section>
@endsection

@section('content')
    {{-- Widgets are automatically injected here by Backpack's layout from the controller --}}
@endsection
