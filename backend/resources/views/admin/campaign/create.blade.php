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
                                            <select class="form-select form-select-targeting" id="branches" name="target_branches[]" multiple>
                                                <option value="">Select branches...</option>
                                            </select>
                                            <small class="text-muted">Leave empty to include all branches</small>
                                        </div>

                                        <!-- Districts -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="districts"><strong>Districts</strong></label>
                                            <select class="form-select form-select-targeting" id="districts" name="target_districts[]" multiple disabled>
                                                <option value="">Select branches first</option>
                                            </select>
                                            <small class="text-muted">Only shows districts from selected branches</small>
                                        </div>
                                    </div>

                                    <!-- Centres -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="centres"><strong>Centres</strong></label>
                                            <select class="form-select form-select-targeting" id="centres" name="target_centres[]" multiple disabled>
                                                <option value="">Select districts first</option>
                                            </select>
                                            <small class="text-muted">Only shows centres from selected districts</small>
                                        </div>

                                        <!-- Courses -->
                                        <div class="col-md-6">
                                            <label class="form-label" for="courses"><strong>Courses</strong></label>
                                            <select class="form-select form-select-targeting" id="courses" name="target_courses[]" multiple disabled>
                                                <option value="">Select centres first</option>
                                            </select>
                                            <small class="text-muted">Only shows courses from selected centres</small>
                                        </div>
                                    </div>

                                    <!-- Sessions -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label" for="sessions"><strong>Sessions</strong></label>
                                            <select class="form-select form-select-targeting" id="sessions" name="target_course_sessions[]" multiple disabled>
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
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
</style>




<!-- Backpack Select2 Assets (use Backpack's bundled version) -->
@if (backpack_theme_config('scripts') && array_key_exists('select2', backpack_theme_config('scripts')))
    @foreach (backpack_theme_config('scripts')['select2'] as $path)
        <script src="{{ asset($path) }}"></script>
    @endforeach
@endif

@if (backpack_theme_config('styles') && array_key_exists('select2', backpack_theme_config('styles')))
    @foreach (backpack_theme_config('styles')['select2'] as $path)
        <link href="{{ asset($path) }}" rel="stylesheet" />
    @endforeach
@endif

<!-- TinyMCE -->
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
        ed.on('change', function(e) { ed.save(); });
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
        textarea.value = text.substring(0, start) + variable + text.substring(end);
        textarea.focus();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeSelect2();
    loadBranches(); // Load immediately, not on open
    setupEventListeners();
});

function initializeSelect2() {
    $('.form-select-targeting').each(function() {
        const $el = $(this);
        if ($el.data('select2')) $el.select2('destroy');
        
        $el.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: $el.attr('placeholder') || 'Select options...',
            allowClear: true,
            minimumResultsForSearch: 5,
            closeOnSelect: false // Keep dropdown open for multi-select
        });
    });
}

async function loadBranches() {
    const $select = $('#branches');
    $select.prop('disabled', true).html('<option>Loading branches...</option>');
    
    try {
        const response = await fetch('/api/campaign-targeting/branches');
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const branches = await response.json();
        
        $select.empty().append('<option value="">Select branches...</option>');
        branches.forEach(b => {
            $select.append(new Option(b.title, b.id, false, false));
        });
        
        $select.prop('disabled', false).trigger('change');
        
    } catch (error) {
        console.error('Error loading branches:', error);
        $select.prop('disabled', false)
               .empty()
               .append('<option value="">Error loading branches</option>')
               .trigger('change');
    }
}

async function loadDistricts() {
    const branchIds = $('#branches').val() || [];
    const $select = $('#districts');
    
    if (!branchIds.length) {
        $select.prop('disabled', true).val(null).trigger('change')
               .empty().append('<option value="">Select branches first</option>');
        return;
    }

    $select.prop('disabled', true).html('<option>Loading districts...</option>');
    
    try {
        const response = await fetch('/api/campaign-targeting/districts', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value 
            },
            body: JSON.stringify({ branch_ids: branchIds })
        });
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const districts = await response.json();
        
        $select.empty().append('<option value="">Select districts...</option>');
        districts.forEach(d => {
            $select.append(new Option(d.title, d.id, false, false));
        });
        
        $select.prop('disabled', false).trigger('change');
        
    } catch (error) {
        console.error('Error loading districts:', error);
        $select.prop('disabled', false)
               .empty()
               .append('<option value="">Error loading districts</option>')
               .trigger('change');
    }
}

