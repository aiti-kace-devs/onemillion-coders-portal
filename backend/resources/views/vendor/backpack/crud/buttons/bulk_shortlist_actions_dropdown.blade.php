<!-- jQuery (must be loaded before Toastr and custom scripts) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Toastr CSS and JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<div class="dropdown d-inline-block">
    <button class="btn btn-primary dropdown-toggle" type="button" id="shortlistActionsDropdown" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="la la-list"></i> Shortlist Actions
    </button>
    <ul class="dropdown-menu" aria-labelledby="shortlistActionsDropdown">
        <li>
            <a class="dropdown-item" href="#" id="chooseShortlistBtn">
                <i class="la la-list-alt text-info"></i> Choose Shortlist
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" id="bulkEmailBtn">
                <i class="la la-envelope text-success"></i> Send Emails
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" id="bulkSMSBtn">
                <i class="la la-sms text-warning"></i> Send SMS
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" id="admit-selected">
                <i class="la la-user-check text-primary"></i> Admit Students
            </a>
        </li>
    </ul>
</div>
@include('vendor.backpack.crud.modals.bulk_email', ['mailable' => $mailable])
@include('vendor.backpack.crud.modals.bulk_sms')
@include('vendor.backpack.crud.modals.choose_shortlist')
@include('vendor.backpack.crud.modals.admit')

