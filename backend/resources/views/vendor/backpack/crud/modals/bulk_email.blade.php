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
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="la la-angle-down"></i></span>
                        <select name="template" id="email_template" class="form-control">
                            <option value="" selected></option>
                            @foreach ($mailable as $mailer)
                                <option value="{{ $mailer }}">{{ $mailer }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label for="message">Or Write Message</label>
                    <x-wysiwyg id="email_message" name="message" />
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

@push('after_scripts')
    @bassetBlock('custom/js/bulk-email.js')
        <script>
            (function() {
                // Handle template selection (if you want to auto-fill message based on template, add AJAX here)
                $('#email_template').on('change', function() {
                    // Optionally, fetch template content via AJAX and set to editor
                    // Example:
                    // let template = $(this).val();
                    // if (template) {
                    //     // load template content into the editor
                    // }
                });

                $('#bulkEmailForm').off('submit').on('submit', function(e) {
                    e.preventDefault();
                    let ids = getCheckedStudentIds();
                    let data = $(this).serializeArray();
                    // Ensure the SimpleMDE content is sent as `message`
                    if (window.simplemde && typeof window.simplemde.value === 'function') {
                        data.push({
                            name: 'message',
                            value: window.simplemde.value()
                        });
                    }
                    let applyToAll = typeof shouldApplyToAll === 'function' ?
                        shouldApplyToAll() :
                        ids.length === 0;

                    if (!applyToAll && ids.length === 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'No students selected',
                                text: 'Select students first or filter to a view with students!'
                            });
                        }
                        return;
                    }

                    if (applyToAll) {
                        data.push({
                            name: 'select_all_in_query',
                            value: 1
                        });
                    } else {
                        ids.forEach(function(id) {
                            data.push({
                                name: 'student_ids[]',
                                value: id
                            });
                        });
                    }

                    $.ajax({
                        url: "{{ route('bulk-email.send') }}",
                        method: 'POST',
                        data: data,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(resp) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: resp.message || 'Emails sent successfully!'
                                }).then(() => window.location.reload());
                            }
                            if (typeof toastr !== 'undefined') {
                                toastr.success(resp.message || 'Emails sent successfully!');
                            }
                            var modal = bootstrap.Modal.getInstance(document.getElementById(
                                'bulkEmailModal'));
                            if (modal) modal.hide();
                        },
                        error: function(xhr) {
                            let errorMsg = xhr.responseJSON?.message || 'Failed to send emails.';
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message || 'Failed to send emails.'
                                });
                            }
                            if (typeof toastr !== 'undefined') {
                                toastr.error(errorMsg);
                            }
                        }
                    });
                });
            })();
        </script>
    @endBassetBlock
@endpush
