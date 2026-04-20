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
                                            <label class="form-label" for="branches">Branches (Optional)</label>
                                            <select class="form-select form-select-md" id="branches" name="target_branches[]" multiple placeholder="Select branches...">
                                                <option value="">-- Select branches --</option>
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}" @selected(in_array($branch->id, old('target_branches', [])))>
                                                        {{ $branch->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Leave empty to include all branches</small>
                                        </div>

                                        <!-- Districts -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="districts">Districts (Optional)</label>
                                            <select class="form-select form-select-md" id="districts" name="target_districts[]" multiple placeholder="Select districts..." disabled>
                                                <option value="">-- Select branches first --</option>
                                            </select>
                                            <small class="text-muted">Only shows districts from selected branches</small>
                                        </div>
                                    </div>

                                    <!-- Centres -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="centres">Centres (Optional)</label>
                                            <select class="form-select form-select-md" id="centres" name="target_centres[]" multiple placeholder="Select centres..." disabled>
                                                <option value="">-- Select districts first --</option>
                                            </select>
                                            <small class="text-muted">Only shows centres from selected districts</small>
                                        </div>

                                        <!-- Courses -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="courses">Courses (Optional)</label>
                                            <select class="form-select form-select-md" id="courses" name="target_courses[]" multiple placeholder="Select courses..." disabled>
                                                <option value="">-- Select centres first --</option>
                                            </select>
                                            <small class="text-muted">Only shows courses from selected centres</small>
                                        </div>
                                    </div>

                                    <!-- Sessions -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label" for="sessions">Sessions (Optional)</label>
                                            <select class="form-select form-select-md" id="sessions" name="target_course_sessions[]" multiple placeholder="Select sessions..." disabled>
                                                <option value="">-- Select courses first --</option>
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
                                <small class="form-text text-muted" id="char-count-info" style="display: none;">
                                    <span id="charCount">0</span>/1200 characters
                                </small>
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
    .form-select-md {
        min-height: 120px;
    }
</style>

<!-- TinyMCE Open Source CDN (No API Key Required) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>

<script>
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

// Load branches on init
document.addEventListener('DOMContentLoaded', async function() {
    await loadBranches();
    setupEventListeners();
    updateTargetSummary();
});

async function loadBranches() {
    try {
        const response = await fetch('/api/campaign-targeting/branches');
        const branches = await response.json();
        populateSelect('branches', branches);
    } catch (error) {
        console.error('Error loading branches:', error);
    }
}

async function loadDistricts() {
    const branchIds = Array.from(document.getElementById('branches').selectedOptions).map(o => o.value);
    
    if (branchIds.length === 0) {
        populateSelect('districts', []);
        disableSelect('districts', true);
        return;
    }

    disableSelect('districts', false);
    try {
        const response = await fetch('/api/campaign-targeting/districts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
            body: JSON.stringify({ branch_ids: branchIds })
        });
        const districts = await response.json();
        populateSelect('districts', districts);
    } catch (error) {
        console.error('Error loading districts:', error);
    }
}

async function loadCentres() {
    const districtIds = Array.from(document.getElementById('districts').selectedOptions).map(o => o.value);
    const branchIds = Array.from(document.getElementById('branches').selectedOptions).map(o => o.value);
    
    // If branches selected but no districts, load centres from branches
    let idsToUse = districtIds.length > 0 ? districtIds : branchIds;
    const endpoint = districtIds.length > 0 ? 'centres' : 'centres';
    
    if (idsToUse.length === 0) {
        populateSelect('centres', []);
        disableSelect('centres', true);
        return;
    }

    disableSelect('centres', false);
    try {
        const response = await fetch('/api/campaign-targeting/' + endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
            body: JSON.stringify({ district_ids: idsToUse })
        });
        const centres = await response.json();
        populateSelect('centres', centres);
    } catch (error) {
        console.error('Error loading centres:', error);
    }
}

async function loadCourses() {
    const centreIds = Array.from(document.getElementById('centres').selectedOptions).map(o => o.value);
    
    if (centreIds.length === 0) {
        populateSelect('courses', []);
        disableSelect('courses', true);
        return;
    }

    disableSelect('courses', false);
    try {
        const response = await fetch('/api/campaign-targeting/courses', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
            body: JSON.stringify({ centre_ids: centreIds })
        });
        const courses = await response.json();
        populateSelect('courses', courses);
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

async function loadSessions() {
    const courseIds = Array.from(document.getElementById('courses').selectedOptions).map(o => o.value);
    
    if (courseIds.length === 0) {
        populateSelect('sessions', []);
        disableSelect('sessions', true);
        return;
    }

    disableSelect('sessions', false);
    try {
        const response = await fetch('/api/campaign-targeting/sessions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
            body: JSON.stringify({ course_ids: courseIds })
        });
        const sessions = await response.json();
        populateSelect('sessions', sessions);
    } catch (error) {
        console.error('Error loading sessions:', error);
    }
}

function populateSelect(selectId, options) {
    const select = document.getElementById(selectId);
    const currentValues = Array.from(select.selectedOptions).map(o => o.value);
    
    select.innerHTML = '<option value="">-- Select ' + selectId + ' --</option>';
    
    options.forEach(opt => {
        const option = document.createElement('option');
        option.value = opt.id;
        option.textContent = opt.title || opt.text || opt.name;
        if (currentValues.includes(String(opt.id))) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

function disableSelect(selectId, disabled) {
    const select = document.getElementById(selectId);
    select.disabled = disabled;
    if (disabled) {
        select.innerHTML += '<option value="">-- Select parent first --</option>';
    }
}

function setupEventListeners() {
    document.getElementById('branches').addEventListener('change', async function() {
        await loadDistricts();
        populateSelect('centres', []);
        populateSelect('courses', []);
        populateSelect('sessions', []);
        disableSelect('centres', true);
        disableSelect('courses', true);
        disableSelect('sessions', true);
        updateTargetSummary();
    });

    document.getElementById('districts').addEventListener('change', async function() {
        await loadCentres();
        populateSelect('courses', []);
        populateSelect('sessions', []);
        disableSelect('courses', true);
        disableSelect('sessions', true);
        updateTargetSummary();
    });

    document.getElementById('centres').addEventListener('change', async function() {
        await loadCourses();
        populateSelect('sessions', []);
        disableSelect('sessions', true);
        updateTargetSummary();
    });

    document.getElementById('courses').addEventListener('change', async function() {
        await loadSessions();
        updateTargetSummary();
    });

    document.getElementById('sessions').addEventListener('change', updateTargetSummary);
}

function updateTargetSummary() {
    const branches = Array.from(document.getElementById('branches').selectedOptions).map(o => o.textContent);
    const districts = Array.from(document.getElementById('districts').selectedOptions).map(o => o.textContent);
    const centres = Array.from(document.getElementById('centres').selectedOptions).map(o => o.textContent);
    const courses = Array.from(document.getElementById('courses').selectedOptions).map(o => o.textContent);
    const sessions = Array.from(document.getElementById('sessions').selectedOptions).map(o => o.textContent);

    let summary = 'Will send to admitted users in: ';
    
    if (sessions.length > 0) {
        summary += sessions.length + ' sessions';
    } else if (courses.length > 0) {
        summary += courses.length + ' courses';
    } else if (centres.length > 0) {
        summary += centres.length + ' centres';
    } else if (districts.length > 0) {
        summary += districts.length + ' districts';
    } else if (branches.length > 0) {
        summary += branches.length + ' branches';
    } else {
        summary += 'all branches';
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