async function loadCentres() {
    const districtIds = $('#districts').val() || [];
    const $select = $('#centres');
    
    if (!districtIds.length) {
        $select.prop('disabled', true).val(null).trigger('change')
               .empty().append('<option value="">Select districts first</option>');
        return;
    }

    $select.prop('disabled', true).html('<option>Loading centres...</option>');
    
    try {
        const response = await fetch('/api/campaign-targeting/centres', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value 
            },
            body: JSON.stringify({ district_ids: districtIds })
        });
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const centres = await response.json();
        
        $select.empty().append('<option value="">Select centres...</option>');
        centres.forEach(c => {
            $select.append(new Option(c.title, c.id, false, false));
        });
        
        $select.prop('disabled', false).trigger('change');
        
    } catch (error) {
        console.error('Error loading centres:', error);
        $select.prop('disabled', false)
               .empty()
               .append('<option value="">Error loading centres</option>')
               .trigger('change');
    }
}

async function loadCourses() {
    const centreIds = $('#centres').val() || [];
    const $select = $('#courses');
    
    if (!centreIds.length) {
        $select.prop('disabled', true).val(null).trigger('change')
               .empty().append('<option value="">Select centres first</option>');
        return;
    }

    $select.prop('disabled', true).html('<option>Loading courses...</option>');
    
    try {
        const response = await fetch('/api/campaign-targeting/courses', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value 
            },
            body: JSON.stringify({ centre_ids: centreIds })
        });
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const courses = await response.json();
        
        $select.empty().append('<option value="">Select courses...</option>');
        courses.forEach(c => {
            $select.append(new Option(c.title, c.id, false, false));
        });
        
        $select.prop('disabled', false).trigger('change');
        
    } catch (error) {
        console.error('Error loading courses:', error);
        $select.prop('disabled', false)
               .empty()
               .append('<option value="">Error loading courses</option>')
               .trigger('change');
    }
}

async function loadSessions() {
    const courseIds = $('#courses').val() || [];
    const $select = $('#sessions');
    
    if (!courseIds.length) {
        $select.prop('disabled', true).val(null).trigger('change')
               .empty().append('<option value="">Select courses first</option>');
        return;
    }

    $select.prop('disabled', true).html('<option>Loading sessions...</option>');
    
    try {
        const response = await fetch('/api/campaign-targeting/sessions', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value 
            },
            body: JSON.stringify({ course_ids: courseIds })
        });
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const sessions = await response.json();
        
        $select.empty().append('<option value="">Select sessions...</option>');
        sessions.forEach(s => {
            $select.append(new Option(s.title, s.id, false, false));
        });
        
        $select.prop('disabled', false).trigger('change');
        
    } catch (error) {
        console.error('Error loading sessions:', error);
        $select.prop('disabled', false)
               .empty()
               .append('<option value="">Error loading sessions</option>')
               .trigger('change');
    }
}

function setupEventListeners() {
    $('#branches').on('change', async function() {
        await loadDistricts();
        resetDependents(['centres', 'courses', 'sessions']);
        updateTargetSummary();
    });

    $('#districts').on('change', async function() {
        await loadCentres();
        resetDependents(['courses', 'sessions']);
        updateTargetSummary();
    });

    $('#centres').on('change', async function() {
        await loadCourses();
        resetDependents(['sessions']);
        updateTargetSummary();
    });

    $('#courses').on('change', async function() {
        await loadSessions();
        updateTargetSummary();
    });

    $('#sessions').on('change', updateTargetSummary);
}

function resetDependents(selectIds) {
    selectIds.forEach(id => {
        $(`#${id}`).prop('disabled', true)
                  .val(null)
                  .trigger('change')
                  .empty()
                  .append(`<option value="">Select ${id.slice(0, -1)} first</option>`);
    });
}

function updateTargetSummary() {
    const counts = {
        branches: $('#branches').val()?.length || 0,
        districts: $('#districts').val()?.length || 0,
        centres: $('#centres').val()?.length || 0,
        courses: $('#courses').val()?.length || 0,
        sessions: $('#sessions').val()?.length || 0
    };

    let summary = 'Will send to admitted users in: ';
    
    if (counts.sessions) summary += `${counts.sessions} session(s)`;
    else if (counts.courses) summary += `${counts.courses} course(s)`;
    else if (counts.centres) summary += `${counts.centres} centre(s)`;
    else if (counts.districts) summary += `${counts.districts} district(s)`;
    else if (counts.branches) summary += `${counts.branches} branch(es)`;
    else summary = 'Will send to all admitted users in all branches';

    const showSummary = Object.values(counts).some(v => v > 0);
    document.getElementById('targeting-summary').style.display = showSummary ? 'block' : 'none';
    document.getElementById('summary-text').textContent = summary;
}
</script>
@endsection