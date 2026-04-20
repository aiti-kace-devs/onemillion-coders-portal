@extends(backpack_view('blank'))

@section('content')

<div class="container-xl">
    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Create Campaign</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Send Customized Campaign to Admitted Users</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ backpack_url('campaign') }}" method="POST" id="campaignForm">
                            @csrf

                            <!-- Campaign Title -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="title">Campaign Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                           id="title" name="title" placeholder="e.g., Q1 2026 Training Reminder" 
                                           value="{{ old('title') }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Priority -->
                                <div class="col-md-6">
                                    <label class="form-label" for="priority">Priority <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" 
                                            id="priority" name="priority" required>
                                        <option value="low" @selected(old('priority') === 'low')>Low</option>
                                        <option value="normal" @selected(old('priority') === 'normal' || !old('priority'))>Normal</option>
                                        <option value="high" @selected(old('priority') === 'high')>High</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Targeting Hierarchy Section -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Target Admitted Users (Select as Specific as Needed)</h5>
                                    <small class="text-muted">Select deeper levels to narrow your audience. E.g., select a branch, then optionally filter by district within that branch.</small>
                                </div>
                                <div class="card-body">
                                    <!-- Branches -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="branches"><strong>Branches</strong></label>
                                            <select class="form-select form-select-targeting" id="branches" name="target_branches[]" multiple data-loaded="false">
                                                <option value="">Loading branches...</option>
                                            </select>
                                            <small class="text-muted">Leave empty to include all branches</small>
                                        </div>

                                        <!-- Districts -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="districts"><strong>Districts</strong></label>
                                            <select class="form-select form-select-targeting" id="districts" name="target_districts[]" multiple disabled data-loaded="false">
                                                <option value="">Select branches first</option>
                                            </select>
                                            <small class="text-muted">Only shows districts from selected branches</small>
                                        </div>
                                    </div>

                                    <!-- Centres -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="centres"><strong>Centres</strong></label>
                                            <select class="form-select form-select-targeting" id="centres" name="target_centres[]" multiple disabled data-loaded="false">
                                                <option value="">Select districts first</option>
                                            </select>
                                            <small class="text-muted">Only shows centres from selected districts</small>
                                        </div>

                                        <!-- Courses -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="courses"><strong>Courses</strong></label>
                                            <select class="form-select form-select-targeting" id="courses" name="target_courses[]" multiple disabled data-loaded="false">
                                                <option value="">Select centres first</option>
                                            </select>
                                            <small class="text-muted">Only shows courses from selected centres</small>
                                        </div>
                                    </div>

                                    <!-- Sessions -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label" for="sessions"><strong>Sessions</strong></label>
                                            <select class="form-select form-select-targeting" id="sessions" name="target_course_sessions[]" multiple disabled data-loaded="false">
                                                <option value="">Select courses first</option>
                                            </select>
                                            <small class="text-muted">Only shows sessions from selected courses. Includes both Master and Course sessions.</small>
                                        </div>
                                    </div>

                                    <!-- Summary -->
                                    <div class="alert alert-info mt-3" id="targeting-summary" style="display: none;">
                                        <strong>Target Summary:</strong> <span id="summary-text"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Message Section with TinyMCE -->
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
                                <textarea class="form-control tinymce-editor @error('message') is-invalid @enderror" 
                                          id="message" name="message" rows="8"
                                          required>{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Hidden type field -->
                            <input type="hidden" name="type" value="campaign">
                            <input type="hidden" name="created_by" value="{{ backpack_auth()->id() }}">

                            <!-- Form Actions -->
                            <div class="form-footer mt-4">
                                <a href="{{ backpack_url('campaign') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Campaign
                                </button>
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

<!-- Select2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- TinyMCE Open Source CDN (No API Key Required) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>

<script>
// Performance optimization: Data cache to avoid re-fetching
const dataCache = {
    branches: null,
    districts: {},
    centres: {},
    courses: {},
    sessions: {}
};

const loadingState = {
    branches: false,
    districts: false,
    centres: false,
    courses: false,
    sessions: false
};

// Initialize TinyMCE
tinymce.init({
    selector: '.tinymce-editor',
    plugins: 'paste,link,lists,table',
    toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link table | removeformat',
    menubar: false,
    statusbar: true,
    height: 300,
    paste_data_images: false,
    setup: function(ed) {
        ed.on('change', function(e) {
            ed.save();
        });
    }
});

function insertVariable(variable) {
    const editor = tinymce.get('message');
    if (editor) {
        editor.insertContent(variable);
        editor.focus();
    } else {
        const textarea = document.getElementById('message');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const newText = text.substring(0, start) + variable + text.substring(end);
        textarea.value = newText;
        textarea.focus();
    }
}

// Initialize Select2 with optimizations
document.addEventListener('DOMContentLoaded', function() {
    initializeSelect2();
    setupEventListeners();
    updateTargetSummary();
});

function initializeSelect2() {
    const selects = document.querySelectorAll('.form-select-targeting');
    
    selects.forEach(select => {
        $(select).select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: select.getAttribute('placeholder') || 'Select options...',
            allowClear: true,
            minimumResultsForSearch: 5,
            language: {
                noResults: function() {
                    return "No results found";
                }
            }
        });
    });

    // Lazy load branches on first open
    $('#branches').on('select2:opening', async function() {
        if (!dataCache.branches && !loadingState.branches) {
            loadingState.branches = true;
            await loadBranches();
        }
    });

    // Lazy load districts on change
    $('#branches').on('change', async function() {
        await loadDistricts();
        $('#centres').val(null).trigger('change');
        $('#courses').val(null).trigger('change');
        $('#sessions').val(null).trigger('change');
        $('#centres').prop('disabled', true);
        $('#courses').prop('disabled', true);
        $('#sessions').prop('disabled', true);
        updateTargetSummary();
    });

    $('#districts').on('change', async function() {
        await loadCentres();
        $('#courses').val(null).trigger('change');
        $('#sessions').val(null).trigger('change');
        $('#courses').prop('disabled', true);
        $('#sessions').prop('disabled', true);
        updateTargetSummary();
    });

    $('#centres').on('change', async function() {
        await loadCourses();
        $('#sessions').val(null).trigger('change');
        $('#sessions').prop('disabled', true);
        updateTargetSummary();
    });

    $('#courses').on('change', async function() {
        await loadSessions();
        updateTargetSummary();
    });

    $('#sessions').on('change', updateTargetSummary);
}

