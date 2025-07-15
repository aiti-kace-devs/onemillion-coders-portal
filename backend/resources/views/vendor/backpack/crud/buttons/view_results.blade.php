@if($entry->examResults && $entry->examResults->count() > 0)
<a href="{{ url('admin/admin_view_result/' . $entry->getKey()) }}" class="dropdown-item" target="_blank">
    <i class="la la-poll text-primary"></i> View Results
</a>
@endif
