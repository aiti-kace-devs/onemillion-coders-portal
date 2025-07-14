{{-- Custom Add Question Button --}}
@php
    $examId = request()->get('exam_id');
@endphp

@if ($examId)
    <a href="{{ url('admin/question-master/create?exam_id=' . $examId) }}" class="btn btn-primary">
        <i class="la la-plus"></i> Add Question
    </a>
@endif
