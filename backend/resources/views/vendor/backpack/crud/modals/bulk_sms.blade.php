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
@bassetBlock('custom/js/bulk-sms.js')
<script>
            $(document).ready(function() {
                const modal = $('#bulkSMSModal');
                const templateSelect = $('#sms_template');
                const messageBox = $('#sms_message');

                modal.on('show.bs.modal', function() {
                    templateSelect.empty().append(
                        '<option selected disabled>Loading templates...</option>');
                    $.get("{{ route('sms-template.fetch') }}", function(templates) {
                        templateSelect.empty().append(
                            '<option value="" disabled selected>Select a template</option>'
                        );
                        $.each(templates, function(index, template) {
                            const option = $('<option></option>')
                                .val(template.id)
                                .text(template.name)
                                .data('content', template.content);
                            templateSelect.append(option);
                        });
                    }).fail(function() {
                        toastr.error('Failed to load SMS templates.');
                        templateSelect.empty().append(
                            '<option value="" disabled selected>Unable to load templates</option>'
                        );
                    });
                });

                templateSelect.on('change', function() {
                    const selectedOption = $(this).find('option:selected');
                    const content = selectedOption.data('content');
                    if (content) {
                        messageBox.val(content);
                    }
                });

                $(document).off('click', '#modal-submit').on('click', '#modal-submit', function() {
                    const message = messageBox.val();
                    const template = templateSelect.val();
                    const ids = getCheckedStudentIds();
                    const applyToAll = shouldApplyToAll();
                    const modalActionEvent = new CustomEvent('modalAction', {
                        detail: {
                            message,
                            template,
                            modalId: 'bulkSMSModal',
                            ids: ids,
                            applyToAll: applyToAll
                        },
                        bubbles: true,
                        cancelable: true,
                    });
                    document.getElementById('bulkSMSModal').dispatchEvent(modalActionEvent);
                });

                modal.off('modalAction').on('modalAction', function(event) {
                    const {
                        message,
                        template,
                        ids,
                        applyToAll
                    } = event.detail;
                    if ((!message && !template)) {
                        toastr.error('You need a message/template and a subject');
                        return;
                    }
                    let data = {
                        message: message
                    };
                    if (applyToAll) {
                        data.select_all_in_query = 1;
                    } else {
                        data.student_ids = ids;
                    }

                    $.ajax({
                        url: "{{ route('bulk-sms.send') }}",
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        },
                        data: data,
                        success: function(response) {
                            toastr.success(response.message ||
                                'SMS transfer initiated successfully!');
                            modal.modal('hide');
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message ||
                                'Failed to send SMS to students.');
                        }
                    });
                });
            });
</script>
@endBassetBlock
@endpush
