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
                        <h1 class="m-0">Manage Students</h1>
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
                                            @can('student.create')
                                                {{-- <a class="btn btn-info mr-2" href="javascript:;" data-toggle="modal"
                                                    data-target="#myModal">Add new student</a> --}}
                                            @endcan
                                            @can('student.bulk-sms')
                                                <button class="btn btn-warning mr-2" data-toggle="modal"
                                                    data-target="#bulk-email-modal">Send Emails
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                                <button class="btn btn-success mr-2" data-toggle="modal"
                                                    data-target="#bulk">Send SMS
                                                    <i class="fas fa-sms"></i>
                                                </button>
                                            @endcan
                                            @can('student.admit')
                                                <button class="btn btn-primary mr-2" id="shortlist-selected">Shortlist
                                                    Students</button>
                                            @endcan
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
                                        <div class="col-md-2">
                                            <select multiple name="status[]" id="status" class="form-control"
                                                data-filter="status" aria-hidden="true">
                                                <option value="0">All Statuses</option>
                                                <option value="passed">Passed</option>
                                                <option value="failed">Failed</option>
                                                <option value="not_taken">Not Taken</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select multiple name="age_range[]" id="age_range" class="form-control"
                                                data-filter="age_range" aria-hidden="true">
                                                <option value="0">All Ages</option>
                                                @foreach ($availableAges as $age)
                                                    <option value="{{ $age }}">{{ $age }} years</option>
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
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Age</th>
                                            <th>Course</th>
                                            <th>Location</th>
                                            <th>Gender</th>
                                            <th>Date Registered</th>
                                            <th>Admission</th>
                                            <th>Score</th>
                                            {{-- <th>Result</th> --}}
                                            <th>Status</th>
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


    <div class="modal fade" id="myModal" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add new Student</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('admin/add_new_students') }}" class="database_operation">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label for="">Enter Name</label>
                                    {{ csrf_field() }}
                                    <input type="text" required="required" name="name" placeholder="Enter name"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label for="">Enter E-mail</label>
                                    {{ csrf_field() }}
                                    <input type="text" required="required" name="email" placeholder="Enter name"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label for="">Enter Mobile-no</label>
                                    {{ csrf_field() }}
                                    <input type="text" required="required" name="mobile_no"
                                        placeholder="Enter mobile-no" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label for="">Select exam</label>
                                    <select class="form-control" required="required" name="exam">
                                        <option value="">Select</option>
                                        @foreach ($exams as $exam)
                                            <option value="{{ $exam['id'] }}">{{ $exam['title'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label for="">password</label>
                                    <input type="password" required="required" name="password"
                                        placeholder="Enter password" class="form-control">
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <button class="btn btn-primary">Add</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>



        @push('scripts')
            <script type="text/javascript" src="{{ url('assets/js/jquery-multiselect.min.js') }}"></script>

            <script>
                var allFilteredIds = [];
                var manuallySelectedIds = [];
                var isFilterApplied = false;
                var debounceTimer;

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
                        buttons: [
                            @can('student.admit')
                                {
                                    extend: 'csv',
                                    text: '<i class="fas fa-file-csv"></i> Export CSV',
                                    className: 'btn btn-success',
                                    title: 'Students_Export_' + new Date().toISOString().slice(0, 10),
                                    exportOptions: {
                                        columns: [1, 2, 3, 4, 5, 6, 7, 9],
                                    }
                                }
                            @endcan
                        ],
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "{{ route('admin.manage_students') }}",
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
                                    '<tr><td colspan="10" class="text-center text-danger">Error loading data. Please try again.</td></tr>'
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
                                        .id + '" ' +
                                        (isFilterApplied ? 'checked' : '') + '>';
                                }
                            },
                            {
                                data: 'name',
                                name: 'users.name'
                            },
                            {
                                data: 'email',
                                name: 'users.email'
                            },
                            {
                                data: 'age',
                                name: 'users.age'
                            },
                            {
                                data: 'course_name',
                                name: 'course_name'
                            },
                            {
                                data: 'course_location',
                                name: 'course_location'
                            },
                            {
                                data: 'gender',
                                name: 'users.gender'
                            },
                            {
                                data: 'date_registered',
                                name: 'users.created_at'
                            },
                            {
                                data: 'admission_status',
                                name: 'admission_status'
                            },
                            {
                                data: 'score',
                                name: 'score',
                                orderable: false
                            },
                            // {
                            //     data: 'result',
                            //     name: 'result',
                            //     orderable: false
                            // },
                            {
                                data: 'status',
                                name: 'status',
                                orderable: false
                            },
                            {
                                data: 'actions',
                                name: 'actions',
                                orderable: false
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
                        updateDataTable();
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
                            manuallySelectedIds = manuallySelectedIds.filter(id => id != studentId);
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

                    $('#shortlist-selected').click(function() {
                        var selectedIds = manuallySelectedIds.length > 0 ? manuallySelectedIds : allFilteredIds;

                        if (!selectedIds || selectedIds.length === 0) {
                            toastr.warning('No students selected or no students match your filters');
                            return;
                        }

                        var btn = $(this);
                        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

                        Swal.fire({
                            title: 'Shortlist Students?',
                            text: `You are about to shortlist ${selectedIds.length} students. Continue?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, shortlist them',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: "{{ route('admin.save_shortlisted_students') }}",
                                    type: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                            'content'),
                                    },
                                    data: {
                                        student_ids: selectedIds
                                    },
                                    success: function(response) {
                                        toastr.success(response.message ||
                                            'Students shortlisted successfully!');
                                        table.ajax.reload();
                                        manuallySelectedIds = [];
                                    },
                                    error: function(xhr) {
                                        toastr.error(xhr.responseJSON?.message ||
                                            'Failed to shortlist students.');
                                        console.error(xhr.responseText);
                                    },
                                    complete: function() {
                                        btn.prop('disabled', false).html('Shortlist Students');
                                    }
                                });
                            } else {
                                btn.prop('disabled', false).html('Shortlist Students');
                            }
                        });
                    });
                });



                var modal = $('#bulk-email-modal');


                $(modal).on('modalAction', function(event) {
                    const message = event.detail.message;
                    const subject = event.detail.subject;
                    const template = event.detail.template;


                    if (!subject || (!message && !template)) {
                        toastr.error('Your need a message/template and a subject');
                        return;
                    }
                    var selectedIds = manuallySelectedIds.length > 0 ? manuallySelectedIds : allFilteredIds;
                    $.ajax({
                        url: "{{ route('admin.send_bulk_email') }}",
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                'content'),
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
                            $(modal).modal('hide')
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

                });
            </script>
        @endpush
    @endsection
