<div class="modal fade" id="chooseSessionModal" tabindex="-1" role="dialog" aria-labelledby="chooseSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chooseSessionModalLabel">Choose Session</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="chooseSessionForm">
                    <input type="hidden" id="choose_session_user_id" name="user_id">
                    <div class="form-group">
                        <label for="choose_session_session">Session</label>
                        <select class="form-control" id="choose_session_session" name="session_id" required></select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="choose-session-modal-submit" type="button" class="btn btn-primary">Choose</button>
            </div>
        </div>
    </div>
</div>
