<div class="dropdown d-inline-block">
    <button class="btn btn-primary dropdown-toggle" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="la la-bolt"></i> Bulk Actions
    </button>
    <ul class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
        <li>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkEmailModal">
                <i class="la la-envelope text-primary"></i> Send Emails
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkSMSModal">
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
{{-- SweetAlert2 fallback --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
                    function getSelectedStudentIds() {
        // Use Backpack's internal checkedItems tracking
        if (typeof crud !== 'undefined' && crud.checkedItems && crud.checkedItems.length > 0) {
            console.log('Using crud.checkedItems:', crud.checkedItems);
            return crud.checkedItems;
        }

        // Fallback: Get from checkbox data attributes
        let checkboxes = $(".crud_bulk_actions_line_checkbox:checked");
        console.log('Found bulk action checkboxes:', checkboxes.length);

        let selectedIds = [];
        checkboxes.each(function() {
            let primaryKeyValue = $(this).data('primary-key-value');
            console.log('Checkbox primary key value:', primaryKeyValue);
            if (primaryKeyValue) {
                selectedIds.push(primaryKeyValue);
            }
        });

        console.log('Selected student IDs from checkboxes:', selectedIds);
        return selectedIds;
    }

    // Bulk Email
    $('#bulkEmailForm').on('submit', function(e) {
        e.preventDefault();
        let ids = getSelectedStudentIds();
        if (ids.length === 0) return alert('Select students first!');
        $('#bulkEmailStudentIds').val(JSON.stringify(ids));
        $.ajax({
            url: '{{ url('admin/user/send-bulk-email') }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            success: function(resp) {
                alert(resp.message);
                var modal = bootstrap.Modal.getInstance(document.getElementById('bulkEmailModal'));
                if (modal) modal.hide();
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Failed to send emails.');
            }
        });
    });

    // Bulk SMS
    $('#bulkSMSForm').on('submit', function(e) {
        e.preventDefault();
        let ids = getSelectedStudentIds();
        if (ids.length === 0) return alert('Select students first!');
        $('#bulkSMSStudentIds').val(JSON.stringify(ids));
        $.ajax({
            url: '{{ url('admin/user/send-bulk-sms') }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            success: function(resp) {
                alert(resp.message);
                var modal = bootstrap.Modal.getInstance(document.getElementById('bulkSMSModal'));
                if (modal) modal.hide();
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Failed to send SMS.');
            }
        });
    });

    // Bulk Shortlist
    $('#bulkShortlistBtn').on('click', function(e) {
        e.preventDefault();

        // Debug: Check all checkboxes on the page
        console.log('All checkboxes on page:', $('input[type="checkbox"]').length);
        console.log('All checkbox names:', $('input[type="checkbox"]').map(function() { return $(this).attr('name'); }).get());
        console.log('All checkbox values:', $('input[type="checkbox"]').map(function() { return $(this).val(); }).get());
        console.log('All checkbox IDs:', $('input[type="checkbox"]').map(function() { return $(this).attr('id'); }).get());
        console.log('All checkbox classes:', $('input[type="checkbox"]').map(function() { return $(this).attr('class'); }).get());

        // Debug: Check table rows for data attributes
        console.log('Table rows with data-entry-id:', $('tr[data-entry-id]').length);
        console.log('Data-entry-id values:', $('tr[data-entry-id]').map(function() { return $(this).attr('data-entry-id'); }).get());
        console.log('All table rows:', $('table tbody tr').length);

        // Debug: Check all attributes on table rows
        console.log('First table row attributes:', $('table tbody tr').first().get(0) ? Object.keys($('table tbody tr').first().get(0).attributes).map(function(key) {
            return $('table tbody tr').first().get(0).attributes[key].name + '=' + $('table tbody tr').first().get(0).attributes[key].value;
        }) : 'No rows found');

        // Debug: Check for any data attributes on rows
        console.log('All data attributes on first row:', $('table tbody tr').first().data());

        let ids = getSelectedStudentIds();
        console.log('Selected student IDs:', ids);
        console.log('Number of selected students:', ids.length);

        if (ids.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No students selected',
                    text: 'Please select at least one student to shortlist.',
                });
            } else {
                alert('Please select at least one student to shortlist.');
            }
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Shortlist Students?',
                text: `You are about to shortlist ${ids.length} students. Continue?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, shortlist them',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performShortlist(ids);
                }
            });
        } else {
            if (confirm(`You are about to shortlist ${ids.length} students. Continue?`)) {
                performShortlist(ids);
            }
        }
    });

    function performShortlist(ids) {
        $.ajax({
            url: '{{ url('admin/user/shortlist-students') }}',
            method: 'POST',
            data: {student_ids: ids, _token: '{{ csrf_token() }}'},
            success: function(resp) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: resp.message || 'Students shortlisted successfully!'
                    }).then(() => window.location.reload());
                } else {
                    alert(resp.message || 'Students shortlisted successfully!');
                    window.location.reload();
                }
            },
            error: function(xhr) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to shortlist students.'
                    });
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to shortlist students.');
                }
            }
        });
    }
</script>
@endpush
