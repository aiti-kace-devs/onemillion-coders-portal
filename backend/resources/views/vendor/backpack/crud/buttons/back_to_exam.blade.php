{{-- Custom Add Question Button --}}
@php
    $examId = request()->get('exam_id');
@endphp

@if ($examId)
    <a href="{{ url('admin/manage-exam') }}" class="btn btn-primary">
        Back To Exams
    </a>
@endif
