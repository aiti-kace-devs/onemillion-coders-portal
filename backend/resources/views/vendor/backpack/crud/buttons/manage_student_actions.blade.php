@php
    $userId = $entry->getKey();
    $hasAdmission = $entry->admissions()->whereNotNull('session')->exists();
    $hasPendingAdmission = $entry->admissions()->whereNull('session')->exists();
    $hasAnyAdmission = $hasAdmission || $hasPendingAdmission;
    $hasExamResults = $entry->examResults && $entry->examResults->count() > 0;
    $latestResult = $entry->examResults()->latest()->first();
    $examId = $latestResult ? $latestResult->exam_id : null;
    
    // Get current admission info for change/edit
    $currentAdmission = $entry->admissions()->first();
    $currentCourseId = $currentAdmission?->course_id;
    $currentSessionId = $currentAdmission?->session;
@endphp
<div class="btn-group flex-wrap" role="group">
    {{-- 1. Admit - Direct admission without modal --}}
    @if (!$hasAnyAdmission)
        <button type="button" class="btn btn-sm btn-primary admit-btn" 
                data-user-id="{{ $userId }}">
            <i class="la la-user-plus"></i> Admit
        </button>
    @else
        {{-- 2. Change Admission --}}
        <button type="button" class="btn btn-sm btn-outline-primary admit-btn" 
                data-user-id="{{ $userId }}"
                data-is-change="true"
                data-current-course="{{ $currentCourseId }}"
                data-current-session="{{ $currentSessionId }}">
            <i class="la la-user-edit"></i> Change Admission
        </button>
        {{-- 3. Delete Admission --}}
        <button type="button" class="btn btn-sm btn-outline-danger delete-admission-btn" 
                data-user-id="{{ $userId }}">
            <i class="la la-trash"></i> Delete Admission
        </button>
        {{-- 4. Choose Session --}}
        <button type="button" class="btn btn-sm btn-outline-success choose-session-btn" 
                data-user-id="{{ $userId }}"
                data-current-course="{{ $currentCourseId }}"
                data-current-session="{{ $currentSessionId }}">
            <i class="la la-calendar"></i> Choose Session
        </button>
    @endif
    {{-- 5. View Results --}}
    @if($hasExamResults)
        <a href="{{ url('admin/admin_view_result/' . $userId) }}" class="btn btn-sm btn-outline-info" target="_blank">
            <i class="la la-poll"></i> View Results
        </a>
        {{-- 6. Reset Results --}}
        @if($examId)
            <a href="{{ route('results.reset', [$examId, $userId]) }}" class="btn btn-sm btn-outline-warning" onclick="return confirm('Are you sure you want to reset this student\'s exam result?');">
                <i class="la la-redo"></i> Reset Results
            </a>
        @endif
    @endif
</div>
