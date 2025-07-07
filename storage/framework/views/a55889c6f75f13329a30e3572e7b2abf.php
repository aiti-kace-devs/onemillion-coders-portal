
<?php $__env->startSection('title', 'Manage Portal'); ?>
<?php $__env->startSection('content'); ?>


    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manage Portal</h1>
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
                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Title</h3>

                                    
                                </div>
                                <div class="card-body">
                                    <table class="table table-striped table-bordered table-hover datatable">
                                        <thead>
                                            <tr>
                                                <th>Name (Email)</th>
                                                <th>Admitted</th>
                                                <th>Course</th>
                                                <th>Session</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td><?php echo e($p['name']); ?> (<?php echo e($p['email']); ?>)</td>
                                                    <td>
                                                        <?php if($p['admitted']): ?>
                                                            <span class="badge badge-primary">Admitted</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Not Admitted</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo e($p['course_name']); ?></td>
                                                    <td><?php echo e($p['session_name']); ?></td>


                                                    <td>
                                                        <?php if(!$p['admitted']): ?>
                                                            <button class="btn btn-primary btn-sm"
                                                                onclick="openModal('<?php echo e($p['userId']); ?>')">Admit</button>
                                                        <?php else: ?>
                                                            <button class="btn btn-info btn-sm"
                                                                onclick="openModal('<?php echo e($p['userId']); ?>', '<?php echo e($p['course_id']); ?>', '<?php echo e($p['session_id']); ?>')">Change
                                                                Admission</button>
                                                        <?php endif; ?>
                                                        <?php if($p['admitted'] && !$p['session_name']): ?>
                                                            <a href="<?php echo e(url('/student/select-session/' . $p['userId'])); ?>"
                                                                target="_blank" class="btn btn-primary btn-sm">Choose
                                                                Session</a>
                                                        <?php endif; ?>
                                                        <?php if(Auth::user()->isSuper()): ?>
                                                            <a href="<?php echo e(url('admin/delete_registered_students/' . $p['id'])); ?>"
                                                                class="btn btn-danger btn-sm">Delete</a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                        <tfoot>
                                            <?php echo e($users->links()); ?>

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

        <!-- Modal -->
        <div class="modal fade" id="myModal" role="dialog">
            <div class="modal-dialog">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Admit Student</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form action="<?php echo e(url('/admin/admit')); ?>" name="admit_form" method="POST">
                            <div class="row">
                                <?php echo e(csrf_field()); ?>

                                <div class="col-sm-12">
                                    <input id="user_id" name="user_id" type="hidden" class="form-control" required>
                                    <input id="change" name="change" value="false" type="hidden" class="form-control"
                                        required>

                                    <div class="form-group">
                                        <label for="course_id" class="form-label">Select Course</label>
                                        <select id="course_id" name="course_id" class="form-control" required>
                                            <option value="">Choose One Course</option>
                                            <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($course->id); ?>"> <?php echo e($course->location); ?> -
                                                    <?php echo e($course->course_name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="session_id" class="form-label">Choose Session</label>
                                        <select id="session_id" name="session_id" class="form-control">
                                            <option value="">Choose One Session</option>

                                            <?php $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option data-course="<?php echo e($session->course_id); ?>"
                                                    value="<?php echo e($session->id); ?>">
                                                    <?php echo e($session->name); ?> </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <button class="btn btn-primary">Admit</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>




        <?php $__env->stopSection(); ?>

        <?php $__env->startPush('scripts'); ?>
            <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?>>
                const form = $('[name="admit_form"]')
                let selectedUser = null;

                const courseInput = $('#course_id');
                const sessionInput = $('#session_id');


                courseInput.on('change', function(e) {
                    const courseId = courseInput.val();
                    $('#session_id option').map(function(i, o) {
                        $(o).show()
                        if (courseId != $(o).attr('data-course')) {
                            $(o).hide()
                        }
                    })
                });

                form

                function openModal(id, course = null, session = null) {
                    $('#user_id').val(id);
                    $('#course_id').val(course);
                    $('#session_id').val(session);
                    if (course) {
                        $('#myModal button').text('Change Admission');
                        $('#change').val('true');

                    } else {
                        $('#myModal button').text('Admit');
                        $('#change').val('false');
                    }

                    $('#myModal').modal('show');
                }
                // form.
            </script>
        <?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\episo\Documents\AptitudeTest\themes\dashboard\views/admin/registered_students.blade.php ENDPATH**/ ?>