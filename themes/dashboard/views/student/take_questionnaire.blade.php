@extends('layouts.student')
@section('title', 'Questionnaire')
@section('content')

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Questionnaire</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Questionnaire</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>

    <div class="container my-4">
        <div class="mb-4">
            <h3 class="h4 font-weight-bold text-capitalize">{{ $questionnaire['title'] }}</h3>
            @if(!empty($questionnaire['description']))
            <p class="text-muted small">{{ $questionnaire['description'] }}</p>
            @endif
        </div>

        <div class="row">
            <div class="col-12 col-md-3 mb-3">
                <!-- Vertical nav tabs for mobile responsiveness -->
                <div class="nav flex-column nav-pills" id="sectionTabs" role="tablist">
                    @foreach ($questionnaire['schema'] as $i => $section)
                    <a class="nav-link {{ $i == 0 ? 'active' : '' }}"
                        id="tab-{{ $i }}"
                        data-toggle="pill"
                        href="#section-{{ $i }}"
                        role="tab">
                        {{ $section['title'] }}
                    </a>
                    @endforeach
                </div>
            </div>

            <div class="col-12 col-md-9">
                <div class="tab-content" id="sectionContent">
                    @foreach ($questionnaire['schema'] as $i => $section)
                    <div class="tab-pane fade {{ $i == 0 ? 'show active' : '' }}"
                        id="section-{{ $i }}"
                        role="tabpanel">
                        <div class="card mb-4">
                            <div class="card-body">
                                @if (!empty($section['description']))
                                <p class="text-muted small">{{ $section['description'] }}</p>
                                @endif

                                <form
                                    method="POST"
                                    class="questionnaireForm"
                                    action="{{ route('student.questionnaire.store',  $questionnaire->code) }}"
                                    enctype="multipart/form-data">
                                    @csrf

                                    <input type="hidden" name="section" value="{{ $i }}">

                                    {{-- GENERAL SECTION QUESTIONS --}}
@if (strtolower($section['title']) !== 'instructors')
    @foreach ($section['questions'] as $index => $question)
    <x-question-input
        :fieldName="'response_data[' . $question['field_name'] . ']'"
        :fieldId="'field-' . $i . '-' . $index"
        :question="$question"
        />
    @endforeach

@else
    {{-- INSTRUCTOR-SPECIFIC QUESTIONS --}}
    @foreach ($instructors as $insIndex => $instructor)
        <h5 class="mt-4 mb-3">Instructor: {{ $instructor->name }}</h5>
        @foreach ($section['questions'] as $index => $question)
            @include('partials.question-input', [
                'fieldName' => "response_data[instructors][{$instructor->id}][{$question['field_name']}]",
                'fieldId' => "field-instructor-{$insIndex}-{$index}",
                'question' => $question,
            ])
        @endforeach
    @endforeach
