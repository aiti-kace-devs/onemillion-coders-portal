
<?php $__env->startSection('title', 'Portal dashboard'); ?>
<?php $__env->startSection('content'); ?>
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">My Details</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active">My Details</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if(session('message')): ?>
                    <div class="alert alert-success">
                        <?php echo e(session('message')); ?>

                    </div>
                <?php elseif(session('error')): ?>
                    <div class="alert alert-danger">
                        <?php echo e(session('error')); ?>

                    </div>
                <?php endif; ?>

                <?php
                    function detailsUpdated($user)
                    {
                        return $user->details_updated_at != null;
                    }
                ?>
                <!-- Small boxes (Stat box) -->
                <form action="<?php echo e(route('student.updateDetails')); ?>" method="POST" name="student-details">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row g-3 flex mb-2 align-items-center">
                        <div class="col-12 mb-2">
                            <label class="form-label col-12">Fullname (as appears on your Ghana Card/ any National ID)
                            </label>
                            <input id="name" type="text" required value=" <?php echo e($user->student_name); ?>" name="name"
                                class="form-control col-12  <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                <?php if(detailsUpdated($user)): ?> disabled <?php endif; ?>>
                            <?php if($user->previous_name): ?>
                                <div class="text-primary">Previous Name: <?php echo e($user->previous_name); ?></div>
                            <?php endif; ?>
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-12 mb-2">
                            <label class="form-label col-12">Card Type</label>
                            <select id="card_type" name="card_type"
                                class="form-control <?php $__errorArgs = ['card_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                <?php if(detailsUpdated($user)): ?> disabled <?php endif; ?> required>
                                <option value="">Select Card Type</option>
                                <option value="ghcard"
                                    <?php echo e(old('card_type', $user->card_type) === 'ghcard' ? 'selected' : ''); ?>>Ghana Card
                                </option>
                                <option value="voters_id"
                                    <?php echo e(old('card_type', $user->card_type) === 'voters_id' ? 'selected' : ''); ?>>Voter's
                                    ID</option>
                                <option value="drivers_license"
                                    <?php echo e(old('card_type', $user->card_type) === 'drivers_license' ? 'selected' : ''); ?>>
                                    Driver's
                                    License</option>
                                <option value="passport"
                                    <?php echo e(old('card_type', $user->card_type) === 'passport' ? 'selected' : ''); ?>>Passport
                                </option>
                            </select>
                            <?php $__errorArgs = ['card_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="input-group col-12 mb-2">
                            <label class="form-label col-12">Card ID</label>
                            <?php if($user->card_type === 'ghcard' || $user->card_type == null): ?>
                                <div id="ghana-card-prefix" class="input-group-prepend none">
                                    <span class="input-group-text" id="basic-addon1">GHA-</span>
                                </div>
                            <?php endif; ?>
                            <input id="ghcard" type="text" required value="<?php echo e(old('ghcard', $user->ghcard)); ?>"
                                name="ghcard" placeholder="123456789-1" <?php if(detailsUpdated($user)): ?> disabled <?php endif; ?>
                                class="form-control  <?php $__errorArgs = ['ghcard'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                <?php if(!empty($user->verification_date)): ?> is-valid <?php else: ?> is-invalid <?php endif; ?>
                                          col-12 mr-2">
                            <?php $__errorArgs = ['ghcard'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php else: ?>
                                
                                <?php if(empty($user->verification_date)): ?>
                                    <div class="invalid-feedback">
                                        Card not verified (This will be done manually by an administrator)
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($user->verification_date)): ?>
                                    <div class="valid-feedback">
                                        Card Verified Successfully
                                    </div>
                                <?php endif; ?>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                        </div>

                        <div class="col-12 mb-2">
                            <label class="form-label col-12">Gender</label>
                            <select id="gender" name="gender" class="form-control"
                                <?php if($user->gender): ?> disabled <?php endif; ?> required>
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo e($user->gender === 'male' ? 'selected' : ''); ?>>Male</option>
                                <option value="female" <?php echo e($user->gender === 'female' ? 'selected' : ''); ?>>Female</option>
                            </select>
                        </div>

                        <div class="col-12 mb-2">
                            <label class="form-label col-12">Network Type</label>
                            <select id="network_type" name="network_type"
                                class="form-control  <?php $__errorArgs = ['network_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                <?php if($user->network_type): ?> disabled <?php endif; ?> required>
                                <option value="">Select Network</option>
                                <option value="mtn"
                                    <?php echo e(old('network_type', $user->network_type) === 'mtn' ? 'selected' : ''); ?>>MTN</option>
                                <option value="telecel"
                                    <?php echo e(old('network_type', $user->network_type) === 'telecel' ? 'selected' : ''); ?>>Telecel
                                </option>
                                <option value="airteltigo"
                                    <?php echo e(old('network_type', $user->network_type) === 'airteltigo' ? 'selected' : ''); ?>>
                                    AirtelTigo</option>
                            </select>
                            <?php $__errorArgs = ['network_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="input-group col-12 mb-2">
                            <label class="form-label col-12">Phone Number</label>
                            
                            <input id="mobile_no" type="text" required value="<?php echo e($user->mobile_no); ?>" name="mobile_no"
                                placeholder="201234567" <?php if($user->mobile_no): ?> disabled <?php endif; ?>
                                class="form-control <?php $__errorArgs = ['mobile_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> col-12 mr-2">
                        </div>
                        <?php $__errorArgs = ['mobile_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div role="alert" class="alert alert-danger"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>


                        <div class="col-12">
                            
                            <?php if(detailsUpdated($user)): ?>
                                <p class="text-sm text-danger">You have already updated your details</p>
                            <?php else: ?>
                                <button id="confirmUpdateDetails" type="button" class="btn btn-primary">Update</button>
                                <p class="text-sm text-danger">You can only update your details once, make sure you verify
                                    all
                                    details before submitting.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <?php if($user->isAdmitted()): ?>
                    <div class="text-md">Location : <?php echo e($user->location); ?> </div>
                    <div class="text-md">Course : <?php echo e($user->course_name); ?></div>
                    <div class="text-md">Session : <?php echo e($user->selected_session); ?></div>
                    <div class="text-lg font-bold mt-2">Student ID for Attendance</div>
                    <div id="qrcode"></div>
                    <button type="button" class="btn btn-primary" id="downloadQRCode">Download</button>
                <?php endif; ?>
                <!-- /.row -->
                <!-- Main row -->

                <!-- /.row (main row) -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>

<?php $__env->stopSection(); ?>


<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('assets/js/jquery.inputmask.bundle.min.js')); ?>"></script>
    <script src="<?php echo e(asset('assets/js/easy.qrcode.min.js')); ?>"></script>
    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?>>
        const innerWidth = Math.floor(window.innerWidth * (7 / 9));
        const width = innerWidth > 400 ? 400 : innerWidth
        const qrcode = new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo e(Auth::user()->userId); ?>",
            width: width,
            height: width,
            colorDark: "black",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H,
            quietZone: 20,
            logo: "<?php echo e(asset('assets/images/logo-bt.png')); ?>",
            logoWidth: 170,
            logoHeight: 80,
        });


        function downloadQRCode() {
            qrcode.download("StudentName-<?php echo e(Auth::user()->name); ?>")
        }

        $(document).ready(function() {
            const cardTypeSelect = $("#card_type");
            const ghcardInput = $("#ghcard");
            const downloadQRButton = $('#downloadQRCode');

            downloadQRButton.on('click', function() {
                downloadQRCode();
            })

            function toggleInputMask() {
                if (cardTypeSelect.val() === "ghcard") {
                    ghcardInput.inputmask({
                        mask: "555555555-5",
                        definitions: {
                            "5": {
                                validator: "[0-9]",
                            },
                        },
                    });
                } else {
                    ghcardInput.inputmask("remove");
                }
            }

            cardTypeSelect.on("change", toggleInputMask);

            toggleInputMask();
        });


        // $("#ghcard").inputmask({
        //     mask: "555555555-5",
        //     definitions: {
        //         '5': {
        //             validator: "[0-9]"
        //         },
        //     }
        // });

        $('#confirmUpdateDetails').on('click', function() {
            confirmUpdateDetails();
        });

        function confirmUpdateDetails() {
            Swal.fire({
                title: 'Confirm Submission',
                text: `Are you sure you want to submit this update. This cannot be undone. Make sure all details are correct`,
                icon: 'info',
                backdrop: `rgba(0,0,0,0.95)`,
                confirmButtonText: 'Yes, Submit',
                cancelButtonText: 'No, Cancel',
                showCancelButton: true,
                allowOutsideClick: false,
                preConfirm: async () => {
                    $('[name="student-details"]').submit()
                }
            })
        }

        document.addEventListener("DOMContentLoaded", function() {
            const cardTypeSelect = document.getElementById("card_type");
            const ghanaCardPrefix = document.getElementById("ghana-card-prefix");

            function togglePrefix() {
                if (cardTypeSelect.value === "ghcard") {
                    ghanaCardPrefix.style.display = "flex";
                } else {
                    ghanaCardPrefix.style.display = "none";
                }
            }

            togglePrefix();

            cardTypeSelect.addEventListener("change", togglePrefix);
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.student', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\episo\Documents\AptitudeTest\themes\dashboard\views/student/id-qr.blade.php ENDPATH**/ ?>