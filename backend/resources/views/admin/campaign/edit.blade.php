@extends(backpack_view('blank'))

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

@section('content')

<div class="container-xl">
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Edit Campaign</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Campaign</h3>
                        @if($campaign->sent_at)
                            <div class="alert alert-info ms-auto mb-0" style="width: auto;">
                                <i class="fas fa-info-circle"></i> This campaign has already been sent and cannot be modified.
                            </div>
                        @endif
                    </div>

                    <div class="card-body">
                        @if($campaign->sent_at)
                            <div class="alert alert-warning">
                                <strong>Campaign Sent:</strong> {{ $campaign->sent_at->format('M d, Y H:i') }}
                            </div>
                        @endif

                        <form action="{{ backpack_url('campaign/' . $campaign->id) }}" method="POST" id="campaignForm">
                            @csrf
                            @method('PUT')

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="title">Campaign Title <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control @error('title') is-invalid @enderror"
                                        id="title"
                                        name="title"
                                        placeholder="Enter campaign title"
                                        value="{{ old('title', $campaign->title) }}"
                                        {{ $campaign->sent_at ? 'disabled' : '' }}
                                        required
                                    >
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="priority">Priority <span class="text-danger">*</span></label>
                                    <select
                                        class="form-select js-enhanced-select @error('priority') is-invalid @enderror"
                                        id="priority"
                                        name="priority"
                                        data-placeholder="Select priority"
                                        {{ $campaign->sent_at ? 'disabled' : '' }}
                                        required
                                    >
                                        <option value="low" @selected(old('priority', $campaign->priority) === 'low')>Low</option>
                                        <option value="normal" @selected(old('priority', $campaign->priority) === 'normal')>Normal</option>
                                        <option value="high" @selected(old('priority', $campaign->priority) === 'high')>High</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="message">Message <span class="text-danger">*</span></label>
                                <p class="text-muted small mb-2">
                                    <strong>Available Variables:</strong>
                                    <span class="badge bg-light text-dark cursor-pointer" onclick="insertVariable('[[first_name]]')">First Name</span>
                                    <span class="badge bg-light text-dark cursor-pointer" onclick="insertVariable('[[last_name]]')">Last Name</span>
                                    <span class="badge bg-light text-dark cursor-pointer" onclick="insertVariable('[[email]]')">Email</span>
                                    <span class="badge bg-light text-dark cursor-pointer" onclick="insertVariable('[[phone_number]]')">Phone</span>
                                    <span class="badge bg-light text-dark cursor-pointer" onclick="insertVariable('[[centre_name]]')">Centre</span>
                                </p>
                                <textarea
                                    class="form-control tinymce-editor @error('message') is-invalid @enderror"
                                    id="message"
                                    name="message"
                                    rows="8"
                                    {{ $campaign->sent_at ? 'disabled' : '' }}
                                    required
                                >{{ old('message', $campaign->message) }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="card mt-4 mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Target Admitted Users (Select as Specific as Needed)</h5>
                                    <small class="text-muted">Select deeper levels to narrow your audience. E.g., select a branch, then optionally filter by district within that branch.</small>
                                </div>

                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="branches"><strong>Branches</strong></label>
                                            <select
                                                class="form-control select2_multiple form-select-targeting js-targeting-select"
                                                id="branches"
                                                name="target_branches[]"
                                                multiple
                                                data-placeholder="Select branch(es)"
                                                {{ $campaign->sent_at ? 'disabled' : '' }}
                                            >
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}">
                                                        {{ $branch->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Leave empty to include all branches</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="districts"><strong>Districts</strong></label>
                                            <select
                                                class="form-control select2_multiple form-select-targeting js-targeting-select"
                                                id="districts"
                                                name="target_districts[]"
                                                multiple
                                                data-placeholder="Select district(s)"
                                                {{ $campaign->sent_at ? 'disabled' : '' }}
                                            ></select>
                                            <small class="text-muted">Only shows districts from selected branches</small>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="centres"><strong>Centres</strong></label>
                                            <select
                                                class="form-control select2_multiple form-select-targeting js-targeting-select"
                                                id="centres"
                                                name="target_centres[]"
                                                multiple
                                                data-placeholder="Select centre(s)"
                                                {{ $campaign->sent_at ? 'disabled' : '' }}
                                            ></select>
                                            <small class="text-muted">Only shows centres from selected districts</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="courses"><strong>Courses</strong></label>
                                            <select
                                                class="form-control select2_multiple form-select-targeting js-targeting-select"
                                                id="courses"
                                                name="target_courses[]"
                                                multiple
                                                data-placeholder="Select course(s)"
                                                {{ $campaign->sent_at ? 'disabled' : '' }}
                                            ></select>
                                            <small class="text-muted">Only shows courses from selected centres</small>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label" for="sessions"><strong>Sessions</strong></label>
                                            <select
                                                class="form-control select2_multiple form-select-targeting js-targeting-select"
                                                id="sessions"
                                                name="target_course_sessions[]"
                                                multiple
                                                data-placeholder="Select session(s)"
                                                {{ $campaign->sent_at ? 'disabled' : '' }}
                                            ></select>
                                            <small class="text-muted">Only shows sessions from selected courses. Includes both Master and Course sessions.</small>
                                        </div>
                                    </div>

                                    <div class="alert alert-info mt-3" id="targeting-summary" style="display: none;">
                                        <strong>Target Summary:</strong> <span id="summary-text"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-footer mt-4">
                                <a href="{{ backpack_url('campaign') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                @if(!$campaign->sent_at)
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Campaign
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-pointer {
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .cursor-pointer:hover {
        background-color: #0d6efd !important;
        color: white !important;
    }

    .form-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>

<script>
const dataCache = {
    districts: {},
    centres: {},
    courses: {},
    sessions: {}
};

const targetingRoutes = {
    districts: '/api/campaign-targeting/districts',
    centres: '/api/campaign-targeting/centres',
    courses: '/api/campaign-targeting/courses',
    sessions: '/api/campaign-targeting/sessions'
};

const isReadOnly = @json((bool) $campaign->sent_at);

const existingSelections = {
    branches: @json(old('target_branches', $campaign->target_branches ?? [])),
    districts: @json(old('target_districts', $campaign->target_districts ?? [])),
    centres: @json(old('target_centres', $campaign->target_centres ?? [])),
    courses: @json(old('target_courses', $campaign->target_courses ?? [])),
    sessions: @json(old('target_course_sessions', $campaign->target_course_sessions ?? []))
};

tinymce.init({
    selector: '.tinymce-editor',
    plugins: 'paste,link,lists,table',
    toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link table | removeformat',
    menubar: false,
    statusbar: true,
    height: 300,
    paste_data_images: false,
    setup: function (ed) {
        ed.on('change', function () {
            ed.save();
        });
    }
});

function insertVariable(variable) {
    const editor = tinymce.get('message');

    if (editor) {
        editor.insertContent(variable);
        editor.focus();
        return;
    }

    const textarea = document.getElementById('message');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + variable + text.substring(end);
    textarea.focus();
}

let select2InitAttempts = 0;

document.addEventListener('DOMContentLoaded', async function () {
    setupEventListeners();
    await loadInitialData();
    ensureSelect2Initialized();
    updateTargetSummary();
});

function hasSelect2() {
    return typeof window.jQuery !== 'undefined'
        && typeof window.jQuery.fn !== 'undefined'
        && typeof window.jQuery.fn.select2 === 'function';
}

function ensureSelect2Initialized() {
    if (initializeSelect2()) {
        return;
    }

    if (select2InitAttempts >= 20) {
        return;
    }

    select2InitAttempts += 1;
    window.setTimeout(ensureSelect2Initialized, 150);
}

function initializeSelect2() {
    if (!hasSelect2()) {
        return false;
    }

    window.jQuery('.js-enhanced-select').each(function () {
        const $select = window.jQuery(this);

        if ($select.hasClass('select2-hidden-accessible')) {
            return;
        }

        $select.select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: $select.data('placeholder') || undefined
        });
    });

    window.jQuery('.js-targeting-select').each(function () {
        const $select = window.jQuery(this);

        if ($select.hasClass('select2-hidden-accessible')) {
            return;
        }

        $select.select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: $select.data('placeholder') || 'Select options...',
            allowClear: true,
            closeOnSelect: false
        });
    });

    return true;
}

