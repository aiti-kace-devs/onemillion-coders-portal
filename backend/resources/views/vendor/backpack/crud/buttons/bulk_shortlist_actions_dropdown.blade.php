<!-- jQuery (must be loaded before Toastr and custom scripts) -->
{{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
<!-- Toastr CSS and JS -->


<div class="dropdown d-inline-block me-2">
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
{{-- @include('vendor.backpack.crud.buttons.shortlist_row_actions_dropdown') --}}




@push('after_scripts')
    @basset('https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.all.min.js')
    @bassetBlock('custom/js/bulk_shortlist_action.js')
        <script>
            console.log('Debugging DOM elements:');
            console.log('Admit Modal exists:', $('#admitModal').length);
            console.log('Bulk Email Modal exists:', $('#bulkEmailModal').length);
            console.log('Bulk SMS Modal exists:', $('#bulkSMSModal').length);

            function getCheckedStudentIds() {
                if (typeof crud !== 'undefined' && crud.checkedItems && crud.checkedItems.length > 0) {
                    return crud.checkedItems;
                }
                // Fallback: Get from checkbox data attributes
                let checkboxes = $(".crud_bulk_checkbox:checked");
                let selectedIds = [];
                checkboxes.each(function() {
                    let primaryKeyValue = $(this).data('primary-key-value');
                    if (primaryKeyValue) {
                        selectedIds.push(primaryKeyValue);
                    }
                });
                return selectedIds;
            }

            function shouldApplyToAll() {
                // If no checkboxes are checked, apply to all students in current view
                let checkedIds = getCheckedStudentIds();
                return checkedIds.length === 0;
            }
            // --- BEGIN: Bulk Shortlist Actions JS ---
            $(document).on('click', '.admit-btn', function() {
                const user_id = $(this).data('id');
                const course_id = $(this).data('course_id');
                const session_id = $(this).data('session_id');

                // Call the modal function
                openAdmitModal(user_id, course_id, session_id);

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

            window.openAdmitModal = function(user_id, course_id = null, session_id = null, callback = null) {
                // console.log('Opening admit modal with:', { id, course_id, session_id });
                try {
                    var $modal = $('#admitModal');
                    if ($modal.length) {
                        $modal.appendTo('body');
                        $('#admitModal #user_id').val(user_id);
                        $('#admitModal #course_id').val(course_id);
                        $('#admitModal #session_id').val(session_id);
                    }
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

                            // Submit form via AJAX
                            const formData = new FormData(this);

                            $.ajax({
                                url: $(this).attr('action'),
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(response) {
                                    if (response.success) {
                                        toastr.success(response.message || 'Students admitted successfully!');
                                        if (typeof crud !== 'undefined' && crud.table) {
                                            crud.table.ajax.reload();
                                        }
                                        manuallySelectedIds = [];
                                    } else {
                                        toastr.error(response.message || 'Failed to admit students.');
                                    }
                                    $('#admitModal').modal('hide');
                                },
                                error: function(xhr) {
                                    toastr.error(xhr.responseJSON?.message || 'Failed to admit students.');
                                    console.error(xhr.responseText);
                                },
                                complete: function() {
                                    // Reset the form submitted flag
                                    this.formSubmitted = false;
                                }.bind(this)
                            });
                        }
                    });

                    $('#admitModal').modal('show');
                    $('#admitModal').one('shown.bs.modal', function() {
                        if (typeof window.initAdmitSelect2 === 'function') window.initAdmitSelect2(this);
                    });
                } catch (e) {
                    console.error('Error opening modal:', e);
                }
            };

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
                        toastr.error(xhr.responseJSON?.message ||
                            'Error updating shortlisted users.');
                    }
                });
            });

            $('#admit-selected').click(function() {
                var selectedIds = getCheckedStudentIds() ?? [];
                var applyToAll = shouldApplyToAll();

                if (!applyToAll && selectedIds.length === 0) {
                    toastr.warning('No students selected or no students match your filters');
                    return;
                }

                var btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                // console.log('Student IDs: ', selectedIds)

                // Get the total count from the current datatable view when applying to all
                let totalCount = 0;
                if (applyToAll) {
                    if (typeof crud !== 'undefined' && crud.table) {
                        // Get the total records in the current filtered view
                        totalCount = crud.table.page.info().recordsDisplay;
                    } else if ($('#crudTable').length) {
                        // Fallback to DataTable API
                        totalCount = $('#crudTable').DataTable().page.info().recordsDisplay;
                    }
                }

                Swal.fire({
                    title: 'Admit Students?',
                    text: applyToAll ? `You are about to admit ${totalCount} students. Continue?` :
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
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Open the admit modal to get course and session selection
                        openAdmitModal('', null, null, function() {
                            const arrayInputName = 'user_ids';
                            $(`input[name="${arrayInputName}[]"]`).remove();

                            if (applyToAll) {
                                $('<input>')
                                    .attr('type', 'hidden')
                                    .attr('name', 'select_all_in_query')
                                    .attr('value', 'true')
                                    .appendTo('form[name="admit_form"]');
                            } else {
                                // Create multiple hidden input elements, one for each value in the array
                                selectedIds.forEach(function(id) {
                                    if (id)
                                        $('<input>')
                                        .attr('type', 'hidden')
                                        .attr('name', arrayInputName + '[]')
                                        .attr('value', id)
                                        .appendTo('form[name="admit_form"]');
                                });
                            }

                            $('[name="admit_form"]').submit();
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
                        btn.prop('disabled', false).html('<i class="la la-user-check text-primary"></i> Admit Students');
                    }
                });
            });
            $('#admitModal #course_id').on('change', function() {
                var courseId = $(this).val();
                $('#admitModal #session_id option').each(function() {
                    $(this).toggle($(this).attr('data-course') === courseId || !$(this).attr(
                        'data-course'));
                });
            });

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


            // Bulk Email modal logic
            // $('#bulkEmailForm').on('submit', function(e) {
            //     e.preventDefault();
            //     let ids = getCheckedStudentIds();
            //     let applyToAll = shouldApplyToAll();

            //     let data = $(this).serializeArray();
            //     if (applyToAll) {
            //         data.push({
            //             name: 'select_all_in_query',
            //             value: true
            //         });
            //     } else {
            //         ids.forEach(function(id) {
            //             data.push({
            //                 name: 'student_ids[]',
            //                 value: id
            //             });
            //         });
            //     }

            //     $.ajax({
            //         url: "{{ route('bulk-email.send') }}",
            //         method: 'POST',
            //         data: data,
            //         headers: {
            //             'X-CSRF-TOKEN': '{{ csrf_token() }}'
            //         },
            //         success: function(resp) {
            //             if (typeof Swal !== 'undefined') {
            //                 Swal.fire({
            //                     icon: 'success',
            //                     title: 'Success',
            //                     text: resp.message || 'Emails sent successfully!'
            //                 }).then(() => window.location.reload());
            //             }
            //             if (typeof toastr !== 'undefined') {
            //                 toastr.success(resp.message || 'Emails sent successfully!');
            //             }
            //             var modal = bootstrap.Modal.getInstance(document.getElementById(
            //                 'bulkEmailModal'));
            //             if (modal) modal.hide();
            //         },
            //         error: function(xhr) {
            //             let errorMsg = xhr.responseJSON?.message || 'Failed to send emails.';
            //             if (typeof Swal !== 'undefined') {
            //                 Swal.fire({
            //                     icon: 'error',
            //                     title: 'Error',
            //                     text: errorMsg
            //                 });
            //             }
            //             if (typeof toastr !== 'undefined') {
            //                 toastr.error(errorMsg);
            //             }
            //         }
            //     });
            // });

            // Bulk SMS modal logic

            // Delete admission logic
            $(document).on('click', '.delete-admission-btn', function(e) {
                e.preventDefault();
                const userId = $(this).data('user-id');
                const deleteUrl = "{{ route('user.delete-admission', ['user_id' => 'USER_ID_PLACEHOLDER']) }}"
                    .replace(
                        'USER_ID_PLACEHOLDER', userId);
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
    @endBassetBlock

    @basset('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js')
@endpush

@push('after_styles')
    @basset('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css')
    @basset('https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.min.css')
@endpush
