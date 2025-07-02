<x-modal id="bulk-email-modal" title="Send Bulk Email" size="modal-lg">
    <label for="subject">Subject</label>
    <input type="text" class="form-control mb-3" name="subject" id="email_subject" placeholder="Email Subject">

    <label for="subject">Select Template To Use</label>
    <select name="email_template" id="email_template" class="form-control">
        <option value="" selected></option>
        @foreach ($mailable as $mailer)
            <option>{{ $mailer }}</option>
        @endforeach
    </select>

    <label for="message">Or Write Message</label>
    <x-wysiwyg></x-wysiwyg>
    <x-slot name="footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button id="modal-submit" type="button" class="btn btn-primary">Submit</button>
    </x-slot>
</x-modal>

<script @nonce>
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
