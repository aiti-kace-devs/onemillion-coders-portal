<div class="modal fade" id="bulkEmailModal" tabindex="-1" aria-labelledby="bulkEmailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="bulkEmailForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="bulkEmailModalLabel">Send Bulk Email</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="text" name="subject" class="form-control mb-2" placeholder="Subject" required>
          <textarea name="message" class="form-control mb-2" placeholder="Message"></textarea>
          <input type="hidden" name="student_ids" id="bulkEmailStudentIds">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Send</button>
        </div>
      </div>
    </form>
  </div>
</div>
