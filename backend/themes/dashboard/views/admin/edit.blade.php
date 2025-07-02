@extends('layouts.app')
@section('title', 'Edit Admin')
@section('content')

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Edit Admin</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ url('admin/manage_admins') }}">Manage Admins</a></li>
                            <li class="breadcrumb-item active">Edit Admin</li>
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
                                <div class="card-header">
                                    <h3 class="card-title">Admin Information</h3>
                                </div>

                                <div class="card-body">
                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('admin.admins.update', $admin->id) }}">
                                        @csrf
                                        @method('PUT')

                                        <div class="form-group row">
                                            <label for="name" class="col-md-4 col-form-label text-md-right">Name</label>
                                            <div class="col-md-6">
                                                <input id="name" type="text"
                                                    class="form-control @error('name') is-invalid @enderror" name="name"
                                                    value="{{ old('name', $admin->name) }}" required autocomplete="name"
                                                    autofocus>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="email" class="col-md-4 col-form-label text-md-right">Email
                                                Address</label>
                                            <div class="col-md-6">
                                                <input id="email" type="email"
                                                    class="form-control @error('email') is-invalid @enderror" name="email"
                                                    value="{{ old('email', $admin->email) }}" required autocomplete="email">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="password"
                                                class="col-md-4 col-form-label text-md-right">Password</label>
                                            <div class="col-md-6">
                                                <input id="password" type="password"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    name="password" autocomplete="new-password">
                                                <small class="form-text text-muted">Leave blank to keep current
                                                    password</small>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="password-confirm"
                                                class="col-md-4 col-form-label text-md-right">Confirm Password</label>
                                            <div class="col-md-6">
                                                <input id="password-confirm" type="password" class="form-control"
                                                    name="password_confirmation" autocomplete="new-password">
                                            </div>
                                        </div>

                                        <!-- Roles Section -->
                                        <div class="form-group row">
                                            <label class="col-md-4 col-form-label text-md-right">Roles</label>
                                            <div class="col-md-6">
                                                <select name="roles[]" class="form-control select2" multiple
                                                    data-placeholder="Select roles">
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->name }}"
                                                            {{ $admin->hasRole($role) ? 'selected' : '' }}>
                                                            {{ $role->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-md-4 col-form-label text-md-right">Courses</label>
                                            <div class="col-md-8">
                                                <select name="courses[]" class="form-control select2" multiple
                                                        data-placeholder="Select courses to assign">
                                                    @foreach ($courses as $course)
                                                        <option value="{{ $course->id }}"
                                                            {{ $admin->assignedCourses->contains($course->id) ? 'selected' : '' }}>
                                                            {{ $course->course_name }} ({{ $course->location }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Permissions Section - Grouped by Resource -->
                                        <div class="form-group row">
                                            <label class="col-md-4 col-form-label text-md-right">Permissions</label>
                                            <div class="col-md-8">
                                                <div class="accordion" id="permissionsAccordion">
                                                    @php
                                                        $groupedPermissions = [];
                                                        foreach ($permissions as $permission) {
                                                            $parts = explode('.', $permission->name);
                                                            $resource = $parts[0] ?? 'other';
                                                            $action = $parts[1] ?? $permission->name;
                                                            $groupedPermissions[$resource][] = [
                                                                'id' => $permission->id,
                                                                'name' => $permission->name,
                                                                'action' => $action,
                                                                'selected' => $admin->hasDirectPermission($permission),
                                                            ];
                                                        }
                                                        ksort($groupedPermissions);
                                                    @endphp

                                                    @foreach ($groupedPermissions as $resource => $perms)
                                                        <div class="card">
                                                            <div class="card-header" id="heading{{ $loop->index }}">
                                                                <h2 class="mb-0">
                                                                    <button class="btn btn-link btn-block text-left"
                                                                        type="button" data-toggle="collapse"
                                                                        data-target="#collapse{{ $loop->index }}"
                                                                        aria-expanded="true"
                                                                        aria-controls="collapse{{ $loop->index }}">
                                                                        {{ ucfirst($resource) }} ({{ count($perms) }}
                                                                        permissions)
                                                                    </button>
                                                                </h2>
                                                            </div>

                                                            <div id="collapse{{ $loop->index }}" class="collapse"
                                                                aria-labelledby="heading{{ $loop->index }}"
                                                                data-parent="#permissionsAccordion">
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        @foreach ($perms as $perm)
                                                                            <div class="col-md-4 mb-2">
                                                                                <div class="form-check">
                                                                                    <input class="form-check-input"
                                                                                        type="checkbox" name="permissions[]"
                                                                                        value="{{ $perm['name'] }}"
                                                                                        id="perm_{{ $perm['id'] }}"
                                                                                        {{ $perm['selected'] ? 'checked' : '' }}>
                                                                                    <label class="form-check-label"
                                                                                        for="perm_{{ $perm['id'] }}">
                                                                                        {{ $perm['action'] }}
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                    <div class="mt-2">
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-primary select-all-perms"
                                                                            data-resource="{{ $resource }}">
                                                                            Select All
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-secondary deselect-all-perms"
                                                                            data-resource="{{ $resource }}">
                                                                            Deselect All
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-0">
                                            <div class="col-md-6 offset-md-4">
                                                <button type="submit" class="btn btn-primary">
                                                    Update Admin
                                                </button>
                                                <a href="{{ url('admin/manage_admins') }}"
                                                    class="btn btn-secondary">Cancel</a>
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
    </div>
    <!-- /.content-wrapper -->
@endsection

@push('scripts')
    <script @nonce>
        $(document).ready(function() {
            // Initialize Select2 for roles
            $('.select2').multiSelect();

            // Select all permissions for a resource
            $(document).on('click', '.select-all-perms', function() {
                const resource = $(this).data('resource');
                const collapseId = $(this).closest('.card').find('.collapse').attr('id');
                $(`#${collapseId} input[type="checkbox"][name="permissions[]"]`).prop('checked', true);
            });

            // Deselect all permissions for a resource
            $(document).on('click', '.deselect-all-perms', function() {
                const resource = $(this).data('resource');
                const collapseId = $(this).closest('.card').find('.collapse').attr('id');
                $(`#${collapseId} input[type="checkbox"][name="permissions[]"]`).prop('checked', false);
            });
        });
    </script>
@endpush

@push('styles')
    <style @nonce>
        .accordion .card-header {
            padding: 0.5rem 1rem;
        }

        .accordion .btn-link {
            text-decoration: none;
            color: #495057;
            font-weight: 500;
        }

        .card-body {
            padding: 1rem;
        }
    </style>
@endpush
