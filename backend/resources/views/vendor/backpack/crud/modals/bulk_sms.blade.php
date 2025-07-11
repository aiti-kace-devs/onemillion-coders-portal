<div class="modal fade" id="bulkSMSModal" tabindex="-1" aria-labelledby="bulkSMSModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="bulkSMSForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="bulkSMSModalLabel">Send Bulk SMS</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <textarea name="message" class="form-control mb-2" placeholder="Message" required></textarea>
          <input type="hidden" name="student_ids" id="bulkSMSStudentIds">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Send</button>
        </div>
      </div>
    </form>
  </div>
</div>
