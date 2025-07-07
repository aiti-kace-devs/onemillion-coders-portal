
<?php $__env->startSection('title', 'View Attendance'); ?>
<?php $__env->startSection('content'); ?>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">View Attendance Report</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">View Attendance Report</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <form class="row d-flex align-items-center mb-4" method="POST"
                                action="<?php echo e(route('admin.generateReport')); ?>">
                                <?php echo csrf_field(); ?>
                                <div class="col-12 col-md-10 d-flex row gap-8">
                                    <div class=" col-md-4 col-12 mb-3">
                                        <label for="report_type" class="form-label">Report Type </label>
                                        <select name="report_type" id="report_type" class="form-control">
                                            <option value="course_summary"
                                                <?php if($report_type == 'course_summary'): ?> selected <?php endif; ?>>
                                                Course Attendance Summary</option>
                                            <option value="session_summary"
                                                <?php if($report_type == 'session_summary'): ?> selected <?php endif; ?>>
                                                Course Session Attendance Summary</option>
                                            <option value="student_summary"
                                                <?php if($report_type == 'student_summary'): ?> selected <?php endif; ?>>
                                                Student Attendance Summary</option>

                                        </select>
                                    </div>
                                    <div class="col-md-4 col-12 mb-3">
                                        <label for="daily" class="form-label">Daily?</label>
                                        <select name="daily" id="daily" class="form-control" aria-hidden="true">
                                            <option value="no" <?php if('no' == ($selectedDailyOption ?? null)): ?> selected <?php endif; ?>>No
                                            </option>
                                            <option value="yes" <?php if('yes' == ($selectedDailyOption ?? null)): ?> selected <?php endif; ?>>Yes
                                            </option>
                                        </select>
                                    </div>
                                    <div id="course_dropdown" class="col-md-4 col-12 mb-3 none">
                                        <label for="course" class="form-label">Select Course</label>
                                        <select multiple name="course_id[]" id="course_id" class="form-control"
                                            aria-hidden="true">
                                            <option value="0" <?php if('0' == $selectedCourse): ?> selected <?php endif; ?>>All
                                                Courses
                                            </option>
                                            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($course->id); ?>"
                                                    <?php if($course->id == ($selectedCourse['id'] ?? null)): ?> selected <?php endif; ?>>
                                                    <?php echo e($course->course_name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-12 mb-3">
                                        <label for="dates">Select Date</label>
                                        <input type="text" name="dates" id="selected_date" class="form-control"
                                            value="<?php echo e($dates); ?>" required>
                                    </div>
                                    <div class="col-md-4 col-12 mb-3">
                                        <label for="dates">Virtual Weeks</label>
                                        <select multiple name="virtual_week[]" id="virtual_week" class="form-control"
                                            aria-hidden="true">
                                            <?php
                                                use Carbon\CarbonImmutable;

                                                $en = CarbonImmutable::now()->locale('en_UK');
                                                $weeks = $en->weeksInYear();
                                                $format = 'd M';

                                            ?>
                                            <?php for($i = 1; $i <= $weeks; $i++): ?>
                                                <option value="<?php echo e($i); ?>"
                                                    <?php if(in_array($i, $virtual_week)): ?> selected <?php endif; ?>> Week
                                                    <?php echo e($i); ?> -
                                                    (<?php echo e($en->week($i)->startOfWeek()->format($format)); ?> -
                                                    <?php echo e($en->week($i)->endOfWeek()->format($format)); ?>)
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-12 mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="showEmoji" checked
                                            onchange="toggleEmoji()">
                                        <label class="form-check-label" for="showEmoji">
                                            Show Emoji
                                        </label>

                                    </div>
                                </div>
                                <div class="col-12 col-md-2">
                                    <input type="submit" class="btn btn-success mt-2" value="Generate Report" />
                                    <button type="submit" class="btn btn-primary mt-2" name="action" value="download">
                                        <i class="fas fa-download mr-1"></i> Download Report
                                    </button>
                                </div>


                            </form>

                            <div class="card-body">
                                <?php if($report_type): ?>
                                    <h4 class="text-uppercase mb-2 text-primary" id="reportHeading">
                                        <?php echo e($selectedCourse['course_name'] ?? ''); ?>

                                        <?php echo e(str_replace('_', ' ', $report_type)); ?>

                                        Report For
                                        <?php echo e($dates); ?></h4>
                                <?php endif; ?>
                                <table class="table table-striped table-bordered table-hover datatable">
                                    <thead>
                                        
                                        <tr>
                                            <?php if($report_type == 'course_summary' || $report_type == 'session_summary'): ?>
                                                <th>Course Name</th>
                                                <th>Min</th>
                                                <th>Max</th>
                                                <th>Average</th>
                                                <th>Total</th>
                                            <?php else: ?>
                                                <th>Student Name</th>
                                                <th>Course Name</th>
                                                <?php if($virtualQuery): ?>
                                                    <th>Virtual</th>
                                                    <th>In-Person</th>
                                                <?php endif; ?>
                                                <th>Session</th>
                                                <th>Total</th>
                                                
                                                
                                            <?php endif; ?>
                                            <?php if($selectedDailyOption == 'yes'): ?>
                                                <?php $__currentLoopData = $dates_array; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <th><?php echo e($date); ?></th>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($report_type == 'course_summary' || $report_type == 'session_summary'): ?>
                                            <?php $__currentLoopData = $attendanceData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course => $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($course); ?></td>
                                                    <td><?php echo e($record->first()->values()[0]->min); ?></td>
                                                    <td><?php echo e($record->first()->values()[0]->max); ?></td>
                                                    <td><?php echo e(floor($record->first()->values()[0]->average ?? 0)); ?></td>
                                                    <td><?php echo e($record->first()->values()[0]->attendance_total); ?></td>
                                                    <?php if($selectedDailyOption == 'yes'): ?>
                                                        <?php $__currentLoopData = $dates_array; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <th><?php echo e($record->get($date)?->values()[0]->total ?? 0); ?></th>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php endif; ?>

                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>

                                        <?php if($report_type == 'student_summary'): ?>
                                            <?php $__currentLoopData = $studentAttendanceData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td class="text-lowercase">
                                                        <span>
                                                            <span
                                                                class="text-uppercase"><?php echo e($record->first()[0]->user_name); ?></span>(<?php echo e($record->first()[0]->email); ?>)
                                                        </span>
                                                    </td>
                                                    <td><?php echo e($record->first()[0]->course_name); ?>

                                                    </td>
                                                    <?php if($virtualQuery): ?>
                                                        <td><?php echo e($record->first()->values()[0]->virtual_attendance ?? 0); ?>

                                                        </td>
                                                        <td><?php echo e($record->first()->values()[0]->in_person); ?></td>
                                                    <?php endif; ?>
                                                    <td><?php echo e($record->first()[0]->session_name); ?></td>
                                                    <td><?php echo e($record->first()->values()[0]->attendance_total ?? 0); ?> </td>
                                                    
                                                    
                                                    <?php if($selectedDailyOption == 'yes'): ?>
                                                        <?php $__currentLoopData = $dates_array; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <?php
                                                                $attended = $record->get($date)?->values()[0]
                                                                    ->attendance_date;
                                                            ?>
                                                            <td class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                                                'attendance-style' => true,
                                                                'yes' => $attended,
                                                                'no' => !$attended,
                                                            ]); ?>">
                                                                <span class="content">
                                                                    <?php echo e($attended ? 'YES' : 'NO'); ?>

                                                                </span>
                                                            </td>
                                                            
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php endif; ?>

                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>

                                    </tbody>
                                    <tfoot>

                                    </tfoot>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
        </div>
        </section>
    </div>
    <!-- /.content-header -->
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="<?php echo e(url('assets/js/jquery-multiselect.min.js')); ?>"></script>

    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?>>
        $(document).ready(function() {
            $('input[name="dates"]').daterangepicker({
                showWeekNumbers: true,
                locale: {
                    format: 'MMMM D, YYYY'
                },
                ranges: {
                    'Start to Date': [moment('2024-10-14'), moment()],
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')]
                }
            });

            toggleEmoji();
        });

        function toggleCourseDropdown() {
            const reportType = document.getElementById('report_type').value;
            const courseDropdown = document.getElementById('course_dropdown');
            if (reportType === 'student_summary') {
                courseDropdown.style.display = 'block';
            } else {
                courseDropdown.style.display = 'none';
            }
        }
        document.getElementById('report_type').addEventListener('change', toggleCourseDropdown);
        document.querySelector('form').addEventListener('submit', function(event) {
            toggleCourseDropdown();
        });
        toggleCourseDropdown();

        $(document).prop('title', $('#reportHeading').text());
        $('#course_id').multiSelect();
        $('#virtual_week').multiSelect();

        function toggleEmoji() {
            const showEmoji = $('#showEmoji').is(':checked');
            if (showEmoji) {
                $('td.attendance-style').addClass('emoji');
                $('td.attendance-style.yes > .content').text('✅');
                $('td.attendance-style.no > .content').text('❌');
                setTimeout(() => {
                    $('.dtr-data > .content').each(function(i, el) {
                        const ele = $(el);
                        if (ele.text().trim().toLowerCase() == 'yes') {
                            ele.text('✅');
                        } else {
                            ele.text('❌');
                        }
                    });

                }, 100);

            } else {
                $('td.attendance-style').removeClass('emoji');
                $('td.attendance-style.yes > .content').text('YES');
                $('td.attendance-style.no > .content').text('NO');
                setTimeout(() => {
                    $('.dtr-data > .content').each(function(i, el) {
                        const ele = $(el);
                        if (ele.text().trim().toLowerCase() == '✅') {
                            ele.text('YES');
                        } else {
                            ele.text('NO');
                        }
                    });
                }, 100);

            }

            $('.datatable').DataTable().responsive.rebuild();
            $('.datatable').DataTable().responsive.recalc();

        }

        $('body').on('click', '.dtr-control', function() {
            const showEmoji = $('#showEmoji').is(':checked');
            if (showEmoji) {
                $('.dtr-data > .content').each(function(i, el) {
                    const ele = $(el);
                    if (ele.text().trim().toLowerCase() == 'yes') {
                        ele.text('✅');
                    } else {
                        ele.text('❌');
                    }
                });
            } else {
                $('.dtr-data > .content').each(function(i, el) {
                    const ele = $(el);
                    if (ele.text().trim().toLowerCase() == '✅') {
                        ele.text('YES');
                    } else {
                        ele.text('NO');
                    }
                });
            }
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\episo\Documents\AptitudeTest\themes\dashboard\views/admin/reports.blade.php ENDPATH**/ ?>