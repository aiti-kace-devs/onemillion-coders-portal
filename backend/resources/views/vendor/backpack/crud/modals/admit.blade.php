@once
    @push('styles')
        <link rel="stylesheet" href="{{ url('assets/plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ url('assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @endpush
    @push('after_styles')
        <link rel="stylesheet" href="{{ url('assets/plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ url('assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ url('assets/plugins/select2/js/select2.full.min.js') }}"></script>
        @if (app()->getLocale() !== 'en')
            <script src="{{ url('assets/plugins/select2/js/i18n/' . str_replace('_', '-', app()->getLocale()) . '.js') }}"></script>
        @endif
    @endpush
    @push('after_scripts')
        <script src="{{ url('assets/plugins/select2/js/select2.full.min.js') }}"></script>
        @if (app()->getLocale() !== 'en')
            <script src="{{ url('assets/plugins/select2/js/i18n/' . str_replace('_', '-', app()->getLocale()) . '.js') }}"></script>
        @endif
    @endpush
    @push('styles')
        <style>
            /* Admit modal – Select2 and form styling */
            #admitModal .form-label {
                margin-bottom: 0.375rem;
                font-weight: 500;
            }

            #admitModal .form-group {
                margin-bottom: 1rem;
            }

            /* Modal header close button */
            #admitModal .modal-header .btn-link:hover {
                opacity: 1 !important;
                text-decoration: none;
            }

            /* Input / Select2 selection display – allow wrapping, better visibility */
            #admitModal .select2-container {
                width: 100% !important;
            }

            #admitModal .select2-selection--single {
                min-height: 2.5rem;
                padding: 0.25rem 0.875rem;
                color: #fff;
            }

            #admitModal .select2-selection__rendered {
                max-width: 100%;
                white-space: normal !important;
                line-height: 1.5;
                padding-right: 1.5rem;
                color: #fff;
            }

            #admitModal .select2-selection__arrow {
                top: 50%;
                transform: translateY(-50%);
            }

            /* Dropdown wider, options wrap so full course names are visible */
            #admitModal .select2-dropdown {
                min-width: 100% !important;
                max-width: none !important;
            }

            #admitModal .select2-results__option {
                white-space: normal !important;
                word-wrap: break-word;
                overflow-wrap: break-word;
                padding: 0.5rem 0.75rem;
                line-height: 1.4;
                min-height: 2.5rem;
                display: block;
            }

            #admitModal .select2-results__option--highlighted,
            #admitModal .select2-results__option[aria-selected="true"] {
                white-space: normal !important;
            }

            /* Button row alignment */
            #admitModal .form-group.d-flex {
                margin-bottom: 0;
                margin-top: 1.25rem;
            }
        </style>
    @endpush
@endonce

@php
    $admitFormAction = $form_action ?? route('user.admit-student');
    $formId = $form_id ?? null;
    $userIdInputId = $user_id_input_id ?? 'user_id';
    $changeInputId = $change_input_id ?? 'change';
    $submitBtnId = $submit_btn_id ?? null;
    $submitText = $submit_text ?? __('Admit');
    $showCancel = $show_cancel ?? false;
@endphp

<style>
    #admitModal .select2-selection--single {
        min-height: 2.5rem;
        padding: 0.25rem 0.875rem;
        color: #fff;
    }
    #admitModal .select2-selection__rendered { color: #fff; }
