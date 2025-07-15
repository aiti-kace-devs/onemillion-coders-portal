<div class="d-flex flex-column align-items-start" style="min-width: 160px;">
    <a class="dropdown-item change-admission-btn" href="javascript:void(0)" role="button" tabindex="0" data-user-id="{{ $entry->getKey() }}">
        <i class="la la-user-edit text-primary"></i> Change Admission
    </a>
    <a class="dropdown-item choose-session-btn" href="javascript:void(0)" role="button" tabindex="0" data-user-id="{{ $entry->getKey() }}">
        <i class="la la-calendar text-success"></i> Choose Session
    </a>
    <a class="dropdown-item delete-admission-btn" href="javascript:void(0)" role="button" tabindex="0" data-user-id="{{ $entry->getKey() }}">
        <i class="la la-trash text-danger"></i> Delete Admission
    </a>
</div>
@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function() {
    // Change Admission
    $(document).on('click', '.change-admission-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        $('#change_admission_user_id').val(userId);
        // Populate courses
        $.get(backpack.baseUrl + '/user/courses', function(courses) {
            var $course = $('#change_admission_course_id');
            $course.empty().append('<option value="">Select Course</option>');
            $.each(courses, function(id, name) {
                $course.append('<option value="'+id+'">'+name+'</option>');
            });
        });
        // Populate sessions
        $.get(backpack.baseUrl + '/user/sessions', function(sessions) {
            var $session = $('#change_admission_session_id');
            $session.empty().append('<option value="">Select Session</option>');
            $.each(sessions, function(id, name) {
                $session.append('<option value="'+id+'">'+name+'</option>');
            });
        });
        $('#changeAdmissionModal').appendTo('body').modal('show');
    });
    // Submit Change Admission
    $(document).on('click', '#change-admission-modal-submit', function() {
        var userId = $('#change_admission_user_id').val();
        var form = $('#changeAdmissionForm');
        var data = form.serialize();
        $.ajax({
            url: backpack.baseUrl + '/user/' + userId + '/change-admission',
            method: 'POST',
            data: data,
            headers: {'X-CSRF-TOKEN': backpack.csrfToken},
            success: function(resp) {
                Swal.fire('Success', resp.message || 'Admission updated successfully.', 'success');
                $('#changeAdmissionModal').modal('hide');
                setTimeout(() => window.location.reload(), 1200);
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to update admission.', 'error');
            }
        });
    });
    // Choose Session
    $(document).on('click', '.choose-session-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        $('#choose_session_user_id').val(userId);
        // Populate sessions
        $.get(backpack.baseUrl + '/user/sessions', function(sessions) {
            var $session = $('#choose_session_session_id');
            $session.empty().append('<option value="">Select Session</option>');
            $.each(sessions, function(id, name) {
                $session.append('<option value="'+id+'">'+name+'</option>');
            });
        });
        $('#chooseSessionModal').appendTo('body').modal('show');
    });
    // Submit Choose Session
    $(document).on('click', '#choose-session-modal-submit', function() {
        var userId = $('#choose_session_user_id').val();
        var form = $('#chooseSessionForm');
        var data = form.serialize();
        $.ajax({
            url: backpack.baseUrl + '/user/' + userId + '/choose-session',
            method: 'POST',
            data: data,
            headers: {'X-CSRF-TOKEN': backpack.csrfToken},
            success: function(resp) {
                Swal.fire('Success', resp.message || 'Session chosen successfully.', 'success');
                $('#chooseSessionModal').modal('hide');
                setTimeout(() => window.location.reload(), 1200);
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to choose session.', 'error');
            }
        });
    });
    // Delete Admission
    $(document).on('click', '.delete-admission-btn', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to delete this admission?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: backpack.baseUrl + '/user/' + userId + '/delete-admission',
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': backpack.csrfToken},
                    success: function(resp) {
                        Swal.fire('Success', resp.message || 'Admission deleted successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1200);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete admission.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
