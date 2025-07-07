<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'bulk-email-modal','title' => 'Send Bulk Email','size' => 'modal-lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'bulk-email-modal','title' => 'Send Bulk Email','size' => 'modal-lg']); ?>
    <label for="subject">Subject</label>
    <input type="text" class="form-control mb-3" name="subject" id="email_subject" placeholder="Email Subject">

    <label for="subject">Select Template To Use</label>
    <select name="email_template" id="email_template" class="form-control">
        <option value="" selected></option>
        <?php $__currentLoopData = $mailable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mailer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option><?php echo e($mailer); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>

    <label for="message">Or Write Message</label>
    <?php if (isset($component)) { $__componentOriginal7c6180b19be4f1691096406ffdcb7998 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7c6180b19be4f1691096406ffdcb7998 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.wysiwyg','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('wysiwyg'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7c6180b19be4f1691096406ffdcb7998)): ?>
<?php $attributes = $__attributesOriginal7c6180b19be4f1691096406ffdcb7998; ?>
<?php unset($__attributesOriginal7c6180b19be4f1691096406ffdcb7998); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7c6180b19be4f1691096406ffdcb7998)): ?>
<?php $component = $__componentOriginal7c6180b19be4f1691096406ffdcb7998; ?>
<?php unset($__componentOriginal7c6180b19be4f1691096406ffdcb7998); ?>
<?php endif; ?>
     <?php $__env->slot('footer', null, []); ?> 
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button id="modal-submit" type="button" class="btn btn-primary">Submit</button>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>

<script <?php echo "nonce=\"" . csp_nonce() . "\""; ?>>
    // const modal = document.getElementById('bulk-email-modal');
    const modalSubmit = document.getElementById('modal-submit');

    modalSubmit.addEventListener('click', function() {
        const message = simplemde.value()
        const subject = document.getElementById('email_subject').value;
        const template = document.getElementById('email_template').value;

        const modalActionEvent = new CustomEvent('modalAction', {
            detail: {
                message,
                subject,
                template,
                modalId: 'bulk-email-modal',
            },
            bubbles: true, // Allow the event to bubble up the DOM
            cancelable: true,
        });

        // Dispatch the event to the modal
        document.getElementById('bulk-email-modal').dispatchEvent(modalActionEvent);
    });
</script>
<?php /**PATH C:\Users\episo\Documents\AptitudeTest\themes\dashboard\views/admin/send-bulk-email.blade.php ENDPATH**/ ?>