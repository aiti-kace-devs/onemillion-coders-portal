{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i>
        {{ trans('backpack::base.dashboard') }}</a></li>

<x-backpack::menu-item title="Admins" icon="la la-question" :link="backpack_url('admin')" />
<x-backpack::menu-item title="Admission rejections" icon="la la-question" :link="backpack_url('admission-rejection')" />
<x-backpack::menu-item title="App configs" icon="la la-question" :link="backpack_url('app-config')" />
<x-backpack::menu-item title="Attendances" icon="la la-question" :link="backpack_url('attendance')" />
<x-backpack::menu-item title="Branches" icon="la la-question" :link="backpack_url('branch')" />
<x-backpack::menu-item title="Centres" icon="la la-question" :link="backpack_url('centre')" />
<x-backpack::menu-item title="Courses" icon="la la-question" :link="backpack_url('course')" />
<x-backpack::menu-item title="Course sessions" icon="la la-question" :link="backpack_url('course-session')" />
<x-backpack::menu-item title="Email templates" icon="la la-question" :link="backpack_url('email-template')" />
<x-backpack::menu-item title="Forms" icon="la la-question" :link="backpack_url('form')" />
<x-backpack::menu-item title="Form responses" icon="la la-question" :link="backpack_url('form-response')" />
<x-backpack::menu-item title="Oex categories" icon="la la-question" :link="backpack_url('oex-category')" />
<x-backpack::menu-item title="Oex exam masters" icon="la la-question" :link="backpack_url('oex-exam-master')" />
<x-backpack::menu-item title="Oex question masters" icon="la la-question" :link="backpack_url('oex-question-master')" />
<x-backpack::menu-item title="Oex results" icon="la la-question" :link="backpack_url('oex-result')" />
<x-backpack::menu-item title="Periods" icon="la la-question" :link="backpack_url('period')" />
<x-backpack::menu-item title="Programmes" icon="la la-question" :link="backpack_url('programme')" />
<x-backpack::menu-item title="Sms templates" icon="la la-question" :link="backpack_url('sms-template')" />
<x-backpack::menu-item title="Students" icon="la la-question" :link="backpack_url('user')" />
<x-backpack::menu-item title="User admissions" icon="la la-question" :link="backpack_url('user-admission')" />
<x-backpack::menu-item title="User exams" icon="la la-question" :link="backpack_url('user-exam')" />

<x-backpack::menu-dropdown title="Add-ons" icon="la la-puzzle-piece">
    <x-backpack::menu-dropdown-header title="Authentication" />
    <x-backpack::menu-dropdown-item title="Students" icon="la la-user" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="Roles" icon="la la-group" :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="Permissions" icon="la la-key" :link="backpack_url('permission')" />
</x-backpack::menu-dropdown>
