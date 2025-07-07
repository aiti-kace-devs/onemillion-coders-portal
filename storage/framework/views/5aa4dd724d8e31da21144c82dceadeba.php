<!DOCTYPE html>
<html lang="en">

<head>
    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?> type="text/javascript">
        BASE_URL = "<?php echo url(''); ?>"
    </script>
    
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> <?php echo $__env->yieldContent('title'); ?></title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo e(asset('assets')); ?>/images/logo.png">
    <link rel="icon" type="image/png" href="<?php echo e(asset('assets')); ?>/images/logo.png">
    <?php echo csp_meta_tag(\App\Helpers\BasePolicy::class) ?>


    <link href="<?php echo e(asset('assets')); ?>/toastr/toastr.min.css" rel="stylesheet" />
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/fontawesome-free/css/all.min.css')); ?>">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet"
        href="<?php echo e(url('assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')); ?>">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css')); ?>">
    <!-- JQVMap -->
    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/jqvmap/jqvmap.min.css')); ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo e(url('assets/dist/css/adminlte.min.css')); ?>">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css')); ?>">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/daterangepicker/daterangepicker.css')); ?>">
    <!-- summernote -->
    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/summernote/summernote-bs4.min.css')); ?>">
    
    <link rel="stylesheet" href="<?php echo e(url('assets/js/jquery-multiselect.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('assets/js/bootstrap-multiselect.min.css')); ?>">


    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/datatables-new/dataTables.bootstrap4.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/datatables-new/responsive.bootstrap4.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/datatables-new/buttons.bootstrap4.min.css')); ?>">
    <link rel="stylesheet" href="https://unpkg.com/@highlightjs/cdn-assets@11.4.0/styles/default.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <style <?php echo "nonce=\"" . csp_nonce() . "\""; ?>>
        .multiselect-container {
            width: 100%;
        }

        .multiselect-native-select>.btn-group {
            width: 100% !important;
        }
    </style>


    
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Preloader -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="<?php echo e(url('assets/images/logo-bt.png')); ?>" alt="OneMillionCodersLogo"
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
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="brand-link text-truncate">
                <span class="brand-text font-weight-light"><?php echo e(config('app.name')); ?></span>
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
                            <a href="<?php echo e(url('admin/dashboard')); ?>"
                                class="nav-link <?php if(request()->is('admin/dashboard')): ?> active <?php endif; ?>">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>
                                    Dashboard
                                </p>
                            </a>
                        </li>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('category.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/exam_category')); ?>"
                                    class="nav-link <?php if(request()->is('admin/exam_category')): ?> active <?php endif; ?>">
                                    <i class="fas fa-list-alt nav-icon"></i>
                                    <p>Category</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('branch.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(route('admin.branch.index')); ?>"
                                    class="nav-link <?php if(isset($activePage) && $activePage == 'manageBranch'): ?> active <?php endif; ?>">
                                    <i class="fas fa-sitemap nav-icon"></i>
                                    <p>Manage Branch</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('centre.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(route('admin.centre.index')); ?>"
                                    class="nav-link <?php if(isset($activePage) && $activePage == 'manageCentre'): ?> active <?php endif; ?>">
                                    <i class="fas fa-university nav-icon"></i>
                                    <p>Manage Centre</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('course.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(route('admin.programme.index')); ?>"
                                    class="nav-link <?php if(isset($activePage) && $activePage == 'manageProgramme'): ?> active <?php endif; ?>">
                                    <i class="fas fa-book-open nav-icon"></i>
                                    <p>Manage Programme</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('course.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(route('admin.course.index')); ?>"
                                    class="nav-link <?php if(isset($activePage) && $activePage == 'manageCourse'): ?> active <?php endif; ?>">
                                    <i class="fas fa-book nav-icon"></i>
                                    <p>Manage Course</p>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('exam.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/manage_exam')); ?>"
                                    class="nav-link <?php if(request()->is('admin/manage_exam')): ?> active <?php endif; ?>">
                                    <i class="fas fa-file nav-icon"></i>
                                    <p>Manage Exam</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('admin.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/manage_admins')); ?>"
                                    class="nav-link <?php if(request()->is('admin/manage_admins')): ?> active <?php endif; ?>">
                                    <i class="fas fa-user-shield nav-icon"></i>
                                    <p>Manage Admin</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['student.read', 'student.bulk-sms', 'student.admit', 'student.bulk-email', 'student.shortlist'])): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/manage_students')); ?>"
                                    class="nav-link <?php if(request()->is('admin/manage_students')): ?> active <?php endif; ?>">

                                    <i class="fas fa-user nav-icon"></i>
                                    <p>Students</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('student.admit')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/shortlisted_students')); ?>"
                                    class="nav-link <?php if(request()->is('admin/shortlisted_students')): ?> active <?php endif; ?>">
                                    <i class="fas fa-user-check nav-icon"></i>
                                    <p>Shortlisted students</p>

                                </a>
                            </li>


                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/registered_students')); ?>"
                                    class="nav-link <?php if(request()->is('admin/registered_students')): ?> active <?php endif; ?>">
                                    <i class="fas fa-user-check nav-icon"></i>
                                    <p>Registered students</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('sms-template.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/manage-sms-template')); ?>"
                                    class="nav-link <?php if(request()->is('admin/manage-sms-template')): ?> active <?php endif; ?>">
                                    <i class="fas fa-clipboard-list nav-icon"></i>
                                    <p>SMS Templates</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('email-template.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/manage-email-template')); ?>"
                                    class="nav-link <?php if(request()->is('admin/manage-email-template')): ?> active <?php endif; ?>">
                                    <i class="fas fa-envelope nav-icon"></i>
                                    <p>Email Templates</p>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('attendance.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/scan_qrcode')); ?>"
                                    class="nav-link <?php if(request()->is('admin/scan_qrcode')): ?> active <?php endif; ?>">
                                    <i class="fas fa-camera nav-icon"></i>
                                    <p>Scan/Generate QR Code</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/verification')); ?>"
                                    class="nav-link <?php if(request()->is('admin/verification')): ?> active <?php endif; ?>">
                                    <i class="fas fa-id-card nav-icon"></i>
                                    <p>Student Verification</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/view_attendance')); ?>"
                                    class="nav-link <?php if(request()->is('admin/view_attendance')): ?> active <?php endif; ?>">
                                    <i class="fas fa-clipboard-list nav-icon"></i>
                                    <p>View Attendance</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('report.read')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('admin/reports')); ?>"
                                    class="nav-link <?php if(request()->is('admin/reports')): ?> active <?php endif; ?>">
                                    <i class="fas fa-file-alt nav-icon"></i>
                                    <p>Generate Report</p>
                                </a>
                            </li>
                        <?php endif; ?>


                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['session.read', 'form.read', 'form-response.read'])): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(route('admin.form.index')); ?>" class="nav-link">
                                    <i class="fas fa-external-link-square-alt nav-icon"></i>
                                    <p>Go To RVMP Portal</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage.page-editor')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url('/admin/builder/manage')); ?>" class="nav-link">
                                    <i class="fas fa-external-link-square-alt nav-icon"></i>
                                    <p>Manage Pages</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        

                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage.monitor')): ?>
                            <li class="nav-item">
                                <a href="<?php echo e(url(config('horizon.path', 'horizon'))); ?>" class="nav-link">
                                    <i class="fas fa-external-link-square-alt nav-icon"></i>
                                    <p>Monitor Queues</p>
                                </a>
                            </li>
                        <?php endif; ?>


                        <li class="nav-item">
                            <a href="<?php echo e(url('admin/logout')); ?>" class="nav-link">
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





        <?php echo $__env->yieldContent('content'); ?>


        <!-- /.content-wrapper -->


        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->
    </div>
    <!-- ./wrapper -->

    <!-- jQuery -->
    <script src="<?php echo e(url('assets/plugins/jquery/jquery.min.js')); ?>"></script>

    <!-- jQuery UI 1.11.4 -->
    <script src="<?php echo e(url('assets/plugins/jquery-ui/jquery-ui.min.js')); ?>"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?>>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="<?php echo e(url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
    <!-- ChartJS -->
    <script src="<?php echo e(url('assets/plugins/chart.js/Chart.min.js')); ?>"></script>
    <!-- Sparkline -->
    <script src="<?php echo e(url('assets/plugins/sparklines/sparkline.js')); ?>"></script>
    <!-- JQVMap -->
    <script src="<?php echo e(url('assets/plugins/jqvmap/jquery.vmap.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/jqvmap/maps/jquery.vmap.usa.js')); ?>"></script>
    <!-- jQuery Knob Chart -->
    <script src="<?php echo e(url('assets/plugins/jquery-knob/jquery.knob.min.js')); ?>"></script>
    <!-- daterangepicker -->
    <script src="<?php echo e(url('assets/plugins/moment/moment.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/daterangepicker/daterangepicker.js')); ?>"></script>
    
    <!-- Tempusdominus Bootstrap 4 -->
    
    
    
    
    


    <script type="text/javascript" src="<?php echo e(url('assets/plugins/moment/moment.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(url('assets/plugins/daterangepicker/daterangepicker.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(url('assets/js/jquery-multiselect.min.js')); ?>"></script>

    

    
    <script src="<?php echo e(url('assets/plugins/datatables-new/jquery.dataTables.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/dataTables.bootstrap4.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/dataTables.responsive.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/responsive.bootstrap4.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/dataTables.buttons.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/buttons.bootstrap4.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/jszip.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/pdfmake.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/vfs_fonts.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/buttons.html5.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/buttons.print.min.js')); ?>"></script>
    <script src="<?php echo e(url('assets/plugins/datatables-new/buttons.colVis.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(url('assets/js/jquery-multiselect.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo e(url('assets/js/bootstrap-multiselect.min.js')); ?>"></script>

    
    
    <script src="<?php echo e(url('assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')); ?>"></script>
    <!-- Summernote -->
    <script src="<?php echo e(url('assets/plugins/summernote/summernote-bs4.min.js')); ?>"></script>
    <!-- overlayScrollbars -->
    <script src="<?php echo e(url('assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')); ?>"></script>
    <!-- AdminLTE App -->
    <script src="<?php echo e(url('assets/dist/js/adminlte.js')); ?>"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="<?php echo e(url('assets/dist/js/demo.js')); ?>"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="<?php echo e(url('assets/dist/js/pages/dashboard.js')); ?>"></script>
    <script src="<?php echo e(url('assets/js/custom.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/toastr/toastr.min.js')); ?>"></script>
    <link rel="stylesheet" href="<?php echo e(url('assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css')); ?>">
    <script src="<?php echo e(url('assets/plugins/sweetalert2/sweetalert2.min.js')); ?>"></script>

    

    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?> type="text/javascript">
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
            if (!title.includes("<?php echo e(config('app.name')); ?>")) {
                document.title = document.title + " - <?php echo e(config('app.name')); ?>"
            }
        });

        const flashMessage = "<?php echo e(session('flash')); ?>";
        const key = "<?php echo e(session('key')); ?>";

        if (flashMessage) {
            setTimeout(() => {
                Swal.fire({
                    text: flashMessage,
                    icon: key || 'info'
                })
            }, 500);
        }

        // $('select[multiple]').multiSelect({
        //     noneText: 'Select...',
        //     allText: 'Select All',
        //     presets: [{
        //             name: 'All',
        //             all: true
        //         },
        //         {
        //             name: 'Clear',
        //             options: []
        //         }
        //     ]
        // });
    </script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>

</html>
<?php /**PATH C:\Users\episo\Documents\AptitudeTest\themes\dashboard\views/layouts/app.blade.php ENDPATH**/ ?>