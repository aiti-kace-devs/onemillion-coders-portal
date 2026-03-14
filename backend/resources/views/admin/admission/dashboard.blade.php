@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
        trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
        'Admissions' => false,
        'Dashboard' => false,
    ];

    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">Admissions Dashboard</span>
            <small>Overview of registration and admission metrics</small>
        </h2>
    </section>

    <style>
        .card-header h6 {
            font-size: 14px !important;
        }
        .chart-container {
            min-height: 400px;
            position: relative;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        
        /* Select2 Backpack 7 / Bootstrap 5 Polishing */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #dce1e7;
            border-radius: 4px;
            min-height: 38px;
            display: flex;
            align-items: center;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 0.75rem;
            color: #1e293b;
        }
        .select2-container--bootstrap-5 .select2-dropdown {
            border-color: #dce1e7;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .select2-container--bootstrap-5 .select2-search__field {
            border-radius: 4px;
            border: 1px solid #dce1e7;
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary, #3b82f6);
        }
    </style>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="row">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Registered</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['total_registered']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="la la-users la-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Admitted</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['total_admitted']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="la la-user-check la-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Shortlisted</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['total_shortlisted']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="la la-list la-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Waiting List</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($summary['waiting_list']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="la la-hourglass-half la-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Level Mismatch Alert --}}
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow mb-4 border-bottom-danger">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-danger"> Level Mismatch Report</h6>
                    <span class="badge badge-danger">High Priority</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h4 class="text-danger font-weight-bold">{{ number_format($mismatch['registered']) }}</h4>
                            <p class="text-muted small">Registered into higher level course</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-warning font-weight-bold">{{ number_format($mismatch['shortlisted']) }}</h4>
                            <p class="text-muted small">Shortlisted with level mismatch</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h4 class="text-success font-weight-bold">{{ number_format($mismatch['admitted']) }}</h4>
                            <p class="text-muted small">Admitted into higher level course</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <table class="table table-sm table-stripped">
                            <thead>
                                <tr>
                                    <th>Student Level</th>
                                    <th>Course Level</th>
                                    <th class="text-center">Registered</th>
                                    <th class="text-center">Shortlisted</th>
                                    <th class="text-center">Admitted (M)</th>
                                    <th class="text-center">Admitted (F)</th>
                                    <th class="text-center">Total Admitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mismatch['details'] as $detail)
                                    <tr>
                                        <td><span class="badge badge-secondary">{{ strtoupper($detail['user_level']) }}</span></td>
                                        <td><span class="badge badge-primary">{{ strtoupper($detail['programme_level']) }}</span></td>
                                        <td class="text-center">{{ number_format($detail['registered_count']) }}</td>
                                        <td class="text-center">{{ number_format($detail['shortlisted_count']) }}</td>
                                        <td class="text-center text-primary">{{ number_format($detail['male_admitted']) }}</td>
                                        <td class="text-center text-danger">{{ number_format($detail['female_admitted']) }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($detail['admitted_count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row 1 --}}
    <div class="row mt-2">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Admissions by Branch</h6>
                    <div class="dropdown no-arrow">
                        <button class="btn btn-sm btn-link text-primary view-all-report" data-type="branch" title="View All Details">
                            <i class="la la-list"></i> View All
                        </button>
                    </div>
                </div>
                <div class="card-body chart-container">
                    <canvas id="branchChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Admissions by Programme</h6>
                    <button class="btn btn-sm btn-link text-primary view-all-report" data-type="programme" title="View All Details">
                        <i class="la la-list"></i> View All
                    </button>
                </div>
                <div class="card-body chart-container">
                    <canvas id="programmeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row 2 --}}
    <div class="row mt-2">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Districts (Admissions)</h6>
                    <button class="btn btn-sm btn-link text-primary view-all-report" data-type="district" title="View All Details">
                        <i class="la la-list"></i> View All
                    </button>
                </div>
                <div class="card-body chart-container">
                    <canvas id="districtChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Constituencies (Admissions)</h6>
                    <button class="btn btn-sm btn-link text-primary view-all-report" data-type="constituency" title="View All Details">
                        <i class="la la-list"></i> View All
                    </button>
                </div>
                <div class="card-body chart-container">
                    <canvas id="constituencyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Centres Table --}}
    <div class="row mt-2">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Centres (Admissions)</h6>
                    <button class="btn btn-sm btn-link text-primary view-all-report" data-type="centre" title="View All Details">
                        <i class="la la-list"></i> View All
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Centre Name</th>
                                    <th>Top Programme</th>
                                    <th class="text-center">Admitted (M)</th>
                                    <th class="text-center">Admitted (F)</th>
                                    <th class="text-center">Total Admitted</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byCentre as $centre)
                                    <tr>
                                        <td>{{ $centre['label'] }}</td>
                                        <td><span class="badge badge-info">{{ $centre['top_programme'] }}</span></td>
                                        <td class="text-center text-primary">{{ number_format($centre['male']) }}</td>
                                        <td class="text-center text-danger">{{ number_format($centre['female']) }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($centre['count']) }}</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary view-centre-details" data-id="{{ $centre['id'] }}" data-name="{{ $centre['label'] }}">
                                                <i class="la la-eye"></i> Details
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_scripts')
    {{-- Unified Report Modal - Moved here to prevent masking --}}
    <div class="modal fade" id="reportDetailsModal" tabindex="-1" role="dialog" aria-labelledby="reportDetailsModalLabel" aria-hidden="true" style="z-index: 9999;">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="modal-title font-weight-bold mb-0" id="reportDetailsModalLabel">
                        <i class="la la-file-alt"></i> Detailed Report
                    </h5>
                    <button type="button" class="close text-white border-0 bg-transparent p-0" data-dismiss="modal" aria-label="Close" style="opacity: 1; outline: none; font-size: 2.2rem; line-height: 1; transition: transform 0.2s;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div id="reportLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Fetching detailed statistics...</p>
                    </div>
                    <div id="reportContent" style="display: none;">
                        <h4 id="reportTitle" class="mb-4 text-primary font-weight-bold"></h4>
                        
                        <div id="reportSelectionWrapper" class="mb-4 d-none">
                            <label for="reportSelector" class="small font-weight-bold text-uppercase text-muted">Select Category:</label>
                            <select id="reportSelector" class="form-control select2" style="width: 100%;">
                                {{-- Dynamically populated --}}
                            </select>
                        </div>

                        <div class="table-responsive shadow-sm rounded">
                            <table class="table table-hover align-middle mb-0" id="detailedBreakdownTable">
                                <thead class="bg-light text-uppercase small font-weight-bold">
                                    <tr>
                                        <th>Programme</th>
                                        <th class="text-center">Level</th>
                                        <th class="text-center">Registered</th>
                                        <th class="text-center text-nowrap">Mismatch (Reg)</th>
                                        <th class="text-center">Admitted (M)</th>
                                        <th class="text-center">Admitted (F)</th>
                                        <th class="text-center">Total Admitted</th>
                                        <th class="text-center text-nowrap">Mismatch (Adm)</th>
                                    </tr>
                                </thead>
                                <tbody class="small font-weight-bold">
                                    {{-- Populated via JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light-50">
                    <button type="button" class="btn btn-secondary px-5 font-weight-bold" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @basset("https://cdn.jsdelivr.net/npm/chart.js")
    @basset("https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css")
    @basset("https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css")
    @basset("https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js")
    @bassetBlock('/admission-dashboard.js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Color Palette
                const colors = {
                    male: '#4e73df',
                    female: '#e74a3b',
                    total: '#36b9cc',
                    palette: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69']
                };

                const chartOptions = {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { drawBorder: false, color: '#f8f9fc' }
                        },
                        x: {
                            grid: { display: false, drawBorder: false }
                        }
                    },
                    plugins: {
                        legend: { display: true, position: 'bottom' }
                    }
                };

                // Branch Chart (Doughnut)
                new Chart(document.getElementById('branchChart'), {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode(collect($byBranch)->pluck('label')) !!},
                        datasets: [{
                            data: {!! json_encode(collect($byBranch)->pluck('count')) !!},
                            backgroundColor: colors.palette,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true, position: 'right' }
                        },
                        cutout: '70%'
                    }
                });

                // Programme Chart (Grouped Bar)
                new Chart(document.getElementById('programmeChart'), {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode(collect($byProgramme)->pluck('label')) !!},
                        datasets: [
                            {
                                label: 'Male',
                                backgroundColor: colors.male,
                                data: {!! json_encode(collect($byProgramme)->pluck('male')) !!}
                            },
                            {
                                label: 'Female',
                                backgroundColor: colors.female,
                                data: {!! json_encode(collect($byProgramme)->pluck('female')) !!}
                            }
                        ]
                    },
                    options: chartOptions
                });

                // District Chart (Grouped Bar)
                new Chart(document.getElementById('districtChart'), {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode(collect($byDistrict)->pluck('label')) !!},
                        datasets: [
                            {
                                label: 'Male',
                                backgroundColor: colors.male,
                                data: {!! json_encode(collect($byDistrict)->pluck('male')) !!}
                            },
                            {
                                label: 'Female',
                                backgroundColor: colors.female,
                                data: {!! json_encode(collect($byDistrict)->pluck('female')) !!}
                            }
                        ]
                    },
                    options: chartOptions
                });

                // Constituency Chart (Grouped Bar)
                new Chart(document.getElementById('constituencyChart'), {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode(collect($byConstituency)->pluck('label')) !!},
                        datasets: [
                            {
                                label: 'Male',
                                backgroundColor: colors.male,
                                data: {!! json_encode(collect($byConstituency)->pluck('male')) !!}
                            },
                            {
                                label: 'Female',
                                backgroundColor: colors.female,
                                data: {!! json_encode(collect($byConstituency)->pluck('female')) !!}
                            }
                        ]
                    },
                    options: chartOptions
                });

                // AJAX Reporting Logic
                const reportModal = $('#reportDetailsModal');
                const reportTitle = $('#reportTitle');
                const reportLoading = $('#reportLoading');
                const reportContent = $('#reportContent');
                const reportTableBody = $('#detailedBreakdownTable tbody');
                const reportWrapper = $('#reportSelectionWrapper');
                const reportSelector = $('#reportSelector');
                
                // Manual fix for modal close buttons
                reportModal.find('[data-dismiss="modal"], [data-bs-dismiss="modal"]').on('click', function() {
                    reportModal.modal('hide');
                });

                // Initialize Select2 with modal support
                function initSelect2() {
                    if ($.fn.select2) {
                        reportSelector.select2({
                            dropdownParent: reportModal,
                            theme: 'bootstrap-5',
                            width: '100%',
                            placeholder: 'Select a category'
                        });
                    }
                }
                
                // Unified Data Loading Function
                function loadReportData(url, title) {
                    reportTitle.text(title);
                    reportContent.hide();
                    reportLoading.show();
                    reportTableBody.empty();
                    reportModal.modal('show');

                    $.ajax({
                        url: url,
                        method: 'GET',
                        success: function(response) {
                            if (!Array.isArray(response)) {
                                response = [response]; // Handle single object response
                            }
                            
                            if (response.length > 0) {
                                response.forEach(item => {
                                    reportTableBody.append(`
                                        <tr>
                                            <td class="font-weight-bold text-dark">${item.title}</td>
                                            <td class="text-center"><span class="badge badge-secondary px-2">${(item.level || 'N/A').toUpperCase()}</span></td>
                                            <td class="text-center">${parseInt(item.registered).toLocaleString()}</td>
                                            <td class="text-center">
                                                <span class="badge ${item.registered_mismatch > 0 ? 'badge-danger' : 'badge-success'} px-2">
                                                    ${item.registered_mismatch}
                                                </span>
                                            </td>
                                            <td class="text-center text-primary font-weight-bold">${parseInt(item.male_admitted).toLocaleString()}</td>
                                            <td class="text-center text-danger font-weight-bold">${parseInt(item.female_admitted).toLocaleString()}</td>
                                            <td class="text-center font-weight-bold text-dark">${parseInt(item.admitted).toLocaleString()}</td>
                                            <td class="text-center">
                                                <span class="badge ${item.admitted_mismatch > 0 ? 'badge-danger' : 'badge-success'} px-2">
                                                    ${item.admitted_mismatch}
                                                </span>
                                            </td>
                                        </tr>
                                    `);
                                });
                            } else {
                                reportTableBody.append('<tr><td colspan="8" class="text-center py-4">No data available for this selection.</td></tr>');
                            }
                            reportLoading.hide();
                            reportContent.fadeIn();
                        },
                        error: function() {
                            reportTableBody.append('<tr><td colspan="8" class="text-center py-4 text-danger font-weight-bold">Error loading detailed report.</td></tr>');
                            reportLoading.hide();
                            reportContent.show();
                        }
                    });
                }

                // Handling "View Details" from Centres table
                $('.view-centre-details').on('click', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    reportWrapper.addClass('d-none');
                    loadReportData(`{{ url('admin/admission-dashboard/centre') }}/${id}`, `Centre: ${name}`);
                });

                // Handling "View All" buttons
                $('.view-all-report').on('click', function() {
                    const type = $(this).data('type');
                    reportModal.modal('show');
                    reportWrapper.removeClass('d-none');
                    reportSelector.empty();
                    
                    // Destroy previous select2 to ensure clean init
                    if (reportSelector.data('select2')) {
                        reportSelector.select2('destroy');
                    }
                    
                    let data = [];
                    let baseUrl = '';
                    let titlePrefix = '';

                    switch(type) {
                        case 'branch':
                            data = {!! json_encode($byBranch) !!};
                            baseUrl = `{{ url('admin/admission-dashboard/branch') }}`;
                            titlePrefix = 'Branch';
                            break;
                        case 'programme':
                            data = {!! json_encode($byProgramme) !!};
                            baseUrl = `{{ url('admin/admission-dashboard/programme') }}`; // Note: Uses title
                            titlePrefix = 'Programme';
                            break;
                        case 'district':
                            data = {!! json_encode($byDistrict) !!};
                            baseUrl = `{{ url('admin/admission-dashboard/district') }}`;
                            titlePrefix = 'District';
                            break;
                        case 'constituency':
                            data = {!! json_encode($byConstituency) !!};
                            baseUrl = `{{ url('admin/admission-dashboard/constituency') }}`;
                            titlePrefix = 'Constituency';
                            break;
                        case 'centre':
                            data = {!! json_encode($byCentre) !!};
                            baseUrl = `{{ url('admin/admission-dashboard/centre') }}`;
                            titlePrefix = 'Centre';
                            break;
                    }

                    // Populate Selector
                    if (data.length > 0) {
                        data.forEach(item => {
                            const val = type === 'programme' ? encodeURIComponent(item.label) : (item.id || item.label);
                            reportSelector.append(new Option(item.label, val));
                        });
                        
                        // Initial Load
                        const firstItem = data[0];
                        if (firstItem) {
                            const initVal = type === 'programme' ? encodeURIComponent(firstItem.label) : (firstItem.id || firstItem.label);
                            loadReportData(`${baseUrl}/${initVal}`, `${titlePrefix}: ${firstItem.label}`);
                        }
                    } else {
                        reportSelector.append(new Option('No data available', ''));
                        reportTitle.text(`${titlePrefix}: No Data`);
                        reportTableBody.empty().append('<tr><td colspan="8" class="text-center py-4">No categories found in the database.</td></tr>');
                        reportLoading.hide();
                        reportContent.show();
                    }

                    // Re-init Select2
                    initSelect2();

                    // On Selection Change
                    reportSelector.off('change').on('change', function() {
                        const selectedVal = $(this).val();
                        const selectedText = $(this).find('option:selected').text();
                        loadReportData(`${baseUrl}/${selectedVal}`, `${titlePrefix}: ${selectedText}`);
                    });
                });
            });
        </script>
    @endBassetBlock
    @bassetBlock('/admission-dashboard.css')
        <style>
            .border-left-primary { border-left: .25rem solid #4e73df!important; }
            .border-left-success { border-left: .25rem solid #1cc88a!important; }
            .border-left-info { border-left: .25rem solid #36b9cc!important; }
            .border-left-warning { border-left: .25rem solid #f6c23e!important; }
            .border-bottom-danger { border-bottom: .25rem solid #e74a3b!important; }
            .text-gray-300 { color: #dddfeb!important; }
            .text-gray-800 { color: #5a5c69!important; }
        </style>
    @endBassetBlock
@endsection
