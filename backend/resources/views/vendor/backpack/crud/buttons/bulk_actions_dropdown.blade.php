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
<script>
    function getSelectedStudentIds() {
        return $("input[name='crud_bulk_checkbox[]']:checked").map(function() {
            return $(this).val();
        }).get();
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
        let ids = getSelectedStudentIds();
        if (ids.length === 0) return alert('Select students first!');
        if (!confirm('Shortlist selected students?')) return;
        $.ajax({
            url: '{{ url('admin/user/shortlist-students') }}',
            method: 'POST',
            data: {student_ids: ids, _token: '{{ csrf_token() }}'},
            success: function(resp) {
                alert(resp.message);
                window.location.reload();
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Failed to shortlist students.');
            }
        });
    });
</script>
@endpush