@push('after_scripts')
    @basset('js')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function getCheckedStudentIds() {
                if (manuallySelectedIds.length > 0) {
                    return manuallySelectedIds;
                }
                return [];
            }
            // --- BEGIN: Bulk Shortlist Actions JS ---
            $(document).on('click', '.admit-btn', function() {
                const user_id = $(this).data('id');
                const course_id = $(this).data('course_id');
                const session_id = $(this).data('session_id');

                // Call the modal function
                openAdmitModal(user_id, course_id, session_id);

            });

            window.openAdmitModal = function(user_id, course_id = null, session_id = null, callback = null) {
                // console.log('Opening admit modal with:', { id, course_id, session_id });
                try {
                    // Ensure the modal is appended to body
                    var $modal = $('#admitModal');
                    if ($modal.length) {
                        $modal.appendTo('body');
                    }
                    $('#admitModal #user_id').val(user_id);
                    $('#admitModal #course_id').val(course_id);
                    $('#admitModal #session_id').val(session_id);
                    if (course_id) {
                        $('#admitModal button[type="submit"]').text('Change Admission');
                        $('#admitModal #change').val('true');
                    } else {
                        $('#admitModal button[type="submit"]').text('Admit');
                        $('#admitModal #change').val('false');
                    }

                    // Move the event listener setup *outside* the 'if (callback)' block.
                    $('[name="admit_form"]').off('submit').on('submit', function(
                        e) { // Use .off() first to prevent duplicates
                        if (!this.formSubmitted) { // Check if preventDefault has already been called
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            this.formSubmitted = true; // Set a flag to indicate that it has been called
                            //  alert('Form submission prevented!'); //  For debugging
                            if (callback) {
                                callback(); // Call the callback function
                            }

                        }
                    });

                    $('#admitModal').modal('show');
                } catch (e) {
                    console.error('Error opening modal:', e);
                }
            };

            $(document).on('click', '.admit-btn', function() {
                console.log('Admit button clicked');

                var userId = $(this).data('userId');
                var course_id = $(this).data('course_id') || null;
                var session_id = $(this).data('session_id') || null;
                if (!userId) {
                    console.error('No user ID found for admit button');
                    return;
                }
                if (typeof window.openAdmitModal === 'function') {
                    window.openAdmitModal(userId, course_id, session_id);
                } else {
                    console.error('openAdmitModal is not defined');
                }
            });

            $('#chooseShortlistBtn').on('click', function(e) {
                e.preventDefault();
                $('#chooseShortlistModal').appendTo('body').modal('show');
            });

            $('#bulkEmailBtn').on('click', function(e) {
                e.preventDefault();
                $('#bulkEmailModal').appendTo('body').modal('show');
            });
            // Open Bulk SMS modal
            $('#bulkSMSBtn').on('click', function(e) {
                e.preventDefault();
                $('#bulkSMSModal').appendTo('body').modal('show');
            });

            // Variables to track selected IDs
            var allFilteredIds = [];
            var manuallySelectedIds = [];
            var isFilterApplied = false;
            var debounceTimer;

            // Checkbox logic using Backpack's checkboxes
            $(document).on('change', '.crud_bulk_checkbox', function() {
                var studentId = $(this).val();
                if ($(this).is(':checked')) {
                    if (!manuallySelectedIds.includes(studentId)) {
                        manuallySelectedIds.push(studentId);
                    }
                } else {
                    manuallySelectedIds = manuallySelectedIds.filter(userId => userId != studentId);
                }
                var allChecked = $('.crud_bulk_checkbox:not(:checked)').length === 0;
                $('#crud_bulk_checkbox_all').prop('checked', allChecked);
            });

            $('#crud_bulk_checkbox_all').change(function() {
                var isChecked = $(this).prop('checked');
                $('.crud_bulk_checkbox').prop('checked', isChecked);
                if (isChecked) {
                    // If you have a way to get all IDs, set them here
                    manuallySelectedIds = $('.crud_bulk_checkbox').map(function() {
                        return $(this).val();
                    }).get();
                } else {
                    manuallySelectedIds = [];
                }
            });

            // Shortlist modal submit logic
            $(document).on('click', '#shortlist-modal-submit', function() {
                const rawEmails = $('#email_list').val();
                const emailList = rawEmails
                    .split(/\r?\n/)
                    .map(email => email.trim())
                    .filter(email => email !== '');

                if (emailList.length === 0) {
                    toastr.error('Please paste at least one valid email address/ phonenumber.');
                    return;
                }

                // determine if emails or phonenumbers
                const sendingEmails = emailList[0].includes('@');
                const sendingPhones = emailList[0].includes('+');

                let dataToSend;

                if (sendingEmails) {
                    dataToSend = {
                        emails: emailList,
                    }
                } else if (sendingPhones) {
                    dataToSend = {
                        phone_numbers: emailList,
                    }
                } else {
                    toastr.error('Please paste at least one valid email address/ phonenumber.');
                    return;
                }

                $.ajax({
                    url: "{{ route('bulk-students.shortlist') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    },
                    data: dataToSend,
                    success: function(response) {
                        toastr.success(response.message || 'Users updated successfully.');
                        $('#shortlisted_students').modal('hide');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Error updating shortlisted users.');
                    }
                });
            });

            $('#admit-selected').click(function() {
                var selectedIds = getCheckedStudentIds();
                var applyToAll = selectedIds.length === 0;

                if (!applyToAll && selectedIds.length === 0) {
                    toastr.warning('No students selected or no students match your filters');
                    return;
                }

                var btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                // console.log('Student IDs: ', selectedIds)


                Swal.fire({
                    title: 'Admit Students?',
                    text: applyToAll ? `You are about to admit all students in this view. This might take a while. Continue?` :
                        `You are about to admit ${selectedIds.length} students. Continue?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, admit them',
                    cancelButtonText: 'Cancel',
                    showDenyButton: true,
                    denyButtonText: 'Yes, but change admission',
                    customClass: {
                        denyButton: 'btn btn-primary',
                        confirmButton: 'btn btn-success'
                    },
                    onBeforeOpen: () => {
                        if (applyToAll) {
                            const textElement = Swal.getHtmlContainer();
                            if (textElement) {
                                $.ajax({
                                    url: "{{ route('user.filtered-count') }}",
                                    method: 'GET',
                                    data: crud.last_list_url.split('?')[1] || '',
                                    success: function(response) {
                                        textElement.textContent =
                                            `You are about to admit ${response.count} students in the filtered query. This might take a while. Continue?`;
                                    }
                                });
                            }
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let data = {
                            _token: '{{ csrf_token() }}'
                        };
                        if (applyToAll) {
                            data.select_all_in_query = true;
                        } else {
                            data.user_ids = selectedIds;
                        }
                        $.ajax({
                            url: "{{ route('user.admit-student') }}",
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: data,
                            success: function(response) {
                                toastr.success(response.message ||
                                    'Students admitted successfully!');
                                table.ajax.reload();
                                manuallySelectedIds = [];
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON?.message ||
                                    'Failed to admit students.');
                                console.error(xhr.responseText);
                            },
                            complete: function() {
                                btn.prop('disabled', false).html('Admit Students');
                            }
                        });
                    } else if (result.isDenied) {
                        openAdmitModal('', null, null, function() {
                            // $('#user_ids').val(JSON.stringify(selectedIds));
                            // Clear any existing input elements with the same name
                            const arrayInputName = 'user_ids';
                            $(`input[name="${arrayInputName}[]"]`).remove();

                            if (applyToAll) {
                                $('<input>')
                                    .attr('type', 'hidden')
                                    .attr('name', 'select_all_in_query')
                                    .attr('value', 'true')
                                    .appendTo(
                                        'form[name="admit_form"]'
                                    );
                            } else {
                                // Create multiple hidden input elements, one for each value in the array
                                selectedIds.forEach(function(id) {
                                    if (id)
                                        $('<input>')
                                        .attr('type', 'hidden')
                                        .attr('name', arrayInputName +
                                            '[]') // Append '[]' to the name
                                        .attr('value', id)
                                        .appendTo(
                                            'form[name="admit_form"]'
                                        ); // Append to the form
                                });
                            }


                            $('[name="admit_form"]').submit();
                        });
                    } else {
                        btn.prop('disabled', false).html('Admit Students');
                    }
                });
            });

            $(document).on('click', '.change-admission-btn', function(e) {
                e.preventDefault();
                const userId = $(this).data('user-id');
                if (!userId) {
                    toastr.error('User ID not found.');
                    return;
                }

                openAdmitModal('', null, null, function() {
                    const arrayInputName = 'user_ids';
                    $(`input[name="${arrayInputName}[]"]`).remove();

                    $('<input>')
                        .attr('type', 'hidden')
                        .attr('name', arrayInputName + '[]')
                        .attr('value', userId)
                        .appendTo('form[name="admit_form"]');

                    $('form[name="admit_form"]').submit();
                });
            });

            $('#admitModal #course_id').on('change', function() {
                var courseId = $(this).val();
                $('#admitModal #session_id option').each(function() {
                    $(this).toggle($(this).attr('data-course') === courseId || !$(this).attr(
                        'data-course'));
                });
            });

            // Bulk Email modal logic
            $('#bulkEmailForm').on('submit', function(e) {
                e.preventDefault();
                let ids = getCheckedStudentIds();
                let applyToAll = ids.length === 0;

                if (!applyToAll && ids.length === 0) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'No students selected',
                            text: 'Select students first or filter to a view with students!'
                        });
                    } else {
                        alert('Select students first or filter to a view with students!');
                    }
                    return;
                }

                let data = $(this).serializeArray();
                if (applyToAll) {
                    data.push({
                        name: 'select_all_in_query',
                        value: true
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
                        var modal = bootstrap.Modal.getInstance(document.getElementById('bulkEmailModal'));
                        if (modal) modal.hide();
                    },
                    error: function(xhr) {
                        let errorMsg = xhr.responseJSON?.message || 'Failed to send emails.';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMsg
                            });
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        }
                    }
                });
            });

            // Bulk SMS modal logic
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
                    const applyToAll = ids.length === 0;
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
                        data.select_all_in_query = true;
                    } else if (ids.length > 0) {
                        data.student_ids = ids;
                    } else {
                        toastr.warning('No students selected or no students match your filters');
                        return;
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

            // Delete admission logic
            $(document).on('click', '.delete-admission-btn', function(e) {
                e.preventDefault();
                const userId = $(this).data('user-id');
                const deleteUrl = "{{ route('user.delete-admission', ['user_id' => 'USER_ID_PLACEHOLDER']) }}".replace('USER_ID_PLACEHOLDER', userId);
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Are you sure you want to remove this student from the shortlist and delete their admission?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                toastr.success(response.message ||
                                    'Admission deleted successfully!');
                                // Optionally reload or update the UI
                                location.reload();
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON?.message ||
                                    'Failed to delete admission.');
                                console.error(xhr.responseText);
                            }
                        });
                    }
                });
            });
            // --- END: Bulk Shortlist Actions JS ---
        </script>
    @endbasset
@endpush
