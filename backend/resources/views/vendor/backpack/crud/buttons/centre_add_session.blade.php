@php
    $canView = backpack_user() && (backpack_user()->can('centre.read.all') || backpack_user()->can('centre.read.self'));
    $canManage = $canView && ! (method_exists(backpack_user(), 'hasRole') && backpack_user()->hasRole('centre-manager'));
@endphp

@if ($canManage)
    <a
        class="dropdown-item"
        href="javascript:void(0)"
        onclick="openAddCentreSessionModal(this)"
        data-centre-id="{{ $entry->getKey() }}"
        data-centre-name="{{ e($entry->title ?? ('Centre #' . $entry->getKey())) }}"
        data-fetch-url="{{ backpack_url('centre/' . $entry->getKey() . '/sessions') }}"
        data-save-url="{{ backpack_url('centre/' . $entry->getKey() . '/sessions') }}"
    >
        <i class="la la-clock-o me-2 text-primary"></i> Manage Sessions
    </a>
@endif
