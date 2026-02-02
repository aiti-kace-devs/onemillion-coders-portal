@php
    // Check admission status
    $admitted = $entry->admissions()->whereNotNull('session')->exists();
    $pending = $entry->admissions()->whereNull('session')->exists();
@endphp

@if (!$admitted && !$pending)
    {{-- Not Admitted - Show Admit Button --}}
    <a class="dropdown-item admit-btn" href="javascript:void(0)" role="button" tabindex="0" data-user-id="{{ $entry->getKey() }}">
        <i class="la la-user-edit text-primary"></i>Admit
    </a>
@else
<div class="d-flex flex-column align-items-start" style="min-width: 160px;">
    <a class="dropdown-item admit-btn" href="javascript:void(0)" role="button" tabindex="0" data-user-id="{{ $entry->getKey() }}">
        <i class="la la-user-edit text-primary"></i> Change Admission
    </a>
    <a class="dropdown-item choose-session-btn" href="javascript:void(0)" role="button" tabindex="0" data-user-id="{{ $entry->getKey() }}">
        <i class="la la-calendar text-success"></i> Choose Session
    </a>
    <a class="dropdown-item delete-admission-btn" href="javascript:void(0)" role="button" tabindex="0" data-user-id="{{ $entry->getKey() }}">
        <i class="la la-trash text-danger"></i> Delete Admission
    </a>
</div>
@endif

