<!-- jQuery (must be loaded before Toastr and custom scripts) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Toastr CSS and JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<div class="dropdown d-inline-block">
    <button class="btn btn-primary dropdown-toggle" type="button" id="shortlistActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="la la-list"></i> Shortlist Actions
    </button>
    <ul class="dropdown-menu" aria-labelledby="shortlistActionsDropdown">
        <li>
            <a class="dropdown-item" href="#" id="chooseShortlistBtn">
                <i class="la la-list-alt text-info"></i> Choose Shortlist
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" id="sendBulkEmailBtn">
                <i class="la la-envelope text-success"></i> Send Emails
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" id="sendBulkSMSBtn">
                <i class="la la-sms text-warning"></i> Send SMS
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" id="admitShortlistedBtn">
                <i class="la la-user-check text-primary"></i> Admit Students
            </a>
        </li>
    </ul>
</div>
@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Fallback for backpack JS object
if (typeof backpack === 'undefined') {
    window.backpack = {
        baseUrl: '/admin', // Change if your admin prefix is different
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    };
}
$(function() {
    // Open Choose Shortlist modal via AJAX
    $('#chooseShortlistBtn').on('click', function(e) {
        e.preventDefault();
        $.get(backpack.baseUrl + '/user/choose-shortlist-modal', function(html) {
            $('#chooseShortlistModal').remove();
            $('body').append(html);
            $('#chooseShortlistModal').modal('show');
        });
    });
    // Submit Choose Shortlist
    $(document).on('click', '#shortlist-modal-submit', function() {
        var raw = $('#email_list').val();
        var list = raw.split(/\r?\n/).map(x => x.trim()).filter(x => x);
        if (list.length === 0) {
            Swal.fire('Error', 'Please paste at least one valid email or phone number.', 'error');
            return;
        }
        // Determine if emails or phones
        var sendingEmails = list[0].includes('@');
        var sendingPhones = list[0].match(/^\+?\d/);
        var dataToSend = {};
        if (sendingEmails) dataToSend.emails = list;
        else if (sendingPhones) dataToSend.phone_numbers = list;
        else {
            Swal.fire('Error', 'Please paste at least one valid email or phone number.', 'error');
            return;
        }
        $.ajax({
            url: backpack.baseUrl + '/user/shortlist-students',
            method: 'POST',
            data: dataToSend,
            headers: {'X-CSRF-TOKEN': backpack.csrfToken},
            success: function(resp) {
                Swal.fire('Success', resp.message || 'Users updated successfully.', 'success');
                $('#chooseShortlistModal').modal('hide');
                setTimeout(() => window.location.reload(), 1200);
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Error updating shortlisted users.', 'error');
            }
        });
    });
    // Open Bulk Email modal
    $('#sendBulkEmailBtn').on('click', function(e) {
        e.preventDefault();
        $('#bulkEmailModal').appendTo('body').modal('show');
    });
    // Submit Bulk Email
    $(document).on('click', '#bulk-email-modal-submit', function() {
        var form = $('#bulkEmailForm');
        var data = form.serialize();
        $.ajax({
            url: backpack.baseUrl + '/user/send-bulk-email',
            method: 'POST',
            data: data,
            headers: {'X-CSRF-TOKEN': backpack.csrfToken},
            success: function(resp) {
                Swal.fire('Success', resp.message || 'Emails sent successfully.', 'success');
                $('#bulkEmailModal').modal('hide');
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to send emails.', 'error');
            }
        });
    });
    // Open Bulk SMS modal
    $('#sendBulkSMSBtn').on('click', function(e) {
        e.preventDefault();
        $('#bulkSMSModal').appendTo('body').modal('show');
    });
    // Submit Bulk SMS
    $(document).on('click', '#bulk-sms-modal-submit', function() {
        var form = $('#bulkSMSForm');
        var data = form.serialize();
        $.ajax({
            url: backpack.baseUrl + '/user/send-bulk-sms',
            method: 'POST',
            data: data,
            headers: {'X-CSRF-TOKEN': backpack.csrfToken},
            success: function(resp) {
                Swal.fire('Success', resp.message || 'SMS sent successfully.', 'success');
                $('#bulkSMSModal').modal('hide');
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to send SMS.', 'error');
            }
        });
    });
    // Admit Students logic (dashboard style)
    $('#admitShortlistedBtn').off('click').on('click', function() {
        var selectedIds = $('.crud_bulk_actions_line_checkbox:checked').map(function() { return $(this).data('primary-key-value'); }).get();
        var admitAll = selectedIds.length === 0;
        var courseId = $('#admitModal #course_id, #course_id').val();
        var sessionId = $('#admitModal #session_id, #session_id').val();
        var btn = $(this);
        var showModal = function(admitCount) {
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            Swal.fire({
                title: 'Admit Students?',
                text: `You are about to admit ${admitCount} students. Continue?`,
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
                        url: backpack.baseUrl + '/user/admit-shortlisted',
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': backpack.csrfToken
                        },
                        data: admitAll
                            ? { admit_all: true, course_id: courseId, session_id: sessionId }
                            : { student_ids: selectedIds, course_id: courseId, session_id: sessionId },
                        success: function(response) {
                            toastr.success(response.message || 'Students admitted successfully!');
                            window.location.reload();
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'Failed to admit students.');
                            console.error(xhr.responseText);
                        },
                        complete: function() {
                            btn.prop('disabled', false).html('Admit Students');
                        }
                    });
                } else if (result.isDenied) {
                    if (typeof openAdmitModal === 'function') {
                        openAdmitModal('', null, null, function() {
                            const arrayInputName = 'user_ids';
                            $(`input[name="${arrayInputName}[]"]`).remove();
                            selectedIds.forEach(function(id) {
                                if (id)
                                    $('<input>')
                                    .attr('type', 'hidden')
                                    .attr('name', arrayInputName + '[]')
                                    .attr('value', id)
                                    .appendTo('form[name="admit_form"]');
                            });
                            $('[name="admit_form"]').submit();
                        });
                    } else {
                        toastr.error('Change admission modal function not found.');
                        btn.prop('disabled', false).html('Admit Students');
                    }
                } else {
                    btn.prop('disabled', false).html('Admit Students');
                }
            });
        };
        if (admitAll) {
            // Fetch the count of all shortlisted students via AJAX
            $.ajax({
                url: backpack.baseUrl + '/user/shortlisted-count',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': backpack.csrfToken },
                success: function(resp) {
                    var admitCount = resp.count || 0;
                    if (admitCount === 0) {
                        toastr.warning('No students selected or no students match your filters');
                        return;
                    }
                    showModal(admitCount);
                },
                error: function() {
                    toastr.error('Could not fetch shortlisted count.');
                }
            });
        } else {
            if (selectedIds.length === 0) {
                toastr.warning('No students selected or no students match your filters');
                return;
            }
            showModal(selectedIds.length);
        }
    });

    // Filter session options based on selected course (if present)
    $(document).on('change', '#admitModal #course_id, #course_id', function() {
        var courseId = $(this).val();
        $('#admitModal #session_id option, #session_id option').each(function() {
            $(this).toggle($(this).attr('data-course') === courseId || !$(this).attr('data-course'));
        });
    });
});

// Initialize selection arrays for bulk actions
var manuallySelectedIds = [];
var allFilteredIds = [];

// Update manuallySelectedIds on checkbox change
$(document).on('change', '.student-checkbox', function() {
    var studentId = $(this).val();
    if ($(this).is(':checked')) {
        if (!manuallySelectedIds.includes(studentId)) {
            manuallySelectedIds.push(studentId);
        }
    } else {
        manuallySelectedIds = manuallySelectedIds.filter(id => id != studentId);
    }
    var allChecked = $('.student-checkbox:not(:checked)').length === 0;
    $('#select-all').prop('checked', allChecked);
});

// Select all handler
$(document).on('change', '#select-all', function() {
    var isChecked = $(this).prop('checked');
    $('.student-checkbox').prop('checked', isChecked);
    if (isChecked) {
        manuallySelectedIds = $('.student-checkbox').map(function() { return $(this).val(); }).get();
    } else {
        manuallySelectedIds = [];
    }
});
</script>
@endpush
