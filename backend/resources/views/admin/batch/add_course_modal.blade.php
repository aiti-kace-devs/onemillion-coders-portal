{{-- Modal for adding courses to a batch --}}
<div class="modal fade" id="addCourseModal" tabindex="-1" role="dialog" aria-labelledby="addCourseModalLabel">
    <div class="modal-dialog modal-lg" role="document" style="margin-top: 50px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCourseModalLabel">Add Course to Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            @php $batchId = $batch?->id ?? 0; @endphp
            <div id="addCourseForm" data-action="{{ backpack_url('batch/add-courses/' . $batchId) }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" name="batch_id" value="{{ $batchId }}">
                        
                        <div class="form-group col-md-12">
                            <label>Branch *</label>
                            <select name="branch_id" id="modal_branch_id" class="form-control" required>
                                <option value="">Select Branch</option>
                                @foreach($branches as $id => $title)
                                    <option value="{{ $id }}">{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group col-md-12">
                            <label>Training Centres *</label>
                            <select name="centre_ids[]" id="modal_centre_ids" class="form-control" multiple required>
                                <option value="">Select a branch first</option>
                            </select>
                            <small class="form-text text-muted">Select one or more training centres.</small>
                        </div>
                        
                        <div class="form-group col-md-12">
                            <label>Programmes *</label>
                            <select name="programme_ids[]" id="modal_programme_ids" class="form-control" multiple required>
                                @foreach($programmes as $programme)
                                    <option
                                        value="{{ $programme->id }}"
                                        data-start-date="{{ $programme->start_date }}"
                                        data-end-date="{{ $programme->end_date }}"
                                    >
                                        {{ $programme->title }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select one or more programmes.</small>
                        </div>

                        <div class="form-group col-md-12">
                            <label>Duration</label>
                            <input type="text" name="duration" id="modal_duration" class="form-control" placeholder="eg. 3 Weeks or 120 hrs">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="modal_start_date" class="form-control">
                            <small class="form-text text-muted">Leave blank to use each programme start date.</small>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="modal_end_date" class="form-control">
                            <small class="form-text text-muted">Leave blank to use each programme end date.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="addCourseSubmitBtn">Add Courses</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal for editing a course in a batch --}}
<div class="modal fade" id="editCourseModal" tabindex="-1" role="dialog" aria-labelledby="editCourseModalLabel">
    <div class="modal-dialog modal-lg" role="document" style="margin-top: 50px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCourseModalLabel">Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @php $batchId = $batch?->id ?? 0; @endphp
            <div id="editCourseForm" data-action="">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" name="batch_id" value="{{ $batchId }}">
                        <input type="hidden" name="course_id" id="edit_course_id" value="">

                        <div class="form-group col-md-12">
                            <label>Branch *</label>
                            <select name="branch_id" id="edit_branch_id" class="form-control" required>
                                <option value="">Select Branch</option>
                                @foreach($branches as $id => $title)
                                    <option value="{{ $id }}">{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-12">
                            <label>Training Centre *</label>
                            <select name="centre_id" id="edit_centre_id" class="form-control" required>
                                <option value="">Select a branch first</option>
                            </select>
                        </div>

                        <div class="form-group col-md-12">
                            <label>Programme *</label>
                            <select name="programme_id" id="edit_programme_id" class="form-control" required>
                                <option value="">Select Programme</option>
                                @foreach($programmes as $programme)
                                    <option
                                        value="{{ $programme->id }}"
                                        data-start-date="{{ $programme->start_date }}"
                                        data-end-date="{{ $programme->end_date }}"
                                    >
                                        {{ $programme->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Duration</label>
                            <input type="text" name="duration" id="edit_duration" class="form-control" placeholder="eg. 3 Weeks or 120 hrs">
                        </div>

                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-control">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control">
                        </div>

                        <div class="form-group col-md-6">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="editCourseSubmitBtn">Update Course</button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('crud_fields_styles')
        <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @endpush

    @push('crud_fields_scripts')
        <script src="{{ asset('assets/plugins/select2/js/select2.full.min.js') }}"></script>
        @if (app()->getLocale() !== 'en')
            <script src="{{ asset('assets/plugins/select2/js/i18n/' . str_replace('_', '-', app()->getLocale()) . '.js') }}"></script>
        @endif
    @endpush
@endonce

<script>
(() => {
    'use strict';

    const apiUrl = '{{ backpack_url('api/centre-by-branch') }}';

    function showModal(modalElement) {
        if (!modalElement) return;

        // Prefer Bootstrap 5 API if available.
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return;
        }

        // Fallback to jQuery interface (if defined).
        const jq = window.jQuery;
        if (jq && jq.fn && jq.fn.modal) {
            jq(modalElement).modal('show');
        }
    }

    function getSelectedValues(selectEl) {
        if (!selectEl) return [];
        return Array.from(selectEl.selectedOptions || []).map((opt) => opt.value).filter((v) => v !== '');
    }

    function getMultiSelectValues(selectEl) {
        if (!selectEl) return [];
        const jq = window.jQuery;
        if (jq) {
            const val = jq(selectEl).val();
            if (Array.isArray(val)) return val.filter((v) => v !== '');
        }
        return getSelectedValues(selectEl);
    }

    function getSingleSelectValue(selectEl) {
        if (!selectEl) return '';
        const jq = window.jQuery;
        if (jq) {
            const val = jq(selectEl).val();
            return val == null ? '' : String(val);
        }
        return selectEl.value || '';
    }

    function getCsrfToken(modalElement) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const fromMeta = csrfMeta ? csrfMeta.getAttribute('content') : null;
        if (fromMeta) return fromMeta;
        if (!modalElement) return '';
        const tokenInput = modalElement.querySelector('input[name="_token"]');
        return tokenInput ? tokenInput.value : '';
    }

    function normalizeDate(value) {
        if (!value) return '';
        const str = String(value).trim();
        return str.length >= 10 ? str.substring(0, 10) : str;
    }

    function clearIfAutoFilled(inputEl) {
        if (!inputEl) return;
        if (inputEl.dataset && inputEl.dataset.autoFilled === '1') {
            inputEl.value = '';
            inputEl.dataset.autoFilled = '0';
        }
    }

    function setAutoFilledValue(inputEl, value) {
        if (!inputEl) return;
        inputEl.value = value || '';
        if (inputEl.dataset) {
            inputEl.dataset.autoFilled = value ? '1' : '0';
        }
    }

    function syncAddCourseDatesFromProgrammeSelection(modalElement) {
        const programmeSelect = document.getElementById('modal_programme_ids');
        const startInput = document.getElementById('modal_start_date') || (modalElement ? modalElement.querySelector('input[name="start_date"]') : null);
        const endInput = document.getElementById('modal_end_date') || (modalElement ? modalElement.querySelector('input[name="end_date"]') : null);
        if (!programmeSelect || !startInput || !endInput) return;

        const selectedOptions = Array.from(programmeSelect.selectedOptions || [])
            .filter((opt) => opt && opt.value && opt.value !== '');

        // Single programme selected: auto-fill from that programme.
        if (selectedOptions.length === 1) {
            const option = selectedOptions[0];
            const start = normalizeDate((option.dataset && option.dataset.startDate) ? option.dataset.startDate : option.getAttribute('data-start-date'));
            const end = normalizeDate((option.dataset && option.dataset.endDate) ? option.dataset.endDate : option.getAttribute('data-end-date'));

            setAutoFilledValue(startInput, start);
            setAutoFilledValue(endInput, end);
            return;
        }

        // Multiple/none selected: avoid forcing a single date range.
        // If values were auto-filled, clear them so programme-level defaults can apply.
        clearIfAutoFilled(startInput);
        clearIfAutoFilled(endInput);
    }

    function initSelect2InModal(modalElement, opts) {
        const jq = window.jQuery;
        if (!jq || !jq.fn || !jq.fn.select2) return;
        if (!modalElement) return;
        if (modalElement.dataset && modalElement.dataset.select2Initialized === '1') return;

        const $modal = jq(modalElement);
        const $dropdownParent = $modal.find('.modal-content').length ? $modal.find('.modal-content') : jq(document.body);

        const $branch = $modal.find(opts.branchSelector);
        const $centre = $modal.find(opts.centreSelector);
        const $programme = $modal.find(opts.programmeSelector);

        if ($programme.length && !$programme.hasClass('select2-hidden-accessible')) {
            $programme.select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $dropdownParent,
                placeholder: opts.programmePlaceholder || 'Select Programme',
                closeOnSelect: typeof opts.programmeCloseOnSelect === 'boolean' ? opts.programmeCloseOnSelect : true,
                allowClear: true,
            });
        }

        if ($centre.length && !$centre.hasClass('select2-hidden-accessible')) {
            $centre.select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $dropdownParent,
                placeholder: opts.centrePlaceholder || 'Select a branch first',
                closeOnSelect: typeof opts.centreCloseOnSelect === 'boolean' ? opts.centreCloseOnSelect : true,
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: apiUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        const branchId = $branch.val();
                        return {
                            q: params.term || '',
                            branch_id: branchId,
                        };
                    },
                    processResults: function (data) {
                        const items = Array.isArray(data) ? data : (data && data.data ? data.data : []);
                        return {
                            results: items.map((item) => ({ id: item.id, text: item.title })),
                        };
                    },
                    cache: true,
                },
            });
        }

        // Enable/disable centres select depending on branch selection
        if ($branch.length && $centre.length) {
            const syncDisabled = function () {
                const hasBranch = Boolean($branch.val());
                $centre.prop('disabled', !hasBranch);
            };

            $branch.on('change', function () {
                $centre.val(null).trigger('change');
                $centre.find('option').remove();
                syncDisabled();
            });

            syncDisabled();
        }

        if (modalElement.dataset) {
            modalElement.dataset.select2Initialized = '1';
        }
    }

    function submitAddCourses(modalElement) {
        const actionHolder = document.getElementById('addCourseForm');
        const actionUrl = actionHolder && actionHolder.dataset ? actionHolder.dataset.action : '';
        if (!actionUrl) {
            alert('Unable to submit: missing form action URL.');
            return;
        }

        const branchSelect = document.getElementById('modal_branch_id');
        const branchId = branchSelect ? branchSelect.value : '';
        const centreIds = getMultiSelectValues(document.getElementById('modal_centre_ids'));
        const programmeIds = getMultiSelectValues(document.getElementById('modal_programme_ids'));
        const duration = document.getElementById('modal_duration') ? document.getElementById('modal_duration').value : '';
        const startDateInput = document.querySelector('#addCourseModal input[name="start_date"]');
        const endDateInput = document.querySelector('#addCourseModal input[name="end_date"]');
        const batchIdInput = document.querySelector('#addCourseModal input[name="batch_id"]');
        const startDate = startDateInput ? startDateInput.value : '';
        const endDate = endDateInput ? endDateInput.value : '';
        const batchId = batchIdInput ? batchIdInput.value : '';

        if (!branchId) {
            alert('Please select a branch');
            return;
        }

        if (centreIds.length === 0) {
            alert('Please select at least one training centre');
            return;
        }

        if (programmeIds.length === 0) {
            alert('Please select at least one programme');
            return;
        }

        const csrfToken = getCsrfToken(modalElement);
        if (!csrfToken) {
            alert('Unable to submit: missing CSRF token.');
            return;
        }

        const submitBtn = document.getElementById('addCourseSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }

        // Create and submit a real form outside the Backpack CRUD form.
        const postForm = document.createElement('form');
        postForm.method = 'POST';
        postForm.action = actionUrl;
        postForm.style.display = 'none';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        postForm.appendChild(tokenInput);

        if (batchId) {
            const batchInput = document.createElement('input');
            batchInput.type = 'hidden';
            batchInput.name = 'batch_id';
            batchInput.value = batchId;
            postForm.appendChild(batchInput);
        }

        const branchInput = document.createElement('input');
        branchInput.type = 'hidden';
        branchInput.name = 'branch_id';
        branchInput.value = branchId;
        postForm.appendChild(branchInput);

        centreIds.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'centre_ids[]';
            input.value = id;
            postForm.appendChild(input);
        });

        programmeIds.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'programme_ids[]';
            input.value = id;
            postForm.appendChild(input);
        });

        if (duration) {
            const durationInput = document.createElement('input');
            durationInput.type = 'hidden';
            durationInput.name = 'duration';
            durationInput.value = duration;
            postForm.appendChild(durationInput);
        }

        if (startDate) {
            const startInput = document.createElement('input');
            startInput.type = 'hidden';
            startInput.name = 'start_date';
            startInput.value = startDate;
            postForm.appendChild(startInput);
        }

        if (endDate) {
            const endInput = document.createElement('input');
            endInput.type = 'hidden';
            endInput.name = 'end_date';
            endInput.value = endDate;
            postForm.appendChild(endInput);
        }

        document.body.appendChild(postForm);
        postForm.submit();
    }

    function submitEditCourse(modalElement) {
        const actionHolder = document.getElementById('editCourseForm');
        const actionUrl = actionHolder && actionHolder.dataset ? actionHolder.dataset.action : '';
        if (!actionUrl) {
            alert('Unable to submit: missing update URL.');
            return;
        }

        const batchIdInput = modalElement ? modalElement.querySelector('input[name="batch_id"]') : null;
        const batchId = batchIdInput ? batchIdInput.value : '';

        const centreId = getSingleSelectValue(document.getElementById('edit_centre_id'));
        const programmeId = getSingleSelectValue(document.getElementById('edit_programme_id'));
        const duration = document.getElementById('edit_duration') ? document.getElementById('edit_duration').value : '';
        const status = getSingleSelectValue(document.getElementById('edit_status'));
        const startDate = document.getElementById('edit_start_date') ? document.getElementById('edit_start_date').value : '';
        const endDate = document.getElementById('edit_end_date') ? document.getElementById('edit_end_date').value : '';

        if (!batchId) {
            alert('Unable to submit: missing batch id.');
            return;
        }

        if (!centreId) {
            alert('Please select a training centre');
            return;
        }

        if (!programmeId) {
            alert('Please select a programme');
            return;
        }

        const csrfToken = getCsrfToken(modalElement);
        if (!csrfToken) {
            alert('Unable to submit: missing CSRF token.');
            return;
        }

        const submitBtn = document.getElementById('editCourseSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }

        const postForm = document.createElement('form');
        postForm.method = 'POST';
        postForm.action = actionUrl;
        postForm.style.display = 'none';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        postForm.appendChild(tokenInput);

        const batchInput = document.createElement('input');
        batchInput.type = 'hidden';
        batchInput.name = 'batch_id';
        batchInput.value = batchId;
        postForm.appendChild(batchInput);

        const centreInput = document.createElement('input');
        centreInput.type = 'hidden';
        centreInput.name = 'centre_id';
        centreInput.value = centreId;
        postForm.appendChild(centreInput);

        const programmeInput = document.createElement('input');
        programmeInput.type = 'hidden';
        programmeInput.name = 'programme_id';
        programmeInput.value = programmeId;
        postForm.appendChild(programmeInput);

        if (duration) {
            const durationInput = document.createElement('input');
            durationInput.type = 'hidden';
            durationInput.name = 'duration';
            durationInput.value = duration;
            postForm.appendChild(durationInput);
        }

        if (startDate) {
            const startInput = document.createElement('input');
            startInput.type = 'hidden';
            startInput.name = 'start_date';
            startInput.value = startDate;
            postForm.appendChild(startInput);
        }

        if (endDate) {
            const endInput = document.createElement('input');
            endInput.type = 'hidden';
            endInput.name = 'end_date';
            endInput.value = endDate;
            postForm.appendChild(endInput);
        }

        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status || '0';
        postForm.appendChild(statusInput);

        document.body.appendChild(postForm);
        postForm.submit();
    }

    function init() {
        const modalElement = document.getElementById('addCourseModal');
        const editModalElement = document.getElementById('editCourseModal');

        // Move modal to body level (keeps modal controls out of the Backpack CRUD form).
        if (modalElement && document.body && modalElement.parentElement !== document.body) {
            document.body.appendChild(modalElement);
        }
        if (editModalElement && document.body && editModalElement.parentElement !== document.body) {
            document.body.appendChild(editModalElement);
        }

        // Make functions globally available (used by inline onclick in generated HTML).
        window.openAddCourseModal = function () {
            initSelect2InModal(modalElement, {
                branchSelector: '#modal_branch_id',
                centreSelector: '#modal_centre_ids',
                programmeSelector: '#modal_programme_ids',
                centrePlaceholder: 'Select a branch first',
                centreCloseOnSelect: false,
                programmePlaceholder: 'Select Programmes',
                programmeCloseOnSelect: false,
            });
            syncAddCourseDatesFromProgrammeSelection(modalElement);
            showModal(modalElement);
        };

        window.loadCentresForBatch = function (branchId) {
            const jq = window.jQuery;
            const centreSelect = document.getElementById('modal_centre_ids');
            if (!centreSelect) return;

            const hasSelect2 = Boolean(jq && jq.fn && jq.fn.select2 && jq(centreSelect).hasClass('select2-hidden-accessible'));

            // Clear current selection
            if (jq) {
                jq(centreSelect).val(null).trigger('change');
            } else {
                centreSelect.value = '';
            }

            // No branch selected: disable + reset placeholder.
            if (!branchId) {
                centreSelect.innerHTML = '<option value="">Select a branch first</option>';
                centreSelect.disabled = true;
                return;
            }

            // Select2 mode: ajax will fetch centres when opened/typed.
            if (hasSelect2) {
                jq(centreSelect).prop('disabled', false);
                return;
            }

            // Fallback (no select2 loaded): fetch centres and populate the plain <select>.
            centreSelect.disabled = true;
            centreSelect.innerHTML = '<option value="">Loading...</option>';

            const populate = function (data) {
                const items = Array.isArray(data) ? data : (data && data.data ? data.data : []);
                const options = ['<option value="">Select Training Centre</option>'];
                items.forEach((item) => {
                    if (!item || item.id === undefined) return;
                    const title = item.title !== undefined ? item.title : (item.text !== undefined ? item.text : item.name);
                    options.push(`<option value="${String(item.id)}">${String(title || '')}</option>`);
                });
                centreSelect.innerHTML = options.join('');
                centreSelect.disabled = false;
            };

            const fail = function () {
                centreSelect.innerHTML = '<option value="">Unable to load centres</option>';
                centreSelect.disabled = false;
            };

            if (jq && typeof jq.ajax === 'function') {
                jq.ajax({
                    url: apiUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: { branch_id: branchId },
                }).done(populate).fail(fail);
            } else if (window.fetch) {
                const requestUrl = `${apiUrl}?branch_id=${encodeURIComponent(branchId)}`;
                fetch(requestUrl, { headers: { 'Accept': 'application/json' } })
                    .then((r) => r.json())
                    .then(populate)
                    .catch(fail);
            } else {
                fail();
            }
        };

        const branchSelect = document.getElementById('modal_branch_id');
        if (branchSelect) {
            branchSelect.addEventListener('change', function () {
                window.loadCentresForBatch(this.value);
            });
        }

        const submitBtn = document.getElementById('addCourseSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function (e) {
                e.preventDefault();
                submitAddCourses(modalElement);
            });
        }

        const programmeSelect = document.getElementById('modal_programme_ids');
        if (programmeSelect) {
            programmeSelect.addEventListener('change', function () {
                syncAddCourseDatesFromProgrammeSelection(modalElement);
            });
        }

        const startDateInput = document.getElementById('modal_start_date');
        if (startDateInput) {
            startDateInput.addEventListener('change', function () {
                if (this.dataset) this.dataset.autoFilled = '0';
            });
        }
        const endDateInput = document.getElementById('modal_end_date');
        if (endDateInput) {
            endDateInput.addEventListener('change', function () {
                if (this.dataset) this.dataset.autoFilled = '0';
            });
        }

        window.openEditCourseModal = function (triggerEl) {
            if (!editModalElement || !triggerEl || !triggerEl.dataset) return;

            const actionHolder = document.getElementById('editCourseForm');
            if (actionHolder && actionHolder.dataset) {
                actionHolder.dataset.action = triggerEl.dataset.updateUrl || '';
            }

            document.getElementById('edit_course_id').value = triggerEl.dataset.courseId || '';

            const branchId = triggerEl.dataset.branchId || '';
            const centreId = triggerEl.dataset.centreId || '';
            const centreTitle = triggerEl.dataset.centreTitle || '';
            const programmeId = triggerEl.dataset.programmeId || '';

            const durationEl = document.getElementById('edit_duration');
            if (durationEl) durationEl.value = triggerEl.dataset.duration || '';
            const statusEl = document.getElementById('edit_status');
            if (statusEl) {
                let statusVal = triggerEl.dataset.status || '1';

                // Prefer the current status toggle state in the table row (if present).
                const row = typeof triggerEl.closest === 'function' ? triggerEl.closest('tr') : null;
                const rowToggle = row ? row.querySelector('input.form-check-input[data-toggle-field="status"]') : null;
                if (rowToggle) {
                    statusVal = rowToggle.checked ? '1' : '0';
                }

                statusEl.value = statusVal;
            }
            const startEl = document.getElementById('edit_start_date');
            if (startEl) startEl.value = String(triggerEl.dataset.startDate || '').substring(0, 10);
            const endEl = document.getElementById('edit_end_date');
            if (endEl) endEl.value = String(triggerEl.dataset.endDate || '').substring(0, 10);

            const editBranchSelect = document.getElementById('edit_branch_id');
            if (editBranchSelect) {
                editBranchSelect.value = branchId;
                editBranchSelect.dispatchEvent(new Event('change'));
            }

            initSelect2InModal(editModalElement, {
                branchSelector: '#edit_branch_id',
                centreSelector: '#edit_centre_id',
                programmeSelector: '#edit_programme_id',
                centrePlaceholder: 'Select a branch first',
                centreCloseOnSelect: true,
                programmePlaceholder: 'Select Programme',
                programmeCloseOnSelect: true,
            });

            // Preselect centre (select2 AJAX) and programme (static)
            const jq = window.jQuery;
            const centreSelect = document.getElementById('edit_centre_id');
            if (centreSelect && centreId) {
                // Create the option if it doesn't exist (needed for select2 ajax).
                const option = new Option(centreTitle || 'Selected Centre', centreId, true, true);
                centreSelect.append(option);
                if (jq) jq(centreSelect).trigger('change');
            }

            const programmeSelect = document.getElementById('edit_programme_id');
            if (programmeSelect && programmeId) {
                if (jq) {
                    jq(programmeSelect).val(programmeId).trigger('change');
                } else {
                    programmeSelect.value = programmeId;
                }
            }

            const editSubmitBtn = document.getElementById('editCourseSubmitBtn');
            if (editSubmitBtn) {
                editSubmitBtn.disabled = false;
                editSubmitBtn.textContent = 'Update Course';
            }

            showModal(editModalElement);
        };

        // SweetAlert confirmation + AJAX delete for the courses table (avoid full page navigation).
        if (typeof window.confirmDeleteBatchCourse !== 'function') {
            window.confirmDeleteBatchCourse = function (triggerEl) {
                if (!triggerEl || !triggerEl.dataset) return;

                const url = triggerEl.dataset.deleteUrl || '';
                const courseName = triggerEl.dataset.courseName || 'this course';

                if (!url) {
                    alert('Unable to delete: missing delete URL.');
                    return;
                }

                const doDelete = function () {
                    const jq = window.jQuery;
                    if (!jq || typeof jq.ajax !== 'function') {
                        alert('Unable to delete: jQuery is not available.');
                        return;
                    }

                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                    triggerEl.disabled = true;

                    jq.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
                        success: function (result) {
                            if (String(result) === '1') {
                                if (window.Noty) {
                                    new Noty({ type: "success", text: "Course deleted successfully." }).show();
                                }

                                const row = triggerEl.closest('tr');
                                const tbody = row ? row.closest('tbody') : null;
                                if (row) row.remove();

                                if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                    const msg = document.getElementById('batchCoursesEmptyMsg');
                                    if (msg) msg.style.display = 'block';
                                }

                                return;
                            }

                            if (window.swal) {
                                window.swal({
                                    title: "Delete failed",
                                    text: "Unable to delete course.",
                                    icon: "error",
                                    timer: 4000,
                                    buttons: false,
                                });
                            } else {
                                alert('Unable to delete course.');
                            }
                        },
                        error: function (xhr) {
                            let message = 'Unable to delete course.';
                            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }

                            if (window.swal) {
                                window.swal({
                                    title: "Delete failed",
                                    text: message,
                                    icon: "error",
                                });
                            } else {
                                alert(message);
                            }
                        },
                        complete: function () {
                            triggerEl.disabled = false;
                        }
                    });
                };

                if (window.swal) {
                    window.swal({
                        title: "Are you sure?",
                        text: `Are you sure you want to delete "${courseName}"?`,
                        icon: "warning",
                        buttons: {
                            cancel: {
                                text: "Cancel",
                                value: null,
                                visible: true,
                                className: "bg-secondary",
                                closeModal: true,
                            },
                            delete: {
                                text: "Yes, delete it!",
                                value: true,
                                visible: true,
                                className: "bg-danger",
                            },
                        },
                        dangerMode: true,
                    }).then((value) => {
                        if (value) doDelete();
                    });
                } else {
                    if (confirm(`Are you sure you want to delete "${courseName}"?`)) {
                        doDelete();
                    }
                }
            };
        }

        const editSubmitBtn = document.getElementById('editCourseSubmitBtn');
        if (editSubmitBtn) {
            editSubmitBtn.addEventListener('click', function (e) {
                e.preventDefault();
                submitEditCourse(editModalElement);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
</script>
