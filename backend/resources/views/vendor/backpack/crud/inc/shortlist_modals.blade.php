<!-- Change Admission Modal -->
<div class="modal fade" id="changeAdmissionModal" tabindex="-1" aria-labelledby="changeAdmissionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="changeAdmissionForm">
      @csrf
      <input type="hidden" id="change_admission_user_id" name="user_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="changeAdmissionModalLabel">Change Admission</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="change_admission_course_id">Course</label>
            <select class="form-control" id="change_admission_course_id" name="course_id" required>
              <option value="">Select Course</option>
              <option value="1">Course 1</option>
              <option value="2">Course 2</option>
              <!-- Replace with @foreach ($courses as $id => $name) if available -->
            </select>
          </div>
          <div class="form-group">
            <label for="change_admission_session_id">Session</label>
            <select class="form-control" id="change_admission_session_id" name="session_id" required>
              <option value="">Select Session</option>
              <option value="1">Session 1</option>
              <option value="2">Session 2</option>
              <!-- Replace with @foreach ($sessions as $id => $name) if available -->
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="change-admission-modal-submit">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Choose Session Modal -->
<div class="modal fade" id="chooseSessionModal" tabindex="-1" aria-labelledby="chooseSessionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="chooseSessionForm">
      @csrf
      <input type="hidden" id="choose_session_user_id" name="user_id">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="chooseSessionModalLabel">Choose Session</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="choose_session_session_id">Session</label>
            <select class="form-control" id="choose_session_session_id" name="session_id" required>
              <option value="">Select Session</option>
              <option value="1">Session 1</option>
              <option value="2">Session 2</option>
              <!-- Replace with @foreach ($sessions as $id => $name) if available -->
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="choose-session-modal-submit">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('after_scripts')
<script>
// Utility: Get all checked student checkboxes in the Backpack table
function getSelectedStudentIds() {
    return $("input[type='checkbox'].student-checkbox:checked")
        .map(function() { return $(this).val(); })
        .get();
}

// AJAX load and show Choose Shortlist modal
$(document).on('click', '#choose-shortlist-btn', function(e) {
    e.preventDefault();
    $.get(backpack.baseUrl + '/user/choose-shortlist-modal', function(html) {
        // Remove any existing modal
        $('#chooseShortlistModal').remove();
        // Append new modal to body
        $('body').append(html);
        // Set selected IDs for shortlist
        window.selectedShortlistIds = getSelectedStudentIds();
        // Show the modal
        $('#chooseShortlistModal').modal('show');
    });
});

// When opening the Admit Students modal, set the selected IDs
$(document).on('show.bs.modal', '#changeAdmissionModal', function() {
    window.selectedAdmitIds = getSelectedStudentIds();
});

// CHOOSE SHORTLIST (Bulk Shortlist)
$(document).on('click', '#shortlist-modal-submit', function() {
    let selectedIds = window.selectedShortlistIds || [];
    let emailsOrPhones = $('#chooseShortlistModal #email_list').val();
    let dataToSend = {};
    if (emailsOrPhones && emailsOrPhones.trim().length > 0) {
        // Parse emails/phones from textarea
        const lines = emailsOrPhones.split(/\r?\n/).map(l => l.trim()).filter(l => l);
        if (lines.length > 0) {
            if (lines[0].includes('@')) {
                dataToSend.emails = lines;
            } else if (lines[0].match(/^\+?\d+$/)) {
                dataToSend.phone_numbers = lines;
            }
        }
    } else if (selectedIds.length > 0) {
        dataToSend.student_ids = selectedIds;
    } else {
        Swal.fire('Error', 'No students selected or emails/phones provided.', 'error');
        return;
    }
    $.ajax({
        url: backpack.baseUrl + '/user/shortlist-students',
        method: 'POST',
        data: dataToSend,
        headers: {'X-CSRF-TOKEN': backpack.csrfToken},
        success: function(resp) {
            Swal.fire('Success', resp.message || 'Students shortlisted successfully.', 'success');
            $('#chooseShortlistModal').modal('hide');
            setTimeout(() => window.location.reload(), 1200);
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to shortlist students.', 'error');
        }
    });
});

// ADMIT STUDENTS (Bulk Admit)
$(document).on('click', '#admit-selected', function() {
    let selectedIds = window.selectedAdmitIds || [];
    let courseId = $('#change_admission_course_id').val();
    let sessionId = $('#change_admission_session_id').val();
    if (!selectedIds.length || !courseId || !sessionId) {
        Swal.fire('Error', 'Please select students, course, and session.', 'error');
        return;
    }
    $.ajax({
        url: backpack.baseUrl + '/user/bulk-admit',
        method: 'POST',
        data: {
            student_ids: selectedIds,
            course_id: courseId,
            session_id: sessionId
        },
        headers: {'X-CSRF-TOKEN': backpack.csrfToken},
        success: function(resp) {
            Swal.fire('Success', resp.message || 'Students admitted successfully.', 'success');
            setTimeout(() => window.location.reload(), 1200);
        },
        error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Failed to admit students.', 'error');
        }
    });
});
</script>
@endpush
