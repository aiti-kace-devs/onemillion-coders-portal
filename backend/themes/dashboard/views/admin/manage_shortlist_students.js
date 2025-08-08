// Extracted from manage_shortlist_students.blade.php
// All code from <script> blocks and @push('scripts')

$(document).on('click', '.admit-btn', function() {
    const user_id = $(this).data('id');
    const course_id = $(this).data('course_id');
    const session_id = $(this).data('session_id');

    // Call the modal function
    openAdmitModal(user_id, course_id, session_id);

});

window.openAdmitModal = function(user_id, course_id = null, session_id = null, callback = null) {
    try {
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

        $('[name="admit_form"]').off('submit').on('submit', function(
            e) {
            if (!this.formSubmitted) {
                e.preventDefault();
                e.stopImmediatePropagation();
                this.formSubmitted = true;
                if (callback) {
                    callback();
                }

            }
        });

        $('#admitModal').modal('show');
    } catch (e) {
        console.error('Error opening modal:', e);
    }
};

$(document).ready(function() {

    $('select[multiple][data-filter]').multiSelect({
        selectableHeader: "<div class='multi-select-legend'>Available Options</div>",
        selectionHeader: "<div class='multi-select-legend'>Selected Options</div>",
        afterSelect: function(values) {
            updateDataTable();
        },
        afterDeselect: function(values) {
            updateDataTable();
        }
    });


    var allFilteredIds = [];
    var manuallySelectedIds = [];
    var isFilterApplied = false;
    var debounceTimer;

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

    $('#studentSearch').on('keyup', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            table.ajax.reload();
        }, 500);
    });

    $('select[multiple][data-filter]').on('change', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            table.ajax.reload();
        }, 300);
    });

    $('#clear-filters').click(function() {
        $('select[multiple][data-filter]').each(function() {
            $(this).val(['0']);
            $(this).multiSelect('deselect_all');
            $(this).multiSelect('select', '0');
        });
        $('#studentSearch').val('');
        table.ajax.reload();
        $('#select-all').prop('checked', false);
        manuallySelectedIds = [];
    });

    $(document).on('change', '.student-checkbox', function() {
        var studentId = $(this).val();
        if ($(this).is(':checked')) {
            if (!manuallySelectedIds.includes(studentId)) {
                manuallySelectedIds.push(studentId);
            }
        } else {
            manuallySelectedIds = manuallySelectedIds.filter(userId => userId != studentId);
        }
        var allChecked = $('.student-checkbox:not(:checked)').length === 0;
        $('#select-all').prop('checked', allChecked);
    });

    $('#select-all').change(function() {
        var isChecked = $(this).prop('checked');
        $('.student-checkbox').prop('checked', isChecked);
        if (isChecked) {
            manuallySelectedIds = [...allFilteredIds];
        } else {
            manuallySelectedIds = [];
        }
    });

    $('#admit-selected').click(function() {
        var selectedIds = manuallySelectedIds.length > 0 ? manuallySelectedIds : allFilteredIds;
        if (!selectedIds || selectedIds.length === 0) {
            toastr.warning('No students selected or no students match your filters');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        Swal.fire({
            title: 'Admit Students?',
            text: `You are about to admit ${selectedIds.length} students. Continue?`,
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
                $.ajax({
                    url: "{{ route('admin.admit_student') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        student_ids: selectedIds
                    },
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
                    const arrayInputName = 'user_ids';
                    $(`input[name="${arrayInputName}[]"]`).remove();

                    selectedIds.forEach(function(id) {
                        if (id)
                            $('<input>')
                            .attr('type', 'hidden')
                            .attr('name', arrayInputName +
                                '[]')
                            .attr('value', id)
                            .appendTo(
                                'form[name="admit_form"]'
                            );
                    });

                    $('[name="admit_form"]').submit();
                });
            } else {
                btn.prop('disabled', false).html('Admit Students');
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

    var modal = $('#bulk-email-modal');
    $(modal).on('modalAction', function(event) {

        const message = event.detail.message;
        const subject = event.detail.subject;
        const template = event.detail.template;
        if (!subject || (!message && !template)) {
            toastr.error('You need a message/template and a subject');
            return;
        }
        var selectedIds = manuallySelectedIds.length > 0 ? manuallySelectedIds : allFilteredIds;
        $.ajax({
            url: "{{ route('admin.send_bulk_email') }}",
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                student_ids: selectedIds,
                subject,
                message,
                template
            },
            success: function(response) {
                toastr.success(response.message ||
                    'Emails transfer initiated successfully!');
                $(modal).modal('hide');
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to send emails.');
                console.error(xhr.responseText);
            }
        });
    });

    function updateDataTable() {
        $('#studentsTable').DataTable().ajax.reload();
    }

    $(document).ready(function() {
        const modal = $('#bulk-sms-modal');
        const templateSelect = $('#sms_template');
        const messageBox = $('#sms_message');

        modal.on('show.bs.modal', function() {

            templateSelect.empty().append(
                '<option selected disabled>Loading templates...</option>');

            $.get("{{ route('admin.fetch.sms.template') }}", function(templates) {
                templateSelect.empty().append(
                    '<option value="" disabled selected>Select a template</option>'
                );

                $.each(templates, function(index, template) {
                    const option = $('<option></option>')
                        .val(template.id)
                        .text(template.name)
                        .data('content', template
                            .content);
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

        $(document).on('click', '#modal-submit', function() {
            const message = messageBox.val();
            const template = templateSelect.val();

            const modalActionEvent = new CustomEvent('modalAction', {
                detail: {
                    message,
                    template,
                    modalId: 'bulk-sms-modal',
                },
                bubbles: true,
                cancelable: true,
            });

            document.getElementById('bulk-sms-modal').dispatchEvent(modalActionEvent);
        });

        modal.on('modalAction', function(event) {
            const {
                message,
                subject,
                template
            } = event.detail;

            if ((!message && !template)) {
                toastr.error('You need a message/template and a subject');
                return;
            }

            var selectedIds = manuallySelectedIds.length > 0 ? manuallySelectedIds :
                allFilteredIds;
            if (!selectedIds || selectedIds.length === 0) {
                toastr.warning('No students selected or no students match your filters');
                return;
            }
            console.log('Student IDs: ', selectedIds)

            $.ajax({
                url: "{{ route('admin.send_bulk_sms') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                data: {
                    student_ids: selectedIds,
                    message,
                },
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

    $(document).on('click', '.delete-admission', function(e) {
        e.preventDefault();
        const userId = $(this).data('userid');
        const deleteUrl = "{{ url('admin/delete-student-admission') }}/" + userId;

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
                        table.ajax.reload();
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

});

$(document).on('click', '#shortlist-modal-submit', function() {
    const rawEmails = $('#email_list').val();
    const emailList = rawEmails
        .split(/\r?\n/)
        .map(email => email.trim())
        .filter(email => email !== '');

    if (emailList.length === 0) {
        toastr.error('Please paste at least one valid email address/ phonenumber.');
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
        url: "{{ route('admin.save_shortlisted_students') }}",
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        data: dataToSend,
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
