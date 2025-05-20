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
                    <a class="nav-link text-capitalize {{ $i == 0 ? 'active' : '' }}"
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

                                    @if (strtolower($section['type']) !== 'instructors')
                                    <x-question-input
                                        :questions="$section['questions']"
                                        :section="$i" />
                                    @else
                                    <x-question-input
                                        :questions="$section['questions']"
                                        :instructors="$instructors"
                                        :section="$i" />
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