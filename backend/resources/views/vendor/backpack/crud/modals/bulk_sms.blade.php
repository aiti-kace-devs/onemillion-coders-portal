<div class="modal fade" id="bulkSMSModal" tabindex="-1" aria-labelledby="bulkSMSModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkSMSModalLabel">Send Bulk SMS</h5>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="sms_template"><strong>Select Template To Use</strong></label>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="la la-angle-down"></i></span>
                    <select name="sms_template" id="sms_template" class="form-select">
                        <option value="" selected>Select a template...</option>
                        @foreach ($smsTemplates ?? [] as $template)
                            <option>{{ $template }}</option>
                        @endforeach
                    </select>
                </div>

                <label for="sms_message">Or Write Message</label>
                <textarea class="form-control mb-3" id="sms_message" rows="5" placeholder="Type your SMS message here..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button id="modal-submit" type="button" class="btn btn-primary">Send</button>
            </div>
        </div>
    </div>
</div>

@push('after_scripts')
@basset('js')
<script>
(function() {
    // Handle template selection (if you want to auto-fill message based on template, add AJAX here)
    $('#sms_template').on('change', function() {
        // Optionally, fetch template content via AJAX and set to textarea
        // let template = $(this).val();
        // if (template) {
        //     $.get('/admin/sms-template-content?template=' + encodeURIComponent(template), function(data) {
        //         $('#sms_message').val(data.content);
        //     });
        // }
    });

    // Handle submit
    $('#modal-submit').on('click', function(e) {
        e.preventDefault();
        let message = $('#sms_message').val();
        let template = $('#sms_template').val();
        // Collect selected student IDs
        let ids = (typeof crud !== 'undefined' && crud.checkedItems && crud.checkedItems.length > 0)
            ? crud.checkedItems
            : (typeof manuallySelectedIds !== 'undefined' && manuallySelectedIds.length > 0)
                ? manuallySelectedIds
                : (typeof allFilteredIds !== 'undefined' ? allFilteredIds : []);

        // Validation
        if ((!message && !template)) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'You need a message/template',
                });
            } else {
                alert('You need a message/template');
            }
            return;
        }
        if (!ids || ids.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No students selected',
                    text: 'Select students first!',
                });
            } else {
                alert('Select students first!');
            }
            return;
        }

        $.ajax({
            url: '{{ route('bulk-sms.send') }}',
            method: 'POST',
            data: {
                student_ids: ids,
                message: message,
                template: template,
                _token: '{{ csrf_token() }}'
            },
            success: function(resp) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: resp.message || 'SMS sent successfully!'
                    });
                } else {
                    alert(resp.message || 'SMS sent successfully!');
                }
                var modal = bootstrap.Modal.getInstance(document.getElementById('bulkSMSModal'));
                if (modal) modal.hide();
            },
            error: function(xhr) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to send SMS.'
                    });
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to send SMS.');
                }
            }
        });
    });
})();
</script>
@endbasset
@endpush
