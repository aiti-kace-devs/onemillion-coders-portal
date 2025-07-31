<!-- Choose Shortlist Modal (Backpack AJAX style) -->
<div class="modal fade" id="chooseShortlistModal" tabindex="-1" aria-labelledby="chooseShortlistModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="chooseShortlistModalLabel">Copy and Paste Shortlisted Student Emails</h5>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label for="email_list">Paste Emails/Phonenumbers Here</label>
        <textarea class="form-control mb-3" name="email_list" id="email_list" rows="10" placeholder="Paste emails/numbers, one per line..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button id="shortlist-modal-submit" type="button" class="btn btn-primary">Submit</button>
      </div>
    </div>
  </div>
</div>
