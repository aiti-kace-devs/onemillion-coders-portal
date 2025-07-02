<!DOCTYPE html>
<html lang="en">

<head>
    <script @nonce type="text/javascript">
        BASE_URL = "<?php echo url(''); ?>"
    </script>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> @yield('title')</title>

    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets') }}/images/logo.png">
    <link rel="icon" type="image/png" href="{{ asset('assets') }}/images/logo.png">


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
    <link rel="stylesheet" href="{{ url('assets/plugins/datatables-new/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/plugins/datatables-new/responsive.bootstrap4.min.css') }}">
    <link href="{{ asset('themes/student/css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ url('assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('/assets/plugins/daterangepicker/daterangepicker.css') }}" />




    {{-- end datatables  --}}

</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Preloader -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="{{ url('assets/images/logo-bt.png') }}" alt="OneMillionCodersLogo"
                height="70">
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

        @unless (isset($noSide) == true && !Auth::user())
            <!-- Main Sidebar Container -->
            <aside class="main-sidebar sidebar-dark-primary elevation-4">
                <!-- Brand Logo -->
                <a href="/" class="brand-link">
                    <img height="50" width="50" src="{{ asset('assets') }}/images/logo.png">

                    <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
                </a>

                <!-- Sidebar -->
                <div class="sidebar">
                    <!-- Sidebar user panel (optional) -->

                    <!-- SidebarSearch Form -->
                    {{-- <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div> --}}

                    <!-- Sidebar Menu -->
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                            data-accordion="false">
                            <!-- Add icons to the links using the .nav-icon class
                                                                                                                                                                                                                                                                   with font-awesome or any other icon font library -->
                            @if (!Auth::user()->isAdmitted())
                                <li class="nav-item">
                                    <a href="{{ url('student/dashboard') }}"
                                        class="nav-link {{ request()->is('student/dashboard') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-tachometer-alt"></i>
                                        <p>
                                            Dashboard
                                        </p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ url('student/exam') }}"
                                        class="nav-link {{ request()->is('student/exam') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-book"></i>
                                        <p>
                                            Aptitude Test
                                        </p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('student.profile') }}"
                                        class="nav-link {{ request()->is('student/profile') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-user"></i>
                                        <p>
                                            My Profile
                                        </p>
                                    </a>
                                </li>
                            @endif
                            @if (Auth::user()->hasAdmission())
                                <li class="nav-item">
                                    <a href="{{ url('student/select-session/' . Auth::user()->userId) }}"
                                        class="nav-link {{ request()->is('student/select-session*') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-check"></i>
                                        <p>
                                            Choose Session
                                        </p>
                                    </a>
                                </li>
                            @endif
                            {{-- @if (!Auth::user()->isAdmitted()) --}}
                            <li class="nav-item">
                                <a href="{{ url('student/application-status') }}"
                                    class="nav-link {{ request()->is('student/application-status') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-clipboard"></i>
                                    <p>
                                        Application Status
                                    </p>
                                </a>
                            </li>
                            {{-- @endif --}}
                            @if (Auth::user()->isAdmitted())
                                <li class="nav-item">
                                    <a href="{{ url('student/id-qrcode') }}" class="nav-link">
                                        <i class="nav-icon fas fa-qrcode"></i>
                                        <p>
                                            My ID (QR)
                                        </p>
                                    </a>
                                </li>

                                {{-- <li class="nav-item">
                                    <a href="{{ url('student/meeting-link') }}" class="nav-link">
                                        <i class="nav-icon fas fa-video"></i>
                                        <p>
                                            Class Meeting Link
                                        </p>
                                    </a>
                                </li> --}}

                                <li class="nav-item">
                                    <a href="{{ url('student/attendance') }}"
                                        class="nav-link {{ request()->is('student/attendance') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-calendar"></i>
                                        <p>
                                            Attendance
                                        </p>
                                    </a>
                                </li>
                            @endif
                            <li class="nav-item">
                                <a href="{{ url('student/logout') }}"
                                    class="nav-link {{ request()->is('student/logout') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user"></i>
                                    <p>
                                        Logout
                                    </p>
                                </a>
                            </li>
                            <!--


                                                                                                                                                                                                                                                            </ul>
                                                                                                                                                                                                                                                          </nav>
                                                                                                                                                                                                                                                          <! /.sidebar-menu -->
                </div>
                <!-- /.sidebar -->
            </aside>
        @endunless




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
    <script src="{{ url('assets/plugins/jquery/jquery.min.js') }}"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ url('assets/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script @nonce>
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
    <!-- Tempusdominus Bootstrap 4 -->

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

    <script @nonce type="text/javascript">
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
                "buttons": [{
                    extend: 'pdfHtml5',
                    orientation: 'portrait',
                    pageSize: 'A4'
                }]
            }).buttons().container().appendTo('.dataTables_wrapper .col-md-6:eq(0)');



            document.addEventListener('contextmenu', function(ev) {
                ev.preventDefault();
                return false;
            }, false);

            const title = document.title;
            if (!title.includes("{{ config('app.name') }}")) {
                document.title = document.title + " - {{ config('app.name') }}"
            }
        });
    </script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> --}}
    <script src="{{ url('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <link rel="stylesheet" href=>

    <script @nonce>
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
