@php
    $extraButtons = $crud->getOperationSetting('rowActionsDropdownExtraButtons') ?? [];
    $hasAnyAction = $crud->hasAccess('update', $entry) || $crud->hasAccess('show', $entry) || $crud->hasAccess('delete', $entry) || !empty($extraButtons);
@endphp
@if ($hasAnyAction)
<div class="dropdown">
    <a class="btn btn-sm btn-outline-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        {{ trans('backpack::crud.actions') }}
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        @if ($crud->hasAccess('update', $entry))
        <li>
            <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/edit') }}">
                <i class="la la-edit me-2 text-primary"></i> {{ trans('backpack::crud.edit') }}
            </a>
        </li>
        @endif
        @if ($crud->hasAccess('show', $entry))
        <li>
            <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/show') }}">
                <i class="la la-eye me-2 text-primary"></i> {{ trans('backpack::crud.preview') }}
            </a>
        </li>
        @endif
        @if ($crud->hasAccess('delete', $entry))
        <li>
            @php
                $deleteBlocked = false;
                $deleteBlockedMessage = null;
                if ($entry instanceof \App\Models\Batch) {
                    $coursesCount = (int) ($entry->courses_count ?? $entry->courses()->count());
                    if ($coursesCount > 0) {
                        $deleteBlocked = true;
                        $deleteBlockedMessage = sprintf(
                            "%d %s already assigned to this batch, so you can't delete it.",
                            $coursesCount,
                            \Illuminate\Support\Str::plural('course', $coursesCount)
                        );
                    }
                }
            @endphp
            <a href="javascript:void(0)"
               onclick="deleteEntry(this)"
               data-route="{{ url($crud->route.'/'.$entry->getKey()) }}"
               @if($deleteBlocked) data-delete-blocked="1" data-delete-blocked-message="{{ $deleteBlockedMessage }}" @endif
               class="dropdown-item text-danger"
               data-button-type="delete">
                <i class="la la-trash me-2"></i> {{ trans('backpack::crud.delete') }}
            </a>
            @once
            {{-- Hidden delete button ensures deleteEntry script is loaded on list page --}}
            <div class="d-none">@include('crud::buttons.delete')</div>
            @endonce
        </li>
        @endif
        @foreach ($extraButtons as $extraView)
        <li>
            @include($extraView)
        </li>
        @endforeach
    </ul>
</div>
@endif
