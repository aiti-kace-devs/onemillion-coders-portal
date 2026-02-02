<div class="modal fade" id="changeAdmissionModal" tabindex="-1" role="dialog" aria-labelledby="changeAdmissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeAdmissionModalLabel">Change Admission</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="changeAdmissionForm">
                    <input type="hidden" id="change_admission_user_id" name="user_id">
                    <div class="form-group">
                        <label for="change_admission_course">Course</label>
                        <select class="form-control" id="change_admission_course" name="course_id" required></select>
                    </div>
                    <div class="form-group">
                        <label for="change_admission_session">Session</label>
                        <select class="form-control" id="change_admission_session" name="session_id" required></select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="change-admission-modal-submit" type="button" class="btn btn-primary">Change</button>
            </div>
        </div>
    </div>
</div>
