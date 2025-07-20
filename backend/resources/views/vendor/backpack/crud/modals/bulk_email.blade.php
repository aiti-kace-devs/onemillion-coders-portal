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
                        <select name="email_template" id="email_template" class="form-control">
                            <option value="" selected></option>
                            @foreach ($mailable as $mailer)
                                <option value="{{ $mailer }}">{{ $mailer }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label for="message">Or Write Message</label>
                    <x-wysiwyg id="email_message" />
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
    @basset('js')
    <script>
        (function() {
            // Initialize WYSIWYG editor if not already initialized
            if (window.ClassicEditor && !window.bulkEmailEditor) {
                ClassicEditor.create(document.querySelector('#email_message'))
                    .then(editor => {
                        window.bulkEmailEditor = editor;
                    })
                    .catch(error => {
                        console.error(error);
                    });
            }

            // Handle template selection (if you want to auto-fill message based on template, add AJAX here)
            $('#email_template').on('change', function() {
                // Optionally, fetch template content via AJAX and set to editor
                // Example:
                // let template = $(this).val();
                // if (template) {
                //     $.get('/admin/email-template-content?template=' + encodeURIComponent(template), function(data) {
                //         if(window.bulkEmailEditor) window.bulkEmailEditor.setData(data.content);
                //     });
                // }
            });

            // Handle submit
            $('#bulk-email-modal-submit').on('click', function(e) {
                e.preventDefault();
                let subject = $('#email_subject').val();
                let template = $('#email_template').val();
                let message = window.bulkEmailEditor ? window.bulkEmailEditor.getData() : $('#email_message')
                    .val();

                // Use SweetAlert2 for validation feedback
                if (!subject || (!message && !template)) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Missing Information',
                            text: 'You need a message/template and a subject',
                        });
                    } else {
                        alert('You need a message/template and a subject');
                    }
                    return;
                }

                // Find the form and submit via AJAX (or trigger the Backpack handler)
                let ids = typeof crud !== 'undefined' && crud.checkedItems ? crud.checkedItems : [];
                if (ids.length === 0) {
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
                    url: "{{ route('bulk-email.send') }}",
                    method: 'POST',
                    data: {
                        student_ids: ids,
                        subject: subject,
                        message: message,
                        template: template,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(resp) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: resp.message || 'Emails sent successfully!'
                            });
                        } else {
                            alert(resp.message || 'Emails sent successfully!');
                        }
                        var modal = bootstrap.Modal.getInstance(document.getElementById(
                            'bulkEmailModal'));
                        if (modal) modal.hide();
                    },
                    error: function(xhr) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'Failed to send emails.'
                            });
                        } else {
                            alert(xhr.responseJSON?.message || 'Failed to send emails.');
                        }
                    }
                });
            });
        })();
    </script>
    @endbasset
@endpush