@endif


                                    @foreach ($section['questions'] as $index => $question)
                                    <div class="form-group">
                                        <div>
                                            <label class="h5 font-weight-normal" for="field-{{ $i }}-{{ $index }}">
                                                {{ $question['title'] }}
                                                @if($question['validators']['required'])
                                                <span class="text-danger">*</span>
                                                @endif
                                            </label>
                                        </div>

                                        @php
                                        $fieldName = "response_data[{$question['field_name']}]";
                                        $fieldId = "field-{$i}-{$index}";
                                        $required = $question['validators']['required'] ? 'required' : '';
                                        $options = isset($question['options']) ? explode(',', $question['options']) : [];
                                        @endphp

                                        {{-- Input types --}}
                                        @if (in_array($question['type'], ['text', 'email', 'number', 'password']))
                                        <input type="{{ $question['type'] }}"
                                            name="{{ $fieldName }}"
                                            id="{{ $fieldId }}"
                                            class="form-control"
                                            placeholder="{{ $question['title'] }}">
                                        @elseif ($question['type'] === 'file')
                                        <input type="file"
                                            name="{{ $fieldName }}"
                                            id="{{ $fieldId }}"
                                            class="form-control-file">
                                        @elseif ($question['type'] === 'select')
                                        <select name="{{ $fieldName }}"
                                            id="{{ $fieldId }}"
                                            class="form-control">
                                            <option value="" disabled selected>-- Select an option --</option>
                                            @foreach ($options as $option)
                                            <option value="{{ trim($option) }}">{{ ucfirst(trim($option)) }}</option>
                                            @endforeach
                                        </select>
                                        @elseif ($question['type'] === 'phonenumber')
                                        <input type="tel"
                                            name="{{ $fieldName }}"
                                            id="{{ $fieldId }}"
                                            class="form-control"
                                            placeholder="{{ $question['title'] }}">
                                        @elseif ($question['type'] === 'checkbox')
                                        @foreach ($options as $idx => $option)
                                        <div class="form-check">
                                            <input type="checkbox"
                                                class="form-check-input"
                                                name="{{ $fieldName }}[]"
                                                id="{{ $fieldId }}-opt-{{ $idx }}"
                                                value="{{ trim($option) }}">
                                            <label class="form-check-label" for="{{ $fieldId }}-opt-{{ $idx }}">
                                                {{ ucfirst(trim($option)) }}
                                            </label>
                                        </div>
                                        @endforeach
                                        @elseif ($question['type'] === 'radio')
                                        @foreach ($options as $idx => $option)
                                        <div class="form-check form-check-inline">
                                            <input type="radio"
                                                class="form-check-input"
                                                name="{{ $fieldName }}"
                                                id="{{ $fieldId }}-opt-{{ $idx }}"
                                                value="{{ trim($option) }}">
                                            <label class="form-check-label" for="{{ $fieldId }}-opt-{{ $idx }}">
                                                {{ ucfirst(trim($option)) }}
                                            </label>
                                        </div>
                                        @endforeach
                                        @elseif ($question['type'] === 'select_course')
                                        <p class="text-muted">[Select Course Component Placeholder]</p>
                                        @endif

                                        @if (!empty($question['description']))
                                        <small class="form-text text-info">{{ $question['description'] }}</small>
                                        @endif
                                        <span class="{{ str_replace(['[', ']'], ['_', ''], $fieldName)}}_error font-weight-bold invalid-feedback" style="display: block;" role="alert"></span>
                                    </div>
                                    @endforeach

                                    @if (strtolower($section['title']) === 'instructors')
                                   @foreach ($instructors as $instructor)
                                   <div class="form-group">
                                        <div>
                                            <label class="h5 font-weight-normal" for="field-{{ $i }}-{{ $index }}">
                                                {{ $question['title'] }}
                                                @if($question['validators']['required'])
                                                <span class="text-danger">*</span>
                                                @endif
                                            </label>
                                        </div>

                                        @php
                                        $fieldName = "response_data[{$question['field_name']}]";
                                        $fieldId = "field-{$i}-{$index}";
                                        $required = $question['validators']['required'] ? 'required' : '';
                                        $options = isset($question['options']) ? explode(',', $question['options']) : [];
                                        @endphp

                                        {{-- Input types --}}
                                        @if (in_array($question['type'], ['text', 'email', 'number', 'password']))
                                        <input type="{{ $question['type'] }}"
                                            name="{{ $fieldName }}"
                                            id="{{ $fieldId }}"
                                            class="form-control"
                                            placeholder="{{ $question['title'] }}">
                                        @elseif ($question['type'] === 'file')
                                        <input type="file"
                                            name="{{ $fieldName }}"
                                            id="{{ $fieldId }}"
                                            class="form-control-file">
                                        @elseif ($question['type'] === 'select')
                                        <select name="{{ $fieldName }}"
                                            id="{{ $fieldId }}"
                                            class="form-control">
                                            <option value="" disabled selected>-- Select an option --</option>
                                            @foreach ($options as $option)
                                            <option value="{{ trim($option) }}">{{ ucfirst(trim($option)) }}</option>
                                            @endforeach
                                        </select>
                                        @elseif ($question['type'] === 'phonenumber')
                                        <input type="tel"
                                            name="{{ $fieldName }}"
                                            id="{{ $fieldId }}"
                                            class="form-control"
                                            placeholder="{{ $question['title'] }}">
                                        @elseif ($question['type'] === 'checkbox')
                                        @foreach ($options as $idx => $option)
                                        <div class="form-check">
                                            <input type="checkbox"
                                                class="form-check-input"
                                                name="{{ $fieldName }}[]"
                                                id="{{ $fieldId }}-opt-{{ $idx }}"
                                                value="{{ trim($option) }}">
                                            <label class="form-check-label" for="{{ $fieldId }}-opt-{{ $idx }}">
                                                {{ ucfirst(trim($option)) }}
                                            </label>
                                        </div>
                                        @endforeach
                                        @elseif ($question['type'] === 'radio')
                                        @foreach ($options as $idx => $option)
                                        <div class="form-check form-check-inline">
                                            <input type="radio"
                                                class="form-check-input"
                                                name="{{ $fieldName }}"
                                                id="{{ $fieldId }}-opt-{{ $idx }}"
                                                value="{{ trim($option) }}">
                                            <label class="form-check-label" for="{{ $fieldId }}-opt-{{ $idx }}">
                                                {{ ucfirst(trim($option)) }}
                                            </label>
                                        </div>
                                        @endforeach
                                        @elseif ($question['type'] === 'select_course')
                                        <p class="text-muted">[Select Course Component Placeholder]</p>
                                        @endif

                                        @if (!empty($question['description']))
                                        <small class="form-text text-info">{{ $question['description'] }}</small>
                                        @endif
                                        <span class="{{ str_replace(['[', ']'], ['_', ''], $fieldName)}}_error font-weight-bold invalid-feedback" style="display: block;" role="alert"></span>
                                    </div>
                                   
                                   @endforeach
                                    @endif

                                    <div class="form-group mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            {{ $i == count($questionnaire['schema']) - 1 ? 'Submit' : 'Save & Next' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endsection

    @push('scripts')
    <script>
        document.querySelectorAll('.questionnaireForm').forEach(questionnaire => {
            questionnaire.addEventListener('submit', function(event) {
                event.preventDefault();
                const form = this;

                $.ajax({
                    type: $(form).attr('method'),
                    url: $(form).attr('action'),
                    data: $(form).serialize(),
                    success: function(response) {
                        // Clear previous errors
                        $(':input', form).removeClass('is-invalid');
                        $('.invalid-feedback', form).text("");

                        if (response.status) {
                            // Handle completion
                            if (response.progress.is_submitted) {
                                window.location.href = response.progress.redirect_url;
                            } else {
                                $('.tab-pane').removeClass('show active');
                                $(`#section-${response.progress.next_section}`).addClass('show active');
                                $(`a[href="#section-${response.progress.next_section}"]`).tab('show');
                            }
                        }
                    },
                    error: function(xhr) {
                        $(':input', form).removeClass('is-invalid');
                        $('.invalid-feedback', form).text("");

                        if (xhr.status === 422) {
                            const response = xhr.responseJSON;

                            if (response.error) {
                                $.each(response.error, function(prefix, val) {
                                    const fieldId = prefix.replace('.', '_');
                                    $(`#${fieldId}`, form).addClass('is-invalid');
                                    $(`.${fieldId}_error`, form).text(val[0]);
                                });
                            }
                            toastr.error(response.msg || 'Validation failed');
                        } else {
                            toastr.error("An error occurred: " + xhr.statusText);
                        }
                    }
                });

            });
        });
    </script>

    @endpush