function setupEventListeners() {
    if (isReadOnly) {
        return;
    }

    const branches = document.getElementById('branches');
    const districts = document.getElementById('districts');
    const centres = document.getElementById('centres');
    const courses = document.getElementById('courses');
    const sessions = document.getElementById('sessions');

    branches?.addEventListener('change', async function () {
        resetSelect('districts', 'Loading district(s)...', true);
        resetSelect('centres', 'Select district(s) first', true);
        resetSelect('courses', 'Select centre(s) first', true);
        resetSelect('sessions', 'Select course(s) first', true);
        await loadDistricts();
        updateTargetSummary();
    });

    districts?.addEventListener('change', async function () {
        resetSelect('centres', 'Loading centre(s)...', true);
        resetSelect('courses', 'Select centre(s) first', true);
        resetSelect('sessions', 'Select course(s) first', true);
        await loadCentres();
        updateTargetSummary();
    });

    centres?.addEventListener('change', async function () {
        resetSelect('courses', 'Loading course(s)...', true);
        resetSelect('sessions', 'Select course(s) first', true);
        await loadCourses();
        updateTargetSummary();
    });

    courses?.addEventListener('change', async function () {
        resetSelect('sessions', 'Loading session(s)...', true);
        await loadSessions();
        updateTargetSummary();
    });

    sessions?.addEventListener('change', updateTargetSummary);
}

