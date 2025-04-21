@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        .multi-select-container {
            display: inline-block;
            position: relative;
        }

        .multi-select-menu {
            position: absolute;
            left: 0;
            top: 0.8em;
            z-index: 1;
            float: left;
            min-width: 100%;
            background: #fff;
            margin: 1em 0;
            border: 1px solid #aaa;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            display: none;
        }

        .multi-select-menuitem {
            display: block;
            font-size: 0.875em;
            padding: 0.6em 1em 0.6em 30px;
            white-space: nowrap;
        }

        .multi-select-legend {
            font-size: 0.875em;
            font-weight: bold;
            padding-left: 10px;
        }

        .multi-select-legend+.multi-select-menuitem {
            padding-top: 0.25rem;
        }

        .multi-select-menuitem+.multi-select-menuitem {
            padding-top: 0;
        }

        .multi-select-presets {
            border-bottom: 1px solid #ddd;
        }

        .multi-select-menuitem input {
            position: absolute;
            margin-top: 0.25em;
            margin-left: -20px;
        }

        .multi-select-button {
            display: inline-block;
            font-size: 0.875em;
            padding: 0.2em 0.6em;
            max-width: 16em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: -0.5em;
            background-color: #fff;
            border: 1px solid #aaa;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            cursor: default;
        }

        .multi-select-button:after {
            content: "";
            display: inline-block;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0.4em 0.4em 0 0.4em;
            border-color: #999 transparent transparent transparent;
            margin-left: 0.4em;
            vertical-align: 0.1em;
        }

        .multi-select-container--open .multi-select-menu {
            display: block;
        }

        .multi-select-container--open .multi-select-button:after {
            border-width: 0 0.4em 0.4em 0.4em;
            border-color: transparent transparent #999 transparent;
        }

        .multi-select-container--positioned .multi-select-menu {
            box-sizing: border-box;
        }

        .multi-select-container--positioned .multi-select-menu label {
            white-space: normal;
        }

        .multi-select-container,
        .multi-select-button {
            display: block;
        }

        .multi-select-button {
            width: 100% !important;
            font-size: inherit !important;
            padding: 6px 12px;
        }
    </style>

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        .multi-select-container {
            display: inline-block;
            position: relative;
        }

        .multi-select-menu {
            position: absolute;
            left: 0;
            top: 0.8em;
            z-index: 1;
            float: left;
            min-width: 100%;
            background: #fff;
            margin: 1em 0;
            border: 1px solid #aaa;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            display: none;
        }

        .multi-select-menuitem {
            display: block;
            font-size: 0.875em;
            padding: 0.6em 1em 0.6em 30px;
            white-space: nowrap;
        }

        .multi-select-legend {
            font-size: 0.875em;
            font-weight: bold;
            padding-left: 10px;
        }

        .multi-select-legend+.multi-select-menuitem {
            padding-top: 0.25rem;
        }

        .multi-select-menuitem+.multi-select-menuitem {
            padding-top: 0;
        }

        .multi-select-presets {
            border-bottom: 1px solid #ddd;
        }

        .multi-select-menuitem input {
            position: absolute;
            margin-top: 0.25em;
            margin-left: -20px;
        }

        .multi-select-button {
            display: inline-block;
            font-size: 0.875em;
            padding: 0.2em 0.6em;
            max-width: 16em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: -0.5em;
            background-color: #fff;
            border: 1px solid #aaa;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            cursor: default;
        }

        .multi-select-button:after {
            content: "";
            display: inline-block;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0.4em 0.4em 0 0.4em;
            border-color: #999 transparent transparent transparent;
            margin-left: 0.4em;
            vertical-align: 0.1em;
        }

        .multi-select-container--open .multi-select-menu {
            display: block;
        }

        .multi-select-container--open .multi-select-button:after {
            border-width: 0 0.4em 0.4em 0.4em;
            border-color: transparent transparent #999 transparent;
        }

        .multi-select-container--positioned .multi-select-menu {
            box-sizing: border-box;
        }

        .multi-select-container--positioned .multi-select-menu label {
            white-space: normal;
        }

        .multi-select-container,
        .multi-select-button {
            display: block;
        }

        .multi-select-button {
            width: 100% !important;
            font-size: inherit !important;
            padding: 6px 12px;
        }
    </style>


    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manage Shortlisted Students</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Manage Exam</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->

            <section class="content">
                <div class="container-fluid">
                    {{-- <x-wysiwyg></x-wysiwyg> --}}
                    <div id="accordion">
                        <div class="card">
                            <div class="card-header" id="headingOne">
                                <span class="d-flex flex-column flex-md-row justify-content-between">
                                    <div class="mb-0 dropdown-toggle" style="cursor: pointer;" data-toggle="collapse"
                                        data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        FILTER DATA
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 d-flex justify-content-end pr-3 mb-2">
                                            <a class="btn btn-info mr-2" href="javascript:;" data-toggle="modal"
                                                data-target="#shortlisted_students">Choose Shortlist</a>
                                            <button class="btn btn-warning mr-2" data-toggle="modal"
                                                data-target="#bulk-email-modal">Send Emails
                                                <i class="fas fa-envelope"></i>
                                            </button>

                                            <button class="btn btn-success mr-2" data-toggle="modal"
                                                data-target="#bulk-sms-modal">Send SMS
                                                <i class="fas fa-sms"></i>
                                            </button>



                                            </button>
                                            <button class="btn btn-primary mr-2" id="admit-selected">Admit Students</button>
                                        </div>
                                    </div>
                                </span>
                            </div>

                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne"
                                data-parent="#accordion" style="">
                                {{-- <div class="card-body"> --}}
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <select multiple name="admission_status[]" id="admission_status"
                                                class="form-control" data-filter="admission_status">
                                                <option value="0">All Admission Statuses</option>
                                                <option value="Admitted">Admitted</option>
                                                <option value="Not Admitted">Not Admitted</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select multiple name="course_id[]" id="course_id" class="form-control"
                                                data-filter="course" aria-hidden="true">
                                                <option value="0">All Courses</option>
                                                @foreach ($courses as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <input type="text" class="form-control" id="studentSearch"
                                                placeholder="Search by name or email">
                                        </div>

                                    </div>
                                    {{-- </div> --}}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <div class="card">
                                {{-- <div class="card-header">
                                    <h3 class="card-title">Title</h3>

                                    <div class="card-tools">
                                        <a class="btn btn-info btn-sm" href="javascript:;" data-toggle="modal"
                                            data-target="#myModal">Add new student</a>
                                    </div>
                                </div> --}}



                                <table id="studentsTable" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th width="20px"><input type="checkbox" id="select-all"></th>
                                            <th>Name (Email)</th>
                                            <th>Admitted</th>
                                            <th>Shortlisted</th>

                                            <th>Course</th>
                                            <th>Session</th>

                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        </section>
    </div>
    </section>
    </div>

    <!-- Modal -->
    @include('admin.send-bulk-email')
    <!-- @include('admin.send_bulk_sms') -->


    <x-modal id="shortlisted_students" title="Copy and Paste Shortlisted Student Emails" size="modal-lg">
        <label for="email_list">Paste Emails/Phonenumbers Here</label>
        <textarea class="form-control mb-3" name="email_list" id="email_list" rows="10" style="min-height: 250px;"
            placeholder="Paste emails/numbers, one per line..."></textarea>

        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button id="shortlist-modal-submit" type="button" class="btn btn-primary">Submit</button>
        </x-slot>
    </x-modal>





    <div class="modal fade" id="admitModal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Admit Student</h4>
                    <button type="button" class="close" data-dismiss="modal">×</button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/admin/admit') }}" name="admit_form" method="POST">
                        {{ csrf_field() }}
                        <input id="user_id" name="user_id" type="hidden" class="form-control" required>
                        {{-- <input name="user_ids[]" type="hidden" class="form-control"> --}}

                        <input id="change" name="change" value="false" type="hidden" class="form-control"
                            required>
                        <div class="form-group">
                            <label for="course_id" class="form-label">Select Course</label>
                            <select id="course_id" name="course_id" class="form-control" required>
                                <option value="">Choose One Course</option>
                                @foreach ($courses as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>


                        <div class="form-group">
                            <label for="session_id" class="form-label">Choose Session</label>
                            <select id="session_id" name="session_id" class="form-control"
                                @if (empty($sessions)) disabled @endif>
                                @if (empty($sessions))
                                    <option value="">No sessions available</option>
                                @else
                                    <option value="">Choose One Session</option>
                                    @foreach ($sessions as $session)
                                        <option data-course="{{ $session->course_id }}" value="{{ $session->id }}">
                                            {{ $session->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if (empty($sessions))
                                <small class="text-muted">Sessions are not configured. Please contact support.</small>
                            @endif
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary"
                                @if (empty($sessions)) disabled @endif>Admit</button>
                        </div>


                        {{-- <div class="form-group">
                            <button class="btn btn-primary" @if (empty($sessions)) disabled @endif>Admit</button>
                        </div> --}}
                    </form>
                </div>
            </div>
        </div>
    </div>



    <x-modal id="bulk-sms-modal" title="Send Bulk SMS TESTING" size="modal-lg">

        <label for="sms_template">Select Template To Use</label>
        <select name="sms_template" id="sms_template" class="form-control">
            <option value="" selected disabled>Loading templates...</option>
        </select>

        <br>

        <label for="sms_message">Or Write Message</label>
        <textarea class="form-control mb-3" name="sms_message" id="sms_message" placeholder="Type your SMS message here..."></textarea>

        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button id="modal-submit" type="button" class="btn btn-primary">Submit</button>
        </x-slot>
    </x-modal>




    @push('scripts')
        <script type="text/javascript" src="{{ url('assets/js/jquery-multiselect.min.js') }}"></script>
        <script>
            $(document).on('click', '.admit-btn', function() {
                const user_id = $(this).data('id');
                const course_id = $(this).data('course_id');
                const session_id = $(this).data('session_id');

                // Call the modal function
                openAdmitModal(user_id, course_id, session_id);

            });

            window.openAdmitModal = function(user_id, course_id = null, session_id = null, callback = null) {
                // console.log('Opening admit modal with:', { id, course_id, session_id });
                try {
                    $('#admitModal #user_id').val(user_id);
                    $('#admitModal #course_id').val(course_id);
                    $('#admitModal #session_id').val(session_id);
                    if (course_id) {
                        $('#admitModal button[type="submit"]').text('Change Admission');
                        $('#admitModal #change').val('true');
                    } else {
                        $('#admitModal button[type="submit"]').text('Admit');
                        $('#admitModal #change').val('false');
                    }

                    // Move the event listener setup *outside* the 'if (callback)' block.
                    $('[name="admit_form"]').off('submit').on('submit', function(
                        e) { // Use .off() first to prevent duplicates
                        if (!this.formSubmitted) { // Check if preventDefault has already been called
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            this.formSubmitted = true; // Set a flag to indicate that it has been called
                            //  alert('Form submission prevented!'); //  For debugging
                            if (callback) {
                                callback(); // Call the callback function
                            }

                        }
                    });

                    $('#admitModal').modal('show');
                } catch (e) {
                    console.error('Error opening modal:', e);
                }
            };

            $(document).ready(function() {

                $('select[multiple][data-filter]').multiSelect({
                    selectableHeader: "<div class='multi-select-legend'>Available Options</div>",
                    selectionHeader: "<div class='multi-select-legend'>Selected Options</div>",
                    afterSelect: function(values) {
                        updateDataTable();
                    },
                    afterDeselect: function(values) {
                        updateDataTable();
                    }
                });

                var table = $('#studentsTable').DataTable({
                    dom: 'Bfrtip',
                    buttons: [{
                        extend: 'csv',
                        text: '<i class="fas fa-file-csv"></i> Export CSV',
                        className: 'btn btn-success',
                        title: 'Students_Export_' + new Date().toISOString().slice(0, 10),
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5]
                        }
                    }],
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('admin.shortlisted_students') }}",
                        type: "GET",
                        data: function(d) {
                            d.draw = d.draw;
                            d.start = d.start;
                            d.length = d.length;
                            isFilterApplied = false;

                            $('select[multiple][data-filter]').each(function() {
                                var filterName = $(this).data('filter');
                                var selected = $(this).val();
                                if (selected && selected.length > 0 && !selected.includes("0")) {
                                    d[filterName] = selected;
                                    isFilterApplied = true;
                                }
                            });
                            if ($('#studentSearch').val()) {
                                d['filter[search_term]'] = $('#studentSearch').val();
                                isFilterApplied = true;
                            }
                        },
                        dataSrc: function(json) {
                            allFilteredIds = json.all_filtered_ids || [];
                            return json.data;
                        },
                        error: function(xhr, error, thrown) {
                            console.log("AJAX Error:", xhr.responseText);
                            $('#studentsTable tbody').html(
                                '<tr><td colspan="7" class="text-center text-danger">Error loading data. Please try again.</td></tr>'
                            );
                        }
                    },
                    columns: [{
                            data: 'checkbox',
                            name: 'checkbox',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                return '<input type="checkbox" class="student-checkbox" value="' + row
                                    .userId + '" ' +
                                    (isFilterApplied ? 'checked' : '') + '>';
                            }
                        },
                        {
                            data: null,
                            name: 'name',
                            render: function(data, type, row) {
                                return row.name + ' (' + row.email + ')';
                            }
                        },
                        {
                            data: 'admitted',
                            name: 'admitted',
                            render: function(data, type, row) {
                                if (data) {
                                    return '<span class="badge badge-primary">Admitted</span>';
                                } else {
                                    return '<span class="badge badge-danger">Not Admitted</span>';
                                }
                            }
                        },
                        {
                            data: 'shortlist',
                            name: 'shortlist',
                            render: function(data, type, row) {
                                return '<span class="badge badge-success">Shortlisted</span>';
                            }
                        },
                        {
                            data: 'course_name',
                            name: 'course_name',
                            render: function(data, type, row) {
                                return data ? data : 'N/A';
                            }
                        },
                        {
                            data: 'session_name',
                            name: 'session_name',
                            render: function(data, type, row) {
                                return data ? data : 'N/A';
                            }
                        },

                        {
                            data: 'actions',
                            name: 'actions',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                var actionDropdown = '<div class="dropdown">' +
                                    '<button class="btn btn-info dropdown-toggle" type="button" id="actionDropdown_' +
                                    row.userId +
                                    '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                                    'Action' +
                                    '</button>' +
                                    '<div class="dropdown-menu" aria-labelledby="actionDropdown_' + row
                                    .userId + '">';

                                if (!row.admitted) {
                                    actionDropdown +=
                                        '<a class="dropdown-item admit-btn" href="javascript:void(0);" data-id="' +
                                        row.userId + '">Admit</a>';
                                } else {
                                    actionDropdown +=
                                        '<a class="dropdown-item admit-btn" href="javascript:void(0);" data-id="' +
                                        row.userId +
                                        '" data-course_id="' + (row.course_id || '') +
                                        '" data-session_id="' + (row.session_id || '') +
                                        '">Change Admission</a>';

                                    if (row.session_name) {
                                        actionDropdown += '<a class="dropdown-item" href="' +
                                            "{{ url('student/select-session') }}" + '/' + row.userId +
                                            '" target="_blank">Choose Session</a>';
                                    }

                                    if (row.session_name) {
                                        actionDropdown +=
                                            '<a class="dropdown-item delete-admission" href="javascript:void(0);" data-userid="' +
                                            row.userId + '">Delete Admission</a>';
                                    }
                                }

                                actionDropdown += '</div></div>';

                                return actionDropdown;
                            }
                        }
                    ],
                    columnDefs: [{
                            targets: 0,
                            width: "5%"
                        },
                        {
                            targets: -1,
                            width: "15%"
                        }
                    ],
                    order: [
                        [1, 'asc']
                    ],
                    drawCallback: function(settings) {
                        if (isFilterApplied) {
                            $('.student-checkbox').prop('checked', true);
                            manuallySelectedIds = [...allFilteredIds];
                        } else {
                            manuallySelectedIds = [];
                        }
                        var allChecked = $('.student-checkbox:not(:checked)').length === 0;
                        $('#select-all').prop('checked', allChecked);
                    }
                });

                var allFilteredIds = [];
                var manuallySelectedIds = [];
                var isFilterApplied = false;
                var debounceTimer;







                $(document).on('click', '.admit-btn', function() {
                    console.log('Admit button clicked');

                    var userId = $(this).data('userId');
                    var course_id = $(this).data('course_id') || null;
                    var session_id = $(this).data('session_id') || null;
                    if (!userId) {
                        console.error('No user ID found for admit button');
                        return;
                    }
                    if (typeof window.openAdmitModal === 'function') {
                        window.openAdmitModal(userId, course_id, session_id);
                    } else {
                        console.error('openAdmitModal is not defined');
                    }
                });

                $('#studentSearch').on('keyup', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        table.ajax.reload();
                    }, 500);
                });

                $('select[multiple][data-filter]').on('change', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        table.ajax.reload();
                    }, 300);
                });

                $('#clear-filters').click(function() {
                    $('select[multiple][data-filter]').each(function() {
                        $(this).val(['0']);
                        $(this).multiSelect('deselect_all');
                        $(this).multiSelect('select', '0');
                    });
                    $('#studentSearch').val('');
                    table.ajax.reload();
                    $('#select-all').prop('checked', false);
                    manuallySelectedIds = [];
                });

                $(document).on('change', '.student-checkbox', function() {
                    var studentId = $(this).val();
                    if ($(this).is(':checked')) {
                        if (!manuallySelectedIds.includes(studentId)) {
                            manuallySelectedIds.push(studentId);
                        }
                    } else {
                        manuallySelectedIds = manuallySelectedIds.filter(userId => userId != studentId);
                    }
                    var allChecked = $('.student-checkbox:not(:checked)').length === 0;
                    $('#select-all').prop('checked', allChecked);
                });

                $('#select-all').change(function() {
                    var isChecked = $(this).prop('checked');
                    $('.student-checkbox').prop('checked', isChecked);
                    if (isChecked) {
                        manuallySelectedIds = [...allFilteredIds];
                    } else {
                        manuallySelectedIds = [];
                    }
                });

                $('#admit-selected').click(function() {
                    var selectedIds = manuallySelectedIds.length > 0 ? manuallySelectedIds : allFilteredIds;
                    if (!selectedIds || selectedIds.length === 0) {
                        toastr.warning('No students selected or no students match your filters');
                        return;
                    }

                    var btn = $(this);
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                    // console.log('Student IDs: ', selectedIds)


                    Swal.fire({
                        title: 'Admit Students?',
                        text: `You are about to admit ${selectedIds.length} students. Continue?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, admit them',
                        cancelButtonText: 'Cancel',
                        showDenyButton: true,
                        denyButtonText: 'Yes, but change admission',
                        customClass: {
                            denyButton: 'btn btn-primary',
                            confirmButton: 'btn btn-success'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ route('admin.admit_student') }}",
                                type: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                data: {
                                    student_ids: selectedIds
                                },
                                success: function(response) {
                                    toastr.success(response.message ||
                                        'Students admitted successfully!');
                                    table.ajax.reload();
                                    manuallySelectedIds = [];
                                },
                                error: function(xhr) {
                                    toastr.error(xhr.responseJSON?.message ||
                                        'Failed to admit students.');
                                    console.error(xhr.responseText);
                                },
                                complete: function() {
                                    btn.prop('disabled', false).html('Admit Students');
                                }
                            });
                        } else if (result.isDenied) {
                            openAdmitModal('', null, null, function() {
                                // $('#user_ids').val(JSON.stringify(selectedIds));
                                // Clear any existing input elements with the same name
                                const arrayInputName = 'user_ids';
                                $(`input[name="${arrayInputName}[]"]`).remove();

                                // Create multiple hidden input elements, one for each value in the array
                                selectedIds.forEach(function(id) {
                                    if (id)
                                        $('<input>')
                                        .attr('type', 'hidden')
                                        .attr('name', arrayInputName +
                                            '[]') // Append '[]' to the name
                                        .attr('value', id)
                                        .appendTo(
                                            'form[name="admit_form"]'
                                        ); // Append to the form
                                });

                                $('[name="admit_form"]').submit();
                            });
                        } else {
                            btn.prop('disabled', false).html('Admit Students');
                        }
                    });
                });

                $('#admitModal #course_id').on('change', function() {
                    var courseId = $(this).val();
                    $('#admitModal #session_id option').each(function() {
                        $(this).toggle($(this).attr('data-course') === courseId || !$(this).attr(
                            'data-course'));
                    });
                });

                var modal = $('#bulk-email-modal');
                $(modal).on('modalAction', function(event) {

                    const message = event.detail.message;
                    const subject = event.detail.subject;
                    const template = event.detail.template;
                    if (!subject || (!message && !template)) {
                        toastr.error('You need a message/template and a subject');
                        return;
                    }
                    var selectedIds = manuallySelectedIds.length > 0 ? manuallySelectedIds : allFilteredIds;
                    $.ajax({
                        url: "{{ route('admin.send_bulk_email') }}",
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            student_ids: selectedIds,
                            subject,
                            message,
                            template
                        },
                        success: function(response) {
                            toastr.success(response.message ||
                                'Emails transfer initiated successfully!');
                            $(modal).modal('hide');
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'Failed to send emails.');
                            console.error(xhr.responseText);
                        }
                    });
                });

                function updateDataTable() {
                    $('#studentsTable').DataTable().ajax.reload();
                }



                $(document).ready(function() {
                    const modal = $('#bulk-sms-modal');
                    const templateSelect = $('#sms_template');
                    const messageBox = $('#sms_message');

                    // Load templates when the modal opens
                    modal.on('show.bs.modal', function() {

                        templateSelect.empty().append(
                            '<option selected disabled>Loading templates...</option>');

                        $.get("{{ route('admin.fetch.sms.template') }}", function(templates) {
                            templateSelect.empty().append(
                                '<option value="" disabled selected>Select a template</option>'
                            );

                            $.each(templates, function(index, template) {
                                const option = $('<option></option>')
                                    .val(template.id)
                                    .text(template.name)
                                    .data('content', template
                                        .content); // store SMS content
                                templateSelect.append(option);
                            });
                        }).fail(function() {
                            toastr.error('Failed to load SMS templates.');
                            templateSelect.empty().append(
                                '<option value="" disabled selected>Unable to load templates</option>'
                            );
                        });
                    });

                    // When a template is selected, auto-fill the message box
                    templateSelect.on('change', function() {
                        const selectedOption = $(this).find('option:selected');
                        const content = selectedOption.data('content');
                        if (content) {
                            messageBox.val(content);
                        }
                    });

                    // Submit button handler
                    $(document).on('click', '#modal-submit', function() {
                        const message = messageBox.val();
                        //const subject = $('#sms_subject').val();
                        const template = templateSelect.val();

                        const modalActionEvent = new CustomEvent('modalAction', {
                            detail: {
                                message,
                                // subject,
                                template,
                                modalId: 'bulk-sms-modal',
                            },
                            bubbles: true,
                            cancelable: true,
                        });

                        document.getElementById('bulk-sms-modal').dispatchEvent(modalActionEvent);
                    });

                    // Handle actual AJAX submission
                    modal.on('modalAction', function(event) {
                        const {
                            message,
                            subject,
                            template
                        } = event.detail;

                        if ((!message && !template)) {
                            toastr.error('You need a message/template and a subject');
                            return;
                        }

                        //const selectedIds = typeof manuallySelectedIds !== 'undefined' && manuallySelectedIds.length > 0 ? manuallySelectedIds: allFilteredIds;

                        var selectedIds = manuallySelectedIds.length > 0 ? manuallySelectedIds :
                            allFilteredIds;
                        if (!selectedIds || selectedIds.length === 0) {
                            toastr.warning('No students selected or no students match your filters');
                            return;
                        }
                        console.log('Student IDs: ', selectedIds)

                        $.ajax({
                            url: "{{ route('admin.send_bulk_sms') }}",
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            },
                            data: {
                                student_ids: selectedIds,
                                //subject,
                                message,
                                //template
                            },
                            success: function(response) {
                                toastr.success(response.message ||
                                    'SMS transfer initiated successfully!');
                                modal.modal('hide');
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON?.message ||
                                    'Failed to send SMS to students.');
                            }
                        });
                    });
                });







                $(document).on('click', '.delete-admission', function(e) {
                    e.preventDefault();
                    const userId = $(this).data('userid');
                    const deleteUrl = "{{ url('admin/delete-student-admission') }}/" + userId;

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Are you sure you want to remove this student from the shortlist and delete their admission?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Proceed with deletion via AJAX
                            $.ajax({
                                url: deleteUrl,
                                type: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(response) {
                                    toastr.success(response.message ||
                                        'Admission deleted successfully!');
                                    table.ajax.reload();
                                },
                                error: function(xhr) {
                                    toastr.error(xhr.responseJSON?.message ||
                                        'Failed to delete admission.');
                                    console.error(xhr.responseText);
                                }
                            });
                        }
                    });
                });






            });
        </script>



        <script>
            $(document).on('click', '#shortlist-modal-submit', function() {
                const rawEmails = $('#email_list').val();
                const emailList = rawEmails
                    .split(/\r?\n/)
                    .map(email => email.trim())
                    .filter(email => email !== '');

                if (emailList.length === 0) {
                    toastr.error('Please paste at least one valid email address/ phonenumber.');
                    return;
                }
                // determine if emails or phonenumbers
                const sendingEmails = emailList[0].includes('@');
                const sendingPhones = emailList[0].includes('+');

                let dataToSend;

                if (sendingEmails) {
                    dataToSend = {
                        emails: emailList,
                    }
                } else if (sendingPhones) {
                    dataToSend = {
                        phone_numbers: emailList,
                    }
                } else {
                    toastr.error('Please paste at least one valid email address/ phonenumber.');
                    return;
                }


                $.ajax({
                    url: "{{ route('admin.save_shortlisted_students') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    },
                    data: dataToSend,
                    success: function(response) {
                        toastr.success(response.message || 'Users updated successfully.');
                        $('#shortlisted_students').modal('hide');

                        // Reload after short delay
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Error updating shortlisted users.');
                    }
                });
            });
        </script>
    @endpush
@endsection
