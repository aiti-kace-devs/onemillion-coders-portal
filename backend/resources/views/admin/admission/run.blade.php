@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
        trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
        'Admissions' => false,
        'Run Automated Admission' => false,
    ];

    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('before_styles')
    {{-- Select2 CSS --}}
    @basset("https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css")
    @basset("https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css")
    @basset("https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.min.css")
@endsection

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">Run Automated Admission</span>
            <small>Select course and preview students before admitting</small>
        </h2>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Admission Configuration</h3>
                </div>
                <div class="card-body">
                    <form id="admissionForm">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check d-none" name="admission_mode" id="mode_course" value="course" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary" for="mode_course">Admit by Course</label>

                                    <input type="radio" class="btn-check d-none" name="admission_mode" id="mode_programme" value="programme" autocomplete="off">
                                    <label class="btn btn-outline-warning" for="mode_programme">Admit by Programme</label>

                                    <input type="radio" class="btn-check d-none" name="admission_mode" id="mode_centre" value="centre" autocomplete="off">
                                    <label class="btn btn-outline-info" for="mode_centre">Admit by Centre</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6" id="course_selection">
                                <div class="form-group">
                                    <label for="course_id">Course <span class="text-danger">*</span></label>
                                    <select name="course_id" id="course_id" class="form-control select2" required>
                                        <option value="">-- Select Course --</option>
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}">{{ $course->course_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="programme_selection" style="display:none;">
                                <div class="form-group">
                                    <label for="programme_id">Programme <span class="text-danger">*</span></label>
                                    <select name="programme_id" id="programme_id" class="form-control select2">
                                        <option value="">-- Select Programme --</option>
                                        @foreach ($programmes as $programme)
                                            <option value="{{ $programme->id }}">{{ $programme->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="centre_selection" style="display:none;">
                                <div class="form-group">
                                    <label for="centre_id">Centre <span class="text-danger">*</span></label>
                                    <select name="centre_id" id="centre_id" class="form-control select2">
                                        <option value="">-- Select Centre --</option>
                                        @foreach ($centres as $centre)
                                            <option value="{{ $centre->id }}">{{ $centre->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            {{-- <div class="col-md-4">
                                <div class="form-group">
                                    <label for="batch_id">Batch <span class="text-danger">*</span></label>
                                    <select name="batch_id" id="batch_id" class="form-control select2" required>
                                        <option value="">-- Select Batch --</option>
                                        @foreach ($batches as $batch)
                                            <option value="{{ $batch->id }}">{{ $batch->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div> --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="limit">Number of Students <span class="text-danger">*</span></label>
                                    <input type="number" name="limit" id="limit" class="form-control" value="50"
                                        min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label>Active Rules</label>
                                <div id="rulesContainer" class="d-flex flex-wrap gap-2">
                                    <span class="text-muted fst-italic">Select a course or programme to view available rules...</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="button" id="previewBtn" class="btn btn-primary">
                                    <i class="la la-eye"></i> Preview Students
                                </button>
                                <button type="button" id="executeBtn" class="btn btn-success" style="display:none;">
                                    <i class="la la-check"></i> Execute Admission
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4" id="previewSection" style="display:none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Preview: Selected Students</h3>
                    <div class="custom-control custom-switch" id="admitAllToggle" style="display:none;">
                        <input type="checkbox" class="custom-control-input" id="admitAllChk">
                        <label class="custom-control-label text-warning font-weight-bold" for="admitAllChk">
                            Admit All eligible students
                        </label>
                    </div>
                </div>
                <div class="card-body">
                    <div id="previewCapNotice" class="alert alert-warning d-none">
                        <p>
                            <i class="la la-info-circle"></i>
                            <strong>Performance Notice:</strong> Only the first 200 students are displayed in the preview.
                            The actual number that <strong>will be admitted</strong> based on your limit is shown in the statistics.
                            To admit <em>all</em> eligible students regardless of the limit, check <strong>"Admit All"</strong> above.
                        </p>
                    </div>
                    <div id="statsSection" class="mb-3"></div>
                    <div id="rulesSection" class="mb-3"></div>
                    <div class="table-responsive">
                        <table id="studentsTable" class="table table-striped table-hover datatable bp-datatable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Gender</th>
                                    <th>Age</th>
                                    <th>Student Level</th>
                                    <th>Education Level</th>
                                    <th id="categoryTableHeader">Programme</th>
                                    <th>Applied Date</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
    {{-- Select2 JS --}}
    @basset("https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js")
    @basset("https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.all.min.js")
    @bassetBlock('/admission-run.js')
        <script>
            $(document).ready(function() {
                // Initialize Select2
                if (typeof $.fn.select2 !== 'undefined') {
                    $('.select2').select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        placeholder: function() {
                            return $(this).find('option:first').text();
                        },
                        allowClear: true
                    });
                } else {
                    console.error('Select2 library not loaded');
                }

                let currentMode = 'course';

                $('input[name="admission_mode"]').change(function() {
                    currentMode = $(this).val();
                    if (currentMode === 'course') {
                        $('#course_selection').show();
                        $('#programme_selection').hide();
                        $('#centre_selection').hide();
                        $('#course_id').trigger('change');
                    } else if (currentMode === 'programme') {
                        $('#course_selection').hide();
                        $('#programme_selection').show();
                        $('#centre_selection').hide();
                        $('#programme_id').trigger('change');
                    } else {
                        $('#course_selection').hide();
                        $('#programme_selection').hide();
                        $('#centre_selection').show();
                        $('#centre_id').trigger('change');
                    }
                });

                function loadRules(entityType, entityId) {
                    const container = $('#rulesContainer');
                    
                    if (!entityId) {
                        container.html('<span class="text-muted fst-italic">Select a ' + entityType + ' to view available rules...</span>');
                        return;
                    }

                    container.html('<i class="la la-spinner la-spin"></i> Loading rules...');

                    let requestData = { _token: '{{ csrf_token() }}' };
                    if (entityType === 'course') requestData.course_id = entityId;
                    else if (entityType === 'programme') requestData.programme_id = entityId;
                    else requestData.centre_id = entityId;

                    $.ajax({
                        url: '{{ backpack_url('admission-run/get-rules') }}',
                        method: 'GET',
                        data: requestData,
                        success: function(response) {
                            if (response.success && response.rules.length > 0) {
                                let html = '';
                                response.rules.forEach(rule => {
                                    let paramsHtml = '';
                                    for (const [key, value] of Object.entries(rule.params)) {
                                        paramsHtml += `<small class="text-muted">(${key.replace('_', ' ').toUpperCase()}: ${value})</small> `;
                                    }
                                    html += `
                                            <div class="custom-control custom-switch mr-3 mb-2">
                                                <input type="checkbox" class="custom-control-input rule-toggle" 
                                                    id="rule_${rule.id}" 
                                                    value="${rule.id}" 
                                                    ${rule.is_active ? 'checked' : ''}>
                                                <label class="custom-control-label" for="rule_${rule.id}">
                                                    ${rule.name} <small class="text-muted">(Priority: ${rule.priority})</small><br>
                                                    ${paramsHtml}
                                                </label>
                                            </div>
                                        `;
                                });
                                container.html(html);
                            } else {
                                container.html('<span class="text-warning">No active rules found.</span>');
                            }
                        },
                        error: function() {
                            container.html('<span class="text-danger">Error fetching rules.</span>');
                        }
                    });
                }

                $('#course_id').change(function() {
                    if (currentMode === 'course') {
                        loadRules('course', $(this).val());
                    }
                });

                $('#programme_id').change(function() {
                    if (currentMode === 'programme') {
                        loadRules('programme', $(this).val());
                    }
                });

                $('#centre_id').change(function() {
                    if (currentMode === 'centre') {
                        loadRules('centre', $(this).val());
                    }
                });

                function getActiveRules() {
                    const rules = [];
                    $('.rule-toggle:checked').each(function() {
                        rules.push($(this).val());
                    });
                    return rules;
                }

                $('#previewBtn').click(function() {
                    const courseId = $('#course_id').val();
                    const programmeId = $('#programme_id').val();
                    const centreId = $('#centre_id').val();
                    const limit = $('#limit').val();

                    if (
                        (currentMode === 'course' && !courseId) || 
                        (currentMode === 'programme' && !programmeId) || 
                        (currentMode === 'centre' && !centreId) || 
                        !limit
                    ) {
                        new Noty({
                            type: 'error',
                            text: 'Please fill in all required fields.'
                        }).show();
                        return;
                    }

                    const activeRules = getActiveRules();

                    const btn = $(this);
                    btn.prop('disabled', true).html('<i class="la la-spinner la-spin"></i> Loading...');

                    let requestData = {
                        _token: '{{ csrf_token() }}',
                        limit: limit,
                        active_rules: activeRules || [0]
                    };
                    if (currentMode === 'course') requestData.course_id = courseId;
                    else if (currentMode === 'programme') requestData.programme_id = programmeId;
                    else requestData.centre_id = centreId;
                    
                    $.ajax({
                        url: '{{ route('admission.preview') }}',
                        method: 'POST',
                        data: requestData,
                        success: function(response) {
                            if (response.success) {
                                previewData = response;
                                displayPreview(response);
                                $('#previewSection').show();
                                $('#executeBtn').show();

                                // Show/hide performance cap notice and Admit All toggle
                                if (response.stats.preview_capped) {
                                    $('#previewCapNotice').removeClass('d-none');
                                    $('#admitAllToggle').show();
                                } else {
                                    $('#previewCapNotice').addClass('d-none');
                                    $('#admitAllToggle').hide();
                                    $('#admitAllChk').prop('checked', false);
                                }

                                const shown = response.students.length;
                                const willAdmit = response.stats.will_admit ?? shown;
                                const msg = response.stats.preview_capped
                                    ? `Showing first ${shown} of ${response.stats.total} eligible — ${willAdmit} will be admitted`
                                    : `Found ${shown} eligible students`;

                                new Noty({ type: 'success', text: msg }).show();
                            }
                        },
                        error: function(xhr) {
                            new Noty({
                                type: 'error',
                                text: xhr.responseJSON?.message || 'Error loading preview'
                            }).show();
                        },
                        complete: function() {
                            btn.prop('disabled', false).html(
                                '<i class="la la-eye"></i> Preview Students');
                        }
                    });
                });

                $('#executeBtn').click(function() {

                    Swal.fire({
                        title: 'Admit Students?',
                        text: "Are you sure you want to execute this admission? Emails will be sent to selected students.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, admit students!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const courseId = $('#course_id').val();
                            const programmeId = $('#programme_id').val();
                            const centreId = $('#centre_id').val();
                            const batchId = $('#batch_id').val();
                            const limit = $('#limit').val();
                            const activeRules = getActiveRules();
                            const admitAll = $('#admitAllChk').is(':checked') ? 1 : 0;

                            const btn = $(this);
                            btn.prop('disabled', true).html('<i class="la la-spinner la-spin"></i> Executing...');
                            
                            let requestData = {
                                _token: '{{ csrf_token() }}',
                                batch_id: batchId,
                                limit: limit,
                                admit_all: admitAll,
                                active_rules: activeRules
                            };
                            if (currentMode === 'course') requestData.course_id = courseId;
                            else if (currentMode === 'programme') requestData.programme_id = programmeId;
                            else requestData.centre_id = centreId;

                            $.ajax({
                                url: '{{ route('admission.execute') }}',
                                method: 'POST',
                                data: requestData,
                                success: function(response) {
                                    if (response.success) {
                                        new Noty({
                                            type: 'success',
                                            text: response.message
                                        }).show();

                                setTimeout(function() {
                                    window.location.href =
                                        '{{ backpack_url('admission-run') }}';
                                }, 1000);
                                }
                            },
                                error: function(xhr) {
                                    new Noty({
                                        type: 'error',
                                        text: xhr.responseJSON?.message ||
                                        'Error executing admission'
                                    }).show();
                                    btn.prop('disabled', false).html(
                                        '<i class="la la-check"></i> Execute Admission');
                            }
                        });
                        }});

                        });
                    });
          

                   function displayPreview(data) {
                    // Display statistics
                    const stats = data.stats;
                    $('#statsSection').html(`
            <div class="row">
                <div class="col-md-2">
                    <div class="alert alert-success">
                        <strong>Available:</strong> ${stats.total}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="alert alert-info">
                        <strong>Selected:</strong> ${stats.total_selected}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-primary">
                        <strong>Gender:</strong> M: ${stats.gender_breakdown.male} / F: ${stats.gender_breakdown.female}
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="alert alert-success">
                        <strong>Levels:</strong> Beginner: ${stats.level_distribution.beginner} / Intermediate: ${stats.level_distribution.intermediate} / Advanced: ${stats.level_distribution.advanced}
                    </div>
                </div>
            </div>
        `);
                    console.log(data.course_programme_level);
                    // Display rules
                    let rulesHtml = '<strong>Applied Rules:</strong> ';
                    data.rules.forEach(function(rule, index) {
                        rulesHtml +=
                            `<span class="badge bg-secondary text-white">${rule.name} (Priority: ${rule.priority})</span> `;
                    });
                    $('#rulesSection').html(rulesHtml);

                    // Update table header based on current mode
                    const categoryHeader = $('#categoryTableHeader');
                    if (currentMode === 'centre') {
                        categoryHeader.text('Programme');
                    } else {
                        categoryHeader.text('Branch');
                    }

                    // Display students
                    const tbody = $('#studentsTable tbody');
                    tbody.empty();
                    data.students.forEach(function(student) {
                        const categoryVal = currentMode === 'centre' ? student.programme : student.branch_name;
                        
                        tbody.append(`
                <tr>
                    <td>${student.name}</td>
                    <td>${student.email}</td>
                    <td>${student.gender}</td>
                    <td>${student.age}</td>
                    <td>${student.student_level ? (data.course_programme_level ? displayLevel(student.student_level, data.course_programme_level) : displayLevel(student.student_level)) : 'N/A'}</td>
                    <td>${student.educational_level}</td>
                    <td>${categoryVal}</td>
                    <td>${student.applied_date}</td>
                </tr>
            `);
                    });
                }
        

                const displayLevel = (level, courseLevel = 'intermediate') => {

                    if (level === 'advanced' || (level === 'intermediate' && courseLevel === 'beginner') || (level === courseLevel)) {
                        return "<span class='badge bg-success text-white'>" + level.toUpperCase() + "</span>"
                    }

                    const spanClass = level === 'intermediate' ? 'bg-warning' : 'bg-danger';
                    return "<span class='badge " + spanClass + " text-white'>" + level.toUpperCase() + "</span>"
                }  


        </script>
    @endBassetBlock
@endsection
