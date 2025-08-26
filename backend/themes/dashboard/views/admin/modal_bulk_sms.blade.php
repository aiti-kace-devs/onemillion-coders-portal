<div class="modal fade" id="bulk-sms-modal" tabindex="-1" role="dialog" aria-labelledby="bulkSmsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkSmsModalLabel">Send Bulk SMS TESTING</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <label for="sms_template">Select Template To Use</label>
                <select name="sms_template" id="sms_template" class="form-control">
                    <option value="" selected disabled>Loading templates...</option>
                </select>
                <br>
                <label for="sms_message">Or Write Message</label>
                <textarea class="form-control mb-3" name="sms_message" id="sms_message" placeholder="Type your SMS message here..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="modal-submit" type="button" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>
