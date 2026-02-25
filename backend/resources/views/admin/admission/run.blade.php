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
                        <div class="row">
                            <div class="col-md-6">
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
                                        min="1" max="200" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label>Active Rules</label>
                                <div id="rulesContainer" class="d-flex flex-wrap gap-2">
                                    <span class="text-muted fst-italic">Select a course to view available rules...</span>
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
                <div class="card-header">
                    <h3 class="card-title">Preview: Selected Students</h3>
                </div>
                <div class="card-body">
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
                                    <th>Exam Score</th>
                                    <th>Education Level</th>
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
    // basset for sweet alert 
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

                let previewData = null;

                $('#course_id').change(function() {
                    const courseId = $(this).val();
                    const container = $('#rulesContainer');
                    
                    if (!courseId) {
                        container.html('<span class="text-muted fst-italic">Select a course to view available rules...</span>');
                        return;
                    }

                    container.html('<i class="la la-spinner la-spin"></i> Loading rules...');

                    $.ajax({
                        url: '{{ backpack_url('admission-run/get-rules') }}',
                        method: 'GET',
                        data: { 
                            course_id: courseId,
                            _token: '{{ csrf_token() }}'
                        },
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
                                container.html('<span class="text-warning">No active rules found for this course.</span>');
                            }
                        },
                        error: function() {
                            container.html('<span class="text-danger">Error fetching rules.</span>');
                        }
                    });
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
                    const limit = $('#limit').val();

                    if (!courseId || !limit) {
                        new Noty({
                            type: 'error',
                            text: 'Please fill in all required fields.'
                        }).show();
                        return;
                    }

                    const activeRules = getActiveRules();

                    const btn = $(this);
                    btn.prop('disabled', true).html('<i class="la la-spinner la-spin"></i> Loading...');

                    $.ajax({
                        url: '{{ route('admission.preview') }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            course_id: courseId,
                            // batch_id: batchId,
                            limit: limit,
                            active_rules: activeRules || [0]
                        },
                        success: function(response) {
                            if (response.success) {
                                previewData = response;
                                displayPreview(response);
                                $('#previewSection').show();
                                $('#executeBtn').show();

                                new Noty({
                                    type: 'success',
                                    text: `Found ${response.students.length} eligible students`
                                }).show();
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
                            const batchId = $('#batch_id').val();
                            const limit = $('#limit').val();
                            const activeRules = getActiveRules();

                            const btn = $(this);
                            btn.prop('disabled', true).html('<i class="la la-spinner la-spin"></i> Executing...');
                            $.ajax({
                                url: '{{ route('admission.execute') }}',
                                method: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    course_id: courseId,
                                    batch_id: batchId,
                                    limit: limit,
                                    active_rules: activeRules
                                },
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
                <div class="col-md-3">
                    <div class="alert alert-success">
                        <strong>Total Available:</strong> ${stats.total}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-info">
                        <strong>Total Selected:</strong> ${stats.total_selected}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-primary">
                        <strong>Gender:</strong> M: ${stats.gender_breakdown.male} / F: ${stats.gender_breakdown.female}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="alert alert-success">
                        <strong>Avg Score:</strong> ${stats.avg_exam_score}
                    </div>
                </div>
            </div>
        `);

                    // Display rules
                    let rulesHtml = '<strong>Applied Rules:</strong> ';
                    data.rules.forEach(function(rule, index) {
                        rulesHtml +=
                            `<span class="badge bg-secondary text-white">${rule.name} (Priority: ${rule.priority})</span> `;
                    });
                    $('#rulesSection').html(rulesHtml);

                    // Display students
                    const tbody = $('#studentsTable tbody');
                    tbody.empty();
                    data.students.forEach(function(student) {
                        tbody.append(`
                <tr>
                    <td>${student.name}</td>
                    <td>${student.email}</td>
                    <td>${student.gender}</td>
                    <td>${student.age}</td>
                    <td>${student.exam_score}</td>
                    <td>${student.educational_level}</td>
                    <td>${student.applied_date}</td>
                </tr>
            `);
                    });
                }
        </script>
    @endBassetBlock
@endsection
