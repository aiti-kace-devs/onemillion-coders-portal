<div class="modal fade" id="bulkSMSModal" tabindex="-1" role="dialog" aria-labelledby="bulkSMSModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkSMSModalLabel">Send Bulk SMS</h5>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkSMSForm">
                    <input type="hidden" id="bulkSMSStudentIds" name="student_ids">
                    <div class="form-group">
                        <label for="bulk_sms_message">Message</label>
                        <textarea class="form-control" id="bulk_sms_message" name="message" rows="5"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="bulk_sms_template">Template (optional)</label>
                        <input type="text" class="form-control" id="bulk_sms_template" name="template">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="bulk-sms-modal-submit" type="submit" class="btn btn-primary" form="bulkSMSForm">Send</button>
            </div>
        </div>
    </div>
</div>
