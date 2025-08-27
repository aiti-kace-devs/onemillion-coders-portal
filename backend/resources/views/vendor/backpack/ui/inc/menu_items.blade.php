{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item">
    <a class="nav-link" href="{{ backpack_url('dashboard') }}">
        <i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}
    </a>
</li>
@can('admin.read.all')
    <x-backpack::menu-dropdown title="Account" icon="la la-user-lock">
        @can('admin.read.all')
            <x-backpack::menu-dropdown-item title="Admins" icon="la la-user-shield" :link="backpack_url('admin')" />
        @endcan
        @can('role.read.all')
            <x-backpack::menu-dropdown-item title="Roles" icon="la la-user-tag" :link="backpack_url('role')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcan

@can('batch.read.all')
    <x-backpack::menu-item title="Admission Batches" icon="la la-users" :link="backpack_url('batch')" />
@endcan


@can('filemanager.read.all')
    <x-backpack::menu-item :title="trans('backpack::crud.file_manager')" icon="la la-folder-open" :link="backpack_url('elfinder')" />
@endcan

@can('branch.read.all')
    <x-backpack::menu-item title="Manage Branches" icon="la la-code-branch" :link="backpack_url('branch')" />
@endcan



@can('centre.read.all')
    <x-backpack::menu-item title="Manage Centres" icon="la la-building" :link="backpack_url('centre')" />
@endcan

@can('programme.read.all')
    <x-backpack::menu-item title="Manage Programmes" icon="la la-graduation-cap" :link="backpack_url('programme')" />
@endcan

@can('course.read.all')
    <x-backpack::menu-dropdown title="Course Moderation" icon="la la-book-reader">
        @can('course.read.all')
            <x-backpack::menu-dropdown-item title="Courses" icon="la la-book" :link="backpack_url('course')" />
        @endcan
        @can('course-session.read.all')
            <x-backpack::menu-dropdown-item title="Course Sessions" icon="la la-clock" :link="backpack_url('course-session')" />
        @endcan
        @can('course-category.read.all')
            <x-backpack::menu-dropdown-item title="Course Categories" icon="la la-layer-group" :link="backpack_url('course-category')" />
        @endcan
        @can('course-module.read.all')
            <x-backpack::menu-dropdown-item title="Course Modules" icon="la la-puzzle-piece" :link="backpack_url('course-module')" />
        @endcan
        @can('course-certification.read.all')
            <x-backpack::menu-dropdown-item title="Course Certifications" icon="la la-certificate" :link="backpack_url('course-certification')" />
        @endcan
        @can('course-match.read.all')
            <x-backpack::menu-dropdown-item title="Course Matches" icon="la la-link" :link="backpack_url('course-match')" />
        @endcan
        @can('course-match-option.read.all')
            <x-backpack::menu-dropdown-item title="Course Match options" icon="la la-list-ul" :link="backpack_url('course-match-option')" />
        @endcan
    </x-backpack::menu-dropdown>
@endcan


@can('attendance.read.all')
    <x-backpack::menu-item title="View Attendances" icon="la la-calendar-check" :link="backpack_url('attendance')" />
@endcan

@can('form.read.all')
    <x-backpack::menu-item title="Registration Form" icon="la la-wpforms" :link="backpack_url('form')" />
@endcan

@can('category.read.all')
    <x-backpack::menu-item title="Manage Exam Categories" icon="la la-layer-group" :link="backpack_url('category')" />
@endcan

@can('manage-exam.read.all')
    <x-backpack::menu-item title="Manage Exams" icon="la la-file-signature" :link="backpack_url('manage-exam')" />
@endcan

@can('qr-scanner.read.all')
    <x-backpack::menu-item title="Scan or Generate QR Code" icon="la la-qrcode" :link="backpack_url('qr-scanner')" />
@endcan

@can('student.read.all')
    <x-backpack::menu-item title="Students" icon="la la-user-graduate" :link="backpack_url('user')" />
@endcan


@can('student-verification.read.all')
    <x-backpack::menu-item title="Student Verifications" icon="la la-check-circle" :link="backpack_url('student-verification')" />
@endcan


@can('email-template.read.all')
    <x-backpack::menu-item title="Email templates" icon="la la-envelope-open-text" :link="backpack_url('email-template')" />
@endcan

@can('sms-template.read.all')
    <x-backpack::menu-item title="Sms templates" icon="la la-sms" :link="backpack_url('sms-template')" />
@endcan

@can('app-config.read.all')
    <x-backpack::menu-item title="App configs" icon="la la-cogs" :link="backpack_url('app-config')" />
@endcan

{{-- Only show dropdown if user has at least one permission for the dropdown items --}}
@if (auth()->user()->can('form-response.read.all') ||
        auth()->user()->can('oex-result.read.all') ||
        auth()->user()->can('period.read.all'))
    <!-- <x-backpack::menu-item title="Form responses" icon="la la-reply" :link="backpack_url('form-response')" /> -->
@endif

{{-- <x-backpack::menu-item title="Results" icon="la la-chart-bar" :link="backpack_url('oex-result')" /> --}}
<!-- <x-backpack::menu-item title="Periods" icon="la la-calendar" :link="backpack_url('period')" /> -->

{{-- <x-backpack::menu-item title="User Admissions" icon="la la-user-check" :link="backpack_url('user-admission')" /> --}}
{{-- <x-backpack::menu-item title="User exams" icon="la la-file" :link="backpack_url('user-exam')" /> --}}
<!-- <x-backpack::menu-item title="Question Masters" icon="la la-question-circle" :link="backpack_url('question-master')" /> -->
<!-- <x-backpack::menu-item title="Results" icon="la la-chart-bar" :link="backpack_url('oex-result')" /> -->
<!-- <x-backpack::menu-item title="Periods" icon="la la-calendar-alt" :link="backpack_url('period')" /> -->
<!-- <x-backpack::menu-item title="Sms templates" icon="la la-sms" :link="backpack_url('sms-template')" /> -->
<!-- <x-backpack::menu-item title="User admissions" icon="la la-user-check" :link="backpack_url('user-admission')" /> -->
<!-- <x-backpack::menu-item title="User exams" icon="la la-clipboard-list" :link="backpack_url('user-exam')" /> -->
<!-- <x-backpack::menu-item title="Admission rejections" icon="la la-times-circle" :link="backpack_url('admission-rejection')" /> -->

{{-- Only show dropdown if user has at least one permission for the dropdown items --}}
@if (auth()->user()->can('student.read.all') ||
        auth()->user()->can('role.read.all') ||
        auth()->user()->can('permission.read.all'))
    <!-- <x-backpack::menu-dropdown title="Add-ons" icon="la la-puzzle-piece">
    <x-backpack::menu-dropdown-header title="Authentication" />
    <x-backpack::menu-dropdown-item title="Students" icon="la la-user-graduate" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="Roles" icon="la la-user-tag" :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="Permissions" icon="la la-key" :link="backpack_url('permission')" />
</x-backpack::menu-dropdown> -->
@endif


{{-- <x-backpack::menu-item :title="trans('backpack::crud.file_manager')" icon="la la-files-o" /> --}}
