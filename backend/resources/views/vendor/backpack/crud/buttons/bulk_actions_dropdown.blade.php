<div class="dropdown d-inline-block">
    <button class="btn btn-primary dropdown-toggle" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="la la-bolt"></i> Bulk Actions
    </button>
    <ul class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
        <li>
            <a class="dropdown-item" href="#" id="bulkEmailBtn">
                <i class="la la-envelope text-primary"></i> Send Emails
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" id="bulkSMSBtn">
                <i class="la la-sms text-success"></i> Send SMS
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" id="bulkShortlistBtn">
                <i class="la la-user-check text-warning"></i> Shortlist Students
            </a>
        </li>
    </ul>
</div>
@include('vendor.backpack.crud.modals.bulk_email')
@include('vendor.backpack.crud.modals.bulk_sms')

@push('after_scripts')
    @basset('https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.all.min.js')
    @bassetBlock('custom/js/bulk_shortlist_action.js')
        <script>
            let selectAllAcrossPages = false;

            // Listen for select all checkbox in table header
            $(document).on('change', '.crud_bulk_actions_select_all', function() {
                selectAllAcrossPages = this.checked;
                if (selectAllAcrossPages) {
                    // Optionally, show a message
                    let total = $('#crudTable').DataTable().page.info().recordsDisplay;
                    if ($('#selectAllMessage').length === 0) {
                        $("<div id='selectAllMessage' class='alert alert-info mt-2'>All " + total +
                            " students are selected across all pages.</div>").insertBefore('#crudTable');
                    }
                } else {
                    $('#selectAllMessage').remove();
                }
            });

            function getCheckedStudentIds() {
                // Use Backpack's internal checkedItems tracking
                if (typeof crud !== 'undefined' && crud.checkedItems && crud.checkedItems.length > 0) {
                    return crud.checkedItems;
                }
                // Fallback: Get from checkbox data attributes
                let checkboxes = $(".crud_bulk_actions_line_checkbox:checked");
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

            // Helper to add select_all flag if needed
            function addSelectAllFlag(data) {
                if (selectAllAcrossPages) {
                    data.push({
                        name: 'select_all',
                        value: true
                    });
                }
                return data;
            }

            // Bulk Email
            $('#bulkEmailBtn').on('click', function(e) {
                e.preventDefault();
                $('#bulkEmailModal').appendTo('body').modal('show');
            });
            // $('#bulkEmailForm').on('submit', function(e) {
            //     e.preventDefault();
            //     let ids = getCheckedStudentIds();
            //     let data = $(this).serializeArray();
            //     let applyToAll = shouldApplyToAll();

            //     if (applyToAll) {
            //         data.push({
            //             name: 'select_all_in_query',
            //             value: 1 // send integer 1
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
            //             var modal = bootstrap.Modal.getInstance(document.getElementById('bulkEmailModal'));
            //             if (modal) modal.hide();
            //         },
            //         error: function(xhr) {
            //             let errorMsg = xhr.responseJSON?.message || 'Failed to send emails.';
            //             if (typeof Swal !== 'undefined') {
            //                 Swal.fire({
            //                     icon: 'error',
            //                     title: 'Error',
            //                     text: xhr.responseJSON?.message || 'Failed to send SMS.'
            //                 });
            //             }
            //             if (typeof toastr !== 'undefined') {
            //                 toastr.error(errorMsg);
            //             }
            //         }
            //     });
            // });

            // Bulk SMS
            // $(document).ready(function() {
            //     const modal = $('#bulkSMSModal');
            //     const templateSelect = $('#sms_template');
            //     const messageBox = $('#sms_message');

            //     modal.on('show.bs.modal', function() {
            //         templateSelect.empty().append(
            //             '<option selected disabled>Loading templates...</option>');
            //         $.get("{{ route('sms-template.fetch') }}", function(templates) {
            //             templateSelect.empty().append(
            //                 '<option value="" disabled selected>Select a template</option>'
            //             );
            //             $.each(templates, function(index, template) {
            //                 const option = $('<option></option>')
            //                     .val(template.id)
            //                     .text(template.name)
            //                     .data('content', template.content);
            //                 templateSelect.append(option);
            //             });
            //         }).fail(function() {
            //             toastr.error('Failed to load SMS templates.');
            //             templateSelect.empty().append(
            //                 '<option value="" disabled selected>Unable to load templates</option>'
            //             );
            //         });
            //     });

            //     templateSelect.on('change', function() {
            //         const selectedOption = $(this).find('option:selected');
            //         const content = selectedOption.data('content');
            //         if (content) {
            //             messageBox.val(content);
            //         }
            //     });

            //     $(document).off('click', '#modal-submit').on('click', '#modal-submit', function() {
            //         const message = messageBox.val();
            //         const template = templateSelect.val();
            //         const ids = getCheckedStudentIds();
            //         const applyToAll = shouldApplyToAll();

            //         const modalActionEvent = new CustomEvent('modalAction', {
            //             detail: {
            //                 message,
            //                 template,
            //                 modalId: 'bulkSMSModal',
            //                 ids: ids,
            //                 applyToAll: applyToAll
            //             },
            //             bubbles: true,
            //             cancelable: true,
            //         });
            //         document.getElementById('bulkSMSModal').dispatchEvent(modalActionEvent);
            //     });

            //     modal.off('modalAction').on('modalAction', function(event) {
            //         const {
            //             message,
            //             template,
            //             ids,
            //             applyToAll
            //         } = event.detail;
            //         if ((!message && !template)) {
            //             toastr.error('You need a message/template and a subject');
            //             return;
            //         }

            //         let data = {
            //             _token: '{{ csrf_token() }}',
            //             message: message,
            //         };

            //         if (applyToAll) {
            //             data.select_all_in_query = 1;
            //         } else if (ids.length > 0) {
            //             data.student_ids = ids;
            //         }

            //         $.ajax({
            //             url: "{{ route('bulk-sms.send') }}",
            //             type: 'POST',
            //             headers: {
            //                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            //             },
            //             data: data,
            //             success: function(response) {
            //                 toastr.success(response.message ||
            //                     'SMS transfer initiated successfully!');
            //                 modal.modal('hide');
            //             },
            //             error: function(xhr) {
            //                 toastr.error(xhr.responseJSON?.message ||
            //                     'Failed to send SMS to students.');
            //             }
            //         });
            //     });
            // });

            // Open Bulk Email modal
            $('#bulkEmailBtn').on('click', function(e) {
                e.preventDefault();
                $('#bulkEmailModal').appendTo('body').modal('show');
            });
            // Open Bulk SMS modal
            $('#bulkSMSBtn').on('click', function(e) {
                e.preventDefault();
                $('#bulkSMSModal').appendTo('body').modal('show');
            });
            // Bulk Shortlist
            $('#bulkShortlistBtn').on('click', function(e) {
                e.preventDefault();
                let ids = getCheckedStudentIds();
                let applyToAll = shouldApplyToAll();

                if (applyToAll) {
                    // Get the total count from the current datatable view
                    let totalCount = 0;
                    if (typeof crud !== 'undefined' && crud.table) {
                        // Get the total records in the current filtered view
                        totalCount = crud.table.page.info().recordsDisplay;
                    } else if ($('#crudTable').length) {
                        // Fallback to DataTable API
                        totalCount = $('#crudTable').DataTable().page.info().recordsDisplay;
                    }

                    Swal.fire({
                        title: 'Shortlist Students?',
                        text: `You are about to shortlist ${totalCount} students. This might take a while. Continue?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, shortlist them',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performShortlist(ids, true);
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Shortlist Students?',
                        text: `You are about to shortlist ${ids.length} students. Continue?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, shortlist them',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            performShortlist(ids, false);
                        }
                    });
                }
            });

            function performShortlist(ids, applyToAll = false) {
                let data = {
                    _token: '{{ csrf_token() }}'
                };
                if (applyToAll) {
                    data.select_all_in_query = 1;
                } else if (ids.length > 0) {
                    data.student_ids = ids;
                }

                $.ajax({
                    url: "{{ route('bulk-students.shortlist') }}",
                    method: 'POST',
                    data: data,
                    success: function(resp) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: resp.message || 'Students shortlisted successfully!'
                            }).then(() => window.location.reload());
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.success(resp.message || 'Students shortlisted successfully!');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = xhr.responseJSON?.message || 'Failed to shortlist students.';
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
            }
        </script>
    @endBassetBlock
    @basset('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js')
@endpush

@push('after_styles')
    @basset('https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css')
    @basset('https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.min.css')
@endpush
