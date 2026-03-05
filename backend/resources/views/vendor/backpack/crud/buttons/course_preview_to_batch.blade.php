@if ($crud->hasAccess('show', $entry))
    <a href="{{ backpack_url('course-batch/' . $entry->getKey() . '/show') }}" bp-button="show" class="btn btn-sm btn-link">
        <i class="la la-eye"></i> <span>{{ trans('backpack::crud.preview') }}</span>
    </a>
@endif
