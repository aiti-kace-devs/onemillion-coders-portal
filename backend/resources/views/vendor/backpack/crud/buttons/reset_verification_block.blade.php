@if($entry->code === '03' && $entry->user && $entry->user->is_verification_blocked)
    <a href="javascript:void(0)" onclick="resetVerificationBlock(this, {{ $entry->user_id }})" class="btn btn-sm btn-link" data-button-type="reset">
        <i class="la la-unlock"></i> Unblock User
    </a>

    <script>
        if (typeof resetVerificationBlock !== 'function') {
            function resetVerificationBlock(button, userId) {
                if (confirm("Are you sure you want to unblock this user for Ghana Card verification?")) {
                    var url = "{{ url(config('backpack.base.route_prefix') . '/ghana-card-verification/reset-block') }}/" + userId;
                    
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(result) {
                            // Show a success notification
                            new Noty({
                                type: "success",
                                text: "User has been unblocked."
                            }).show();
                            
                            // Reload the page or the datatable
                            location.reload();
                        },
                        error: function(result) {
                            // Show an error notification
                            new Noty({
                                type: "error",
                                text: "Could not unblock user."
                            }).show();
                        }
                    });
                }
            }
        }
    </script>
@endif
