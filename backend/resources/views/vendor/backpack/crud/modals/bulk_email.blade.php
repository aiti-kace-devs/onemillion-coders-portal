<div class="modal fade" id="bulkEmailModal" tabindex="-1" role="dialog" aria-labelledby="bulkEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkEmailModalLabel">Send Bulk Email</h5>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkEmailForm">
                    <div class="form-group">
                        <label for="bulk_email_subject">Subject</label>
                        <input type="text" class="form-control" id="bulk_email_subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="bulk_email_message">Message</label>
                        <textarea class="form-control" id="bulk_email_message" name="message" rows="5"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="bulk_email_template">Template (optional)</label>
                        <input type="text" class="form-control" id="bulk_email_template" name="template">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="bulk-email-modal-submit" type="button" class="btn btn-primary">Send</button>
            </div>
        </div>
    </div>
</div>