</style>
<div class="modal fade" id="admitModal" tabindex="-1" aria-labelledby="admitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h5 class="modal-title flex-grow-1" id="admitModalLabel">Admit Student</h5>
                <button type="button" class="btn btn-sm btn-link text-muted p-0 m-0 border-0 align-self-center"
                    style="font-size:1.5rem;line-height:1;opacity:.7" data-dismiss="modal" data-bs-dismiss="modal"
                    aria-label="Close" title="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ $admitFormAction }}" name="admit_form" method="POST"
                    @if ($formId) id="{{ $formId }}" @endif>
                    {{ csrf_field() }}
                    <input id="{{ $userIdInputId }}" name="user_id" type="hidden" class="form-control" required>
                    <input id="{{ $changeInputId }}" name="change" value="false" type="hidden" class="form-control"
                        required>
                    <div class="form-group mb-3">
                        <label for="course_id" class="form-label">{{ __('Select Course') }}</label>
                        <select id="course_id" name="course_id" class="form-control" required>
                            <option value="">{{ __('Choose One Course') }}</option>
                            @foreach ($courses ?? [] as $id => $name)
                                <option value="{{ $id }}" @selected((string) $id === (string) old('course_id'))>{{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="session_id" class="form-label">{{ __('Choose Session') }}</label>
                        <select id="session_id" name="session_id" class="form-control"
                            @if (empty($sessions)) disabled @endif>
                            @if (empty($sessions))
                                <option value="">{{ __('No sessions available') }}</option>
                            @else
                                <option value="">{{ __('Choose One Session') }}</option>
                                @foreach ($sessions ?? [] as $session)
                                    <option data-course="{{ $session->course_id }}" value="{{ $session->id }}"
                                        @selected((string) $session->id === (string) old('session_id'))>
                                        {{ $session->name ?? ($session->session ?? $session->id) }}</option>
                                @endforeach
                            @endif
                        </select>
                        @if (empty($sessions))
                            <small
                                class="text-muted">{{ __('Sessions are not configured. Please contact support.') }}</small>
                        @endif
                    </div>
                    <div class="form-group d-flex gap-2">
                        @if ($showCancel)
                            <button type="button" class="btn btn-outline-secondary flex-grow-1" data-dismiss="modal"
                                data-bs-dismiss="modal">Cancel</button>
                        @endif
                        <button class="btn btn-primary flex-grow-1" type="submit"
                            @if ($submitBtnId) id="{{ $submitBtnId }}" @endif
                            @if (empty($sessions)) disabled @endif>{{ $submitText }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const jq = window.jQuery;
            if (!jq || !jq.fn || !jq.fn.select2) return;

            function filterSessionByCourse(modalEl, courseId) {
                const $modal = jq(modalEl);
                $modal.find('#session_id option').each(function() {
                    const dataCourse = jq(this).attr('data-course');
                    jq(this).toggle(!dataCourse || dataCourse === courseId);
                });
            }

            function initAdmitSelect2(modalEl) {
                if (!modalEl) return;
                const $modal = jq(modalEl);
                const $course = $modal.find('#course_id');
                const $session = $modal.find('#session_id');
                if (!$course.length || !$session.length || $session.prop('disabled')) return;

                filterSessionByCourse(modalEl, $course.val());
                if (!$course.hasClass('select2-hidden-accessible')) {
                    $course.select2({
                        dropdownParent: $modal,
                        width: '100%'
                    });
                }
                if (!$session.hasClass('select2-hidden-accessible')) {
                    $session.select2({
                        dropdownParent: $modal,
                        width: '100%'
                    });
                }
            }
            window.initAdmitSelect2 = initAdmitSelect2;

            function destroyAdmitSelect2(modalEl) {
                if (!modalEl) return;
                const $modal = jq(modalEl);
                const $course = $modal.find('#course_id');
                const $session = $modal.find('#session_id');
                if ($course.hasClass('select2-hidden-accessible')) $course.select2('destroy');
                if ($session.hasClass('select2-hidden-accessible')) $session.select2('destroy');
            }

            jq(document).on('shown.bs.modal', '#admitModal', function() {
                initAdmitSelect2(this);
            });

            jq(document).on('hidden.bs.modal', '#admitModal', function() {
                destroyAdmitSelect2(this);
            });

            jq(document).on('change', '#admitModal #course_id', function() {
                const courseId = jq(this).val();
                const $session = jq('#admitModal #session_id');
                if (!$session.length || $session.prop('disabled')) return;
                filterSessionByCourse(document.getElementById('admitModal'), courseId);
                const currentVal = $session.val();
                const isValid = !currentVal || jq('#admitModal #session_id option[value="' + currentVal +
                    '"]:visible').length;
                if (!isValid) $session.val('');
                if ($session.hasClass('select2-hidden-accessible')) {
                    $session.select2('destroy');
                }
                $session.select2({
                    dropdownParent: jq('#admitModal'),
                    width: '100%'
                });
            });
        })();
    </script>
@endpush

@push('after_scripts')
    <script>
        (function() {
            const jq = window.jQuery;
            if (!jq || !jq.fn || !jq.fn.select2) return;

            function filterSessionByCourse(modalEl, courseId) {
                const $modal = jq(modalEl);
                $modal.find('#session_id option').each(function() {
                    const dataCourse = jq(this).attr('data-course');
                    jq(this).toggle(!dataCourse || dataCourse === courseId);
                });
            }

            function initAdmitSelect2(modalEl) {
                if (!modalEl) return;
                const $modal = jq(modalEl);
                const $course = $modal.find('#course_id');
                const $session = $modal.find('#session_id');
                if (!$course.length || !$session.length || $session.prop('disabled')) return;

                filterSessionByCourse(modalEl, $course.val());
                if (!$course.hasClass('select2-hidden-accessible')) {
                    $course.select2({
                        dropdownParent: $modal,
                        width: '100%'
                    });
                }
                if (!$session.hasClass('select2-hidden-accessible')) {
                    $session.select2({
                        dropdownParent: $modal,
                        width: '100%'
                    });
                }
            }
            window.initAdmitSelect2 = initAdmitSelect2;

            function destroyAdmitSelect2(modalEl) {
                if (!modalEl) return;
                const $modal = jq(modalEl);
                const $course = $modal.find('#course_id');
                const $session = $modal.find('#session_id');
                if ($course.hasClass('select2-hidden-accessible')) $course.select2('destroy');
                if ($session.hasClass('select2-hidden-accessible')) $session.select2('destroy');
            }

            jq(document).on('shown.bs.modal', '#admitModal', function() {
                initAdmitSelect2(this);
            });

            jq(document).on('hidden.bs.modal', '#admitModal', function() {
                destroyAdmitSelect2(this);
            });

            jq(document).on('change', '#admitModal #course_id', function() {
                const courseId = jq(this).val();
                const $session = jq('#admitModal #session_id');
                if (!$session.length || $session.prop('disabled')) return;
                filterSessionByCourse(document.getElementById('admitModal'), courseId);
                const currentVal = $session.val();
                const isValid = !currentVal || jq('#admitModal #session_id option[value="' + currentVal +
                    '"]:visible').length;
                if (!isValid) $session.val('');
                if ($session.hasClass('select2-hidden-accessible')) {
                    $session.select2('destroy');
                }
                $session.select2({
                    dropdownParent: jq('#admitModal'),
                    width: '100%'
                });
            });
        })();
    </script>
@endpush