async function loadInitialData() {
    applySelectedValues('branches', existingSelections.branches);

    if ((existingSelections.branches || []).length > 0) {
        await loadDistricts();
        applySelectedValues('districts', existingSelections.districts);
    } else {
        resetSelect('districts', 'Select branch(es) first', true);
    }

    if ((existingSelections.districts || []).length > 0) {
        await loadCentres();
        applySelectedValues('centres', existingSelections.centres);
    } else {
        resetSelect('centres', 'Select district(s) first', true);
    }

    if ((existingSelections.centres || []).length > 0) {
        await loadCourses();
        applySelectedValues('courses', existingSelections.courses);
    } else {
        resetSelect('courses', 'Select centre(s) first', true);
    }

    if ((existingSelections.courses || []).length > 0) {
        await loadSessions();
        applySelectedValues('sessions', existingSelections.sessions);
    } else {
        resetSelect('sessions', 'Select course(s) first', true);
    }

    if (isReadOnly) {
        ['branches', 'districts', 'centres', 'courses', 'sessions'].forEach(function (selectId) {
            setSelectDisabled(selectId, true);
        });
    }
}

async function fetchJson(url, payload = null) {
    const options = {
        method: payload ? 'POST' : 'GET',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    if (payload) {
        options.headers['Content-Type'] = 'application/json';
        options.headers['X-CSRF-TOKEN'] = document.querySelector('input[name="_token"]').value;
        options.body = JSON.stringify(payload);
    }

    const response = await fetch(url, options);

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
    }

    return response.json();
}

function getSelectedValues(selectId) {
    const select = document.getElementById(selectId);

    if (!select) {
        return [];
    }

    return Array.from(select.selectedOptions)
        .map(option => String(option.value))
        .filter(Boolean);
}

function syncSelectUi(selectId) {
    if (!hasSelect2()) {
        return;
    }

    window.jQuery('#' + selectId).trigger('change.select2');
}

async function loadDistricts() {
    const branchIds = getSelectedValues('branches');

    if (branchIds.length === 0) {
        resetSelect('districts', 'Select branch(es) first', true);
        return;
    }

    const cacheKey = branchIds.join(',');

    try {
        const data = dataCache.districts[cacheKey]
            ?? await fetchJson(targetingRoutes.districts, { branch_ids: branchIds });

        dataCache.districts[cacheKey] = Array.isArray(data) ? data : [];
        populateSelect('districts', dataCache.districts[cacheKey]);
        setSelectDisabled('districts', false);
    } catch (error) {
        console.error('Error loading districts:', error);
        resetSelect('districts', 'Unable to load districts', true);
    }
}

async function loadCentres() {
    const districtIds = getSelectedValues('districts');

    if (districtIds.length === 0) {
        resetSelect('centres', 'Select district(s) first', true);
        return;
    }

    const cacheKey = districtIds.join(',');

    try {
        const data = dataCache.centres[cacheKey]
            ?? await fetchJson(targetingRoutes.centres, { district_ids: districtIds });

        dataCache.centres[cacheKey] = Array.isArray(data) ? data : [];
        populateSelect('centres', dataCache.centres[cacheKey]);
        setSelectDisabled('centres', false);
    } catch (error) {
        console.error('Error loading centres:', error);
        resetSelect('centres', 'Unable to load centres', true);
    }
}

