<div class="modal fade" id="bulkEmailModal" tabindex="-1" role="dialog" aria-labelledby="bulkEmailModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkEmailModalLabel">Send Bulk Email</h5>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkEmailForm">
                    <input type="hidden" id="bulkEmailStudentIds" name="student_ids">
                    <label for="subject">Subject</label>
                    <input type="text" class="form-control mb-3" name="subject" id="email_subject"
                        placeholder="Email Subject">
                    <label for="subject">Select Template To Use</label>
                    <select name="email_template" id="email_template" class="form-control">
                        <option value="" selected></option>
                        @foreach ($mailable as $mailer)
                            <option>{{ $mailer }}</option>
                        @endforeach
                    </select>

                    <label for="message">Or Write Message</label>
                    <x-wysiwyg></x-wysiwyg>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="bulk-email-modal-submit" type="submit" class="btn btn-primary"
                    form="bulkEmailForm">Send</button>
            </div>
        </div>
    </div>
</div>
