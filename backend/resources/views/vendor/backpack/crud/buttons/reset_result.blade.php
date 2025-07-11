@if($entry->examResults && $entry->examResults->count() > 0)
<a href="{{ url('admin/reset-exam/' . ($entry->exam_id ?? 0) . '/' . $entry->getKey()) }}" class="dropdown-item">
    <i class="la la-redo text-primary"></i> Reset Result
</a>
@endif
