<div class="modal fade" id="bulkAdmitModal" tabindex="-1" role="dialog" aria-labelledby="bulkAdmitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkAdmitModalLabel">Admit Shortlisted Students</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="bulkAdmitForm">
                    <div class="form-group">
                        <label for="bulk_admit_course">Course</label>
                        <select class="form-control" id="bulk_admit_course" name="course_id" required></select>
                    </div>
                    <div class="form-group">
                        <label for="bulk_admit_session">Session</label>
                        <select class="form-control" id="bulk_admit_session" name="session_id" required></select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="bulk-admit-modal-submit" type="button" class="btn btn-primary">Admit</button>
            </div>
        </div>
    </div>
</div>