async function loadCourses() {
    const centreIds = getSelectedValues('centres');

    if (centreIds.length === 0) {
        resetSelect('courses', 'Select centre(s) first', true);
        return;
    }

    const cacheKey = centreIds.join(',');

    try {
        const data = dataCache.courses[cacheKey]
            ?? await fetchJson(targetingRoutes.courses, { centre_ids: centreIds });

        dataCache.courses[cacheKey] = Array.isArray(data) ? data : [];
        populateSelect('courses', dataCache.courses[cacheKey]);
        setSelectDisabled('courses', false);
    } catch (error) {
        console.error('Error loading courses:', error);
        resetSelect('courses', 'Unable to load courses', true);
    }
}

async function loadSessions() {
    const courseIds = getSelectedValues('courses');

    if (courseIds.length === 0) {
        resetSelect('sessions', 'Select course(s) first', true);
        return;
    }

    const cacheKey = courseIds.join(',');

    try {
        const data = dataCache.sessions[cacheKey]
            ?? await fetchJson(targetingRoutes.sessions, { course_ids: courseIds });

        dataCache.sessions[cacheKey] = Array.isArray(data) ? data : [];
        populateSelect('sessions', dataCache.sessions[cacheKey]);
        setSelectDisabled('sessions', false);
    } catch (error) {
        console.error('Error loading sessions:', error);
        resetSelect('sessions', 'Unable to load sessions', true);
    }
}

function populateSelect(selectId, options) {
    const select = document.getElementById(selectId);
    const currentValues = getSelectedValues(selectId);

    if (!select) {
        return;
    }

    select.innerHTML = '';

    (options || []).forEach(function (opt) {
        const value = String(opt.id);
        const label = opt.title || opt.text || opt.name || value;
        const option = new Option(label, value, false, currentValues.includes(value));
        select.appendChild(option);
    });

    syncSelectUi(selectId);
}

function applySelectedValues(selectId, values) {
    const select = document.getElementById(selectId);
    const normalizedValues = (values || []).map(String);

    if (!select) {
        return;
    }

    Array.from(select.options).forEach(function (option) {
        option.selected = normalizedValues.includes(String(option.value));
    });

    syncSelectUi(selectId);
}

function setSelectDisabled(selectId, disabled) {
    const select = document.getElementById(selectId);

    if (!select) {
        return;
    }

    select.disabled = disabled;
    syncSelectUi(selectId);
}

function resetSelect(selectId, placeholder, disabled) {
    const select = document.getElementById(selectId);

    if (!select) {
        return;
    }

    select.innerHTML = '';
    Array.from(select.options).forEach(function (option) {
        option.selected = false;
    });
    select.dataset.placeholder = placeholder;
    select.disabled = disabled;
    syncSelectUi(selectId);
}

function updateTargetSummary() {
    const branches = getSelectedValues('branches');
    const districts = getSelectedValues('districts');
    const centres = getSelectedValues('centres');
    const courses = getSelectedValues('courses');
    const sessions = getSelectedValues('sessions');

    let summary = 'Will send to admitted users in: ';

    if (sessions.length > 0) {
        summary += sessions.length + ' session(s)';
    } else if (courses.length > 0) {
        summary += courses.length + ' course(s)';
    } else if (centres.length > 0) {
        summary += centres.length + ' centre(s)';
    } else if (districts.length > 0) {
        summary += districts.length + ' district(s)';
    } else if (branches.length > 0) {
        summary += branches.length + ' branch(es)';
    } else {
        summary = 'Will send to all admitted users in all branches';
    }

    const summaryEl = document.getElementById('targeting-summary');
    const summaryText = document.getElementById('summary-text');

    if (branches.length > 0 || districts.length > 0 || centres.length > 0 || courses.length > 0 || sessions.length > 0) {
        summaryText.textContent = summary;
        summaryEl.style.display = 'block';
    } else {
        summaryEl.style.display = 'none';
    }
}
</script>

@endsection