async function loadBranches() {
    if (dataCache.branches) return;
    
    try {
        const response = await fetch('/api/campaign-targeting/branches');
        dataCache.branches = await response.json();
        populateSelect('branches', dataCache.branches);
    } catch (error) {
        console.error('Error loading branches:', error);
        loadingState.branches = false;
    }
}

async function loadDistricts() {
    const branchIds = $('#branches').val() || [];
    
    if (!branchIds || branchIds.length === 0) {
        $('#districts').prop('disabled', true).val(null).trigger('change');
        $('#districts').select2('data', []);
        return;
    }

    $('#districts').prop('disabled', false);
    const cacheKey = branchIds.join(',');
    
    if (dataCache.districts[cacheKey]) {
        populateSelect('districts', dataCache.districts[cacheKey]);
        return;
    }

    try {
        const response = await fetch('/api/campaign-targeting/districts', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value 
            },
            body: JSON.stringify({ branch_ids: branchIds })
        });
        const data = await response.json();
        dataCache.districts[cacheKey] = data;
        populateSelect('districts', data);
    } catch (error) {
        console.error('Error loading districts:', error);
    }
}

async function loadCentres() {
    const districtIds = $('#districts').val() || [];
    
    if (!districtIds || districtIds.length === 0) {
        $('#centres').prop('disabled', true).val(null).trigger('change');
        return;
    }

    $('#centres').prop('disabled', false);
    const cacheKey = districtIds.join(',');
    
    if (dataCache.centres[cacheKey]) {
        populateSelect('centres', dataCache.centres[cacheKey]);
        return;
    }

    try {
        const response = await fetch('/api/campaign-targeting/centres', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value 
            },
            body: JSON.stringify({ district_ids: districtIds })
        });
        const data = await response.json();
        dataCache.centres[cacheKey] = data;
        populateSelect('centres', data);
    } catch (error) {
        console.error('Error loading centres:', error);
    }
}

async function loadCourses() {
    const centreIds = $('#centres').val() || [];
    
    if (!centreIds || centreIds.length === 0) {
        $('#courses').prop('disabled', true).val(null).trigger('change');
        return;
    }

    $('#courses').prop('disabled', false);
    const cacheKey = centreIds.join(',');
    
    if (dataCache.courses[cacheKey]) {
        populateSelect('courses', dataCache.courses[cacheKey]);
        return;
    }

    try {
        const response = await fetch('/api/campaign-targeting/courses', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value 
            },
            body: JSON.stringify({ centre_ids: centreIds })
        });
        const data = await response.json();
        dataCache.courses[cacheKey] = data;
        populateSelect('courses', data);
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

async function loadSessions() {
    const courseIds = $('#courses').val() || [];
    
    if (!courseIds || courseIds.length === 0) {
        $('#sessions').prop('disabled', true).val(null).trigger('change');
        return;
    }

    $('#sessions').prop('disabled', false);
    const cacheKey = courseIds.join(',');
    
    if (dataCache.sessions[cacheKey]) {
        populateSelect('sessions', dataCache.sessions[cacheKey]);
        return;
    }

    try {
        const response = await fetch('/api/campaign-targeting/sessions', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value 
            },
            body: JSON.stringify({ course_ids: courseIds })
        });
        const data = await response.json();
        dataCache.sessions[cacheKey] = data;
        populateSelect('sessions', data);
    } catch (error) {
        console.error('Error loading sessions:', error);
    }
}

function populateSelect(selectId, options) {
    const $select = $('#' + selectId);
    const currentValues = $select.val() || [];
    
    const formattedOptions = options.map(opt => ({
        id: opt.id,
        text: opt.title || opt.text || opt.name
    }));
    
    $select.select2({
        data: formattedOptions,
        theme: 'bootstrap-5'
    });
    
    if (currentValues.length > 0) {
        $select.val(currentValues).trigger('change');
    }
}

function setupEventListeners() {
    // Event listeners are set up in initializeSelect2
}

function updateTargetSummary() {
    const branches = $('#branches').val() || [];
    const districts = $('#districts').val() || [];
    const centres = $('#centres').val() || [];
    const courses = $('#courses').val() || [];
    const sessions = $('#sessions').val() || [];

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
</script>

@endsection
