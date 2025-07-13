@php
    // Get the latest exam result for this entry
    $latestResult = $entry->examResults()->latest()->first();
    $examId = $latestResult ? $latestResult->exam_id : null;
@endphp
@if($examId)
<a href="{{ route('results.reset', [$examId, $entry->getKey()]) }}" class="dropdown-item">
    <i class="la la-redo text-primary"></i> Reset Result
</a>
@endif
