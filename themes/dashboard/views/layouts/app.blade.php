<!DOCTYPE html>
<html lang="en">

<head>
    <script type="text/javascript">
        BASE_URL = "<?php echo url(''); ?>"
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> @yield('title')</title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets') }}/images/logo.png">
    <link rel="icon" type="image/png" href="{{ asset('assets') }}/images/logo.png">


    <link href="{{ asset('assets') }}/toastr/toastr.min.css" rel="stylesheet" />
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ url('assets/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet"
        href="{{ url('assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <!-- iCheck -->
    <link rel="stylesheet" href="{{ url('assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- JQVMap -->
    <link rel="stylesheet" href="{{ url('assets/plugins/jqvmap/jqvmap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ url('assets/dist/css/adminlte.min.css') }}">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="{{ url('assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="{{ url('assets/plugins/daterangepicker/daterangepicker.css') }}">
    <!-- summernote -->
    <link rel="stylesheet" href="{{ url('assets/plugins/summernote/summernote-bs4.min.css') }}">
    {{-- <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css"> --}}
    <link rel="stylesheet" href="{{ url('assets/js/jquery-multiselect.min.css') }}">

    <link rel="stylesheet" href="{{ url('assets/plugins/datatables-new/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/plugins/datatables-new/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/plugins/datatables-new/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="//unpkg.com/@highlightjs/cdn-assets@11.4.0/styles/default.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"
        integrity="sha512-aVKKRRi/Q/YV+4mjoKBsE4x3H+BkegoM/em46NNlCqNTmUYADjBbeNefNxYV7giUp0VxICtqdrbqU7iVaeZNXA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    @stack('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Preloader -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="{{ url('assets/dist/img/AdminLTELogo.png') }}" alt="AdminLTELogo"
                height="60" width="60">
        </div>

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Navbar Search -->
                {{-- <li class="nav-item">
                    <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                        <i class="fas fa-search"></i>
                    </a>
                    <div class="navbar-search-block">
                        <form class="form-inline">
                            <div class="input-group input-group-sm">
                                <input class="form-control form-control-navbar" type="search" placeholder="Search"
                                    aria-label="Search">
                                <div class="input-group-append">
                                    <button class="btn btn-navbar" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </li> --}}

                <!-- Notifications Dropdown Menu -->
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="{{ route('admin.dashboard') }}" class="brand-link text-truncate">
                <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel (optional) -->

                <!-- SidebarSearch Form -->


                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
                        <li class="nav-item">
                            <a href="{{ url('admin/dashboard') }}"
                                class="nav-link @if (request()->is('admin/dashboard')) active @endif">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>
                                    Dashboard
                                </p>
                            </a>
                        </li>

                        @can('category.read')
                            <li class="nav-item">
                                <a href="{{ url('admin/exam_category') }}"
                                    class="nav-link @if (request()->is('admin/exam_category')) active @endif">
                                    <i class="fas fa-list-alt nav-icon"></i>
                                    <p>Category</p>
                                </a>
                            </li>
                        @endcan

                        @can('branch.read')
                            <li class="nav-item">
                                <a href="{{ route('admin.branch.index') }}"
                                    class="nav-link @if (isset($activePage) && $activePage == 'manageBranch') active @endif">
                                    <i class="fas fa-sitemap nav-icon"></i>
                                    <p>Manage Branch</p>
                                </a>
                            </li>
                        @endcan

                        @can('centre.read')
                            <li class="nav-item">
                                <a href="{{ route('admin.centre.index') }}"
                                    class="nav-link @if (isset($activePage) && $activePage == 'manageCentre') active @endif">
                                    <i class="fas fa-university nav-icon"></i>
                                    <p>Manage Centre</p>
                                </a>
                            </li>
                        @endcan

                        @can('course.read')
                            <li class="nav-item">
                                <a href="{{ route('admin.programme.index') }}"
                                    class="nav-link @if (isset($activePage) && $activePage == 'manageProgramme') active @endif">
                                    <i class="fas fa-book-open nav-icon"></i>
                                    <p>Manage Programme</p>
                                </a>
                            </li>
                        @endcan

                        @can('course.read')
                            <li class="nav-item">
                                <a href="{{ route('admin.course.index') }}"
                                    class="nav-link @if (isset($activePage) && $activePage == 'manageCourse') active @endif">
                                    <i class="fas fa-book nav-icon"></i>
                                    <p>Manage Course</p>
                                </a>
                            </li>
                        @endcan
                        {{-- <li class="nav-item">
                            <a href="{{ route('admin.period.index')}}" class="nav-link @if (isset($activePage) && $activePage == 'managePeriod') active @endif">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Manage Period</p>
                        </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.class.schedule.index')}}" class="nav-link @if (isset($activePage) && $activePage == 'manageClassSchedule') active @endif">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Manage Class Schedule</p>
                            </a>
                        </li> --}}
                        @can('exam.read')
                            <li class="nav-item">
                                <a href="{{ url('admin/manage_exam') }}"
                                    class="nav-link @if (request()->is('admin/manage_exam')) active @endif">
                                    <i class="fas fa-file nav-icon"></i>
                                    <p>Manage Exam</p>
                                </a>
                            </li>
                        @endcan

                        @can('admin.read')
                            <li class="nav-item">
                                <a href="{{ url('admin/manage_admins') }}"
                                    class="nav-link @if (request()->is('admin/manage_admins')) active @endif">
                                    <i class="fas fa-user-shield nav-icon"></i>
                                    <p>Manage Admin</p>
                                </a>
                            </li>
                        @endcan

                        {{-- @can('student.read') --}}
                        <li class="nav-item">
                            <a href="{{ url('admin/manage_students') }}"
                                class="nav-link @if (request()->is('admin/manage_students')) active @endif">

                                <i class="fas fa-user nav-icon"></i>
                                <p>Students</p>
                            </a>
                        </li>
                        {{-- @endcan --}}

                        @can('student.admit')
                            <li class="nav-item">
                                <a href="{{ url('admin/shortlisted_students') }}"
                                    class="nav-link @if (request()->is('admin/shortlisted_students')) active @endif">
                                    <i class="fas fa-user-check nav-icon"></i>
                                    <p>Shortlisted students</p>

                                </a>
                            </li>


                            <li class="nav-item">
                                <a href="{{ url('admin/registered_students') }}"
                                    class="nav-link @if (request()->is('admin/registered_students')) active @endif">
                                    <i class="fas fa-user-check nav-icon"></i>
                                    <p>Registered students</p>
                                </a>
                            </li>
                        @endcan

                        @can('sms-template.read')
                            <li class="nav-item">
                                <a href="{{ url('admin/manage-sms-template') }}"
                                    class="nav-link @if (request()->is('admin/manage-sms-template')) active @endif">
                                    <i class="fas fa-clipboard-list nav-icon"></i>
                                    <p>SMS Templates</p>
                                </a>
                            </li>
                        @endcan
                        {{-- <li class="nav-item">
                            <a href="{{ url('admin/generate_qrcode') }}" class="nav-link">
                        <i class="fas fa-qrcode nav-icon"></i>
                        <p>Generate QR Code</p>
                        </a>
                        </li> --}}
                        @can('attendance.read')
                            <li class="nav-item">
                                <a href="{{ url('admin/scan_qrcode') }}"
                                    class="nav-link @if (request()->is('admin/scan_qrcode')) active @endif">
                                    <i class="fas fa-camera nav-icon"></i>
                                    <p>Scan/Generate QR Code</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ url('admin/verification') }}"
                                    class="nav-link @if (request()->is('admin/verification')) active @endif">
                                    <i class="fas fa-id-card nav-icon"></i>
                                    <p>Student Verification</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ url('admin/view_attendance') }}"
                                    class="nav-link @if (request()->is('admin/view_attendance')) active @endif">
                                    <i class="fas fa-clipboard-list nav-icon"></i>
                                    <p>View Attendance</p>
                                </a>
                            </li>
                        @endcan

                        @can('report.view')
                            <li class="nav-item">
                                <a href="{{ url('admin/reports') }}"
                                    class="nav-link @if (request()->is('admin/reports')) active @endif">
                                    <i class="fas fa-file-alt nav-icon"></i>
                                    <p>Generate Report</p>
                                </a>
                            </li>
                        @endcan


                        @canany(['session.read', 'form.read', 'form-response.read'])
                            <li class="nav-item">
                                <a href="{{ route('admin.form.index') }}" class="nav-link">
                                    <i class="fas fa-external-link-square-alt nav-icon"></i>
                                    <p>Go To RVMP Portal</p>
                                </a>
                            </li>
                        @endcanany

                        @can('manage.page-editor')
                            <li class="nav-item">
                                <a href="{{ url('/admin/builder/manage') }}" class="nav-link">
                                    <i class="fas fa-external-link-square-alt nav-icon"></i>
                                    <p>Manage Pages</p>
                                </a>
                            </li>
                        @endcan

                        {{-- @can('manage.config')
                            <li class="nav-item">
                                <a href="{{ route(config('env-editor.route.name')) }}" class="nav-link">
                                    <i class="fas fa-external-link-square-alt nav-icon"></i>
                                    <p>Logs</p>
                                </a>
                            </li>
                        @endcan --}}

                        @can('manage.monitor')
                            <li class="nav-item">
                                <a href="{{ url(config('horizon.path', 'horizon')) }}" class="nav-link">
                                    <i class="fas fa-external-link-square-alt nav-icon"></i>
                                    <p>Monitor Queues</p>
                                </a>
                            </li>
                        @endcan


                        <li class="nav-item">
                            <a href="{{ url('admin/logout') }}" class="nav-link">
                                <i class="fas fa-sign-out-alt nav-icon"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                        <!--
          <li class="nav-item menu-open">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Dashboard
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="./index.html" class="nav-link active">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Dashboard v1</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="./index2.html" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Dashboard v2</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="./index3.html" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Dashboard v3</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="pages/widgets.html" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Widgets
                <span class="right badge badge-danger">New</span>
              </p>
            </a>
          </li> -->

                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>





        @yield('content')


        <!-- /.content-wrapper -->


        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ url('assets/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="{{ url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- ChartJS -->
    <script src="{{ url('assets/plugins/chart.js/Chart.min.js') }}"></script>
    <!-- Sparkline -->
    <script src="{{ url('assets/plugins/sparklines/sparkline.js') }}"></script>
    <!-- JQVMap -->
    <script src="{{ url('assets/plugins/jqvmap/jquery.vmap.min.js') }}"></script>
    <script src="{{ url('assets/plugins/jqvmap/maps/jquery.vmap.usa.js') }}"></script>
    <!-- jQuery Knob Chart -->
    <script src="{{ url('assets/plugins/jquery-knob/jquery.knob.min.js') }}"></script>
    <!-- daterangepicker -->
    <script src="{{ url('assets/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ url('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
    {{-- <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script> --}}
    <!-- Tempusdominus Bootstrap 4 -->
    {{-- <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script> --}}
    {{--
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script> --}}
    {{-- --}}
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.24/build/pdfmake.min.js"></script>

    {{-- <script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script> --}}

    {{-- datatables --}}
    <script src="{{ url('assets/plugins/datatables-new/jquery.dataTables.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/dataTables.responsive.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/dataTables.buttons.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/jszip.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/pdfmake.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/vfs_fonts.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/buttons.html5.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/buttons.print.min.js') }}"></script>
    <script src="{{ url('assets/plugins/datatables-new/buttons.colVis.min.js') }}"></script>
    <script type="text/javascript" src="{{ url('assets/js/jquery-multiselect.min.js') }}"></script>

    {{-- end datatables  --}}
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="{{ url('assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <!-- Summernote -->
    <script src="{{ url('assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
    <!-- overlayScrollbars -->
    <script src="{{ url('assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ url('assets/dist/js/adminlte.js') }}"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="{{ url('assets/dist/js/demo.js') }}"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="{{ url('assets/dist/js/pages/dashboard.js') }}"></script>
    <script src="{{ url('assets/js/custom.js') }}"></script>
    <script src="{{ asset('assets/toastr/toastr.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('.datatable')) {
                $('.datatable').DataTable().destroy();
            }
            $('.datatable').DataTable({
                columnDefs: [{
                    width: "15%",
                    targets: -1
                }, ],
                "responsive": true,
                "lengthChange": true,
                "autoWidth": true,
                "buttons": ["copy", "csv", {
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    pageSize: 'A3'
                }]
            }).buttons().container().appendTo('.dataTables_wrapper .col-md-6:eq(0)');

            const title = document.title;
            if (!title.includes("{{ config('app.name') }}")) {
                document.title = document.title + " - {{ config('app.name') }}"
            }
        });
    </script>
    <script>
        const flashMessage = "{{ session('flash') }}";
        const key = "{{ session('key') }}";

        if (flashMessage) {
            setTimeout(() => {
                Swal.fire({
                    text: flashMessage,
                    icon: key || 'info'
                })
            }, 500);
        }
    </script>
    @stack('scripts')
</body>

</html>
