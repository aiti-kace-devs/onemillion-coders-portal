<div class="modal fade" id="<?php echo e($id); ?>" tabindex="-1" role="dialog" aria-labelledby="<?php echo e($id); ?>Label"
    aria-hidden="true">
    <div class="modal-dialog <?php echo e($size ?? ''); ?>" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?php echo e($id); ?>Label"><?php echo e($title ?? 'Modal Title'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" inert>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php echo e($slot); ?>

            </div>
            <?php if(isset($footer)): ?>
                <div class="modal-footer">
                    <?php echo e($footer); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\episo\Documents\AptitudeTest\themes\dashboard\views/components/modal.blade.php ENDPATH**/ ?>