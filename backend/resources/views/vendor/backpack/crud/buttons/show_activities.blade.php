@if ($crud->hasAccess('show'))
    <a href="{{ route('user.activities', ['user_id' => $entry->getKey()]) }}" class="btn btn-sm btn-link" title="Show Activities">
        <i class="la la-history"></i> <span class="d-none d-lg-inline">Show Activities</span>
    </a>
@endif
