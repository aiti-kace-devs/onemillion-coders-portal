@if($entry->user)
    @php
        $isBlocked = (bool) $entry->user->is_verification_blocked;
        $blockReason = (string) ($entry->user->verification_block_reason ?? '');
    @endphp

    @if($isBlocked && $blockReason === \App\Services\GhanaCardService::BLOCK_REASON_ATTEMPTS_EXCEEDED)
        <a href="javascript:void(0)" onclick="addVerificationAttempts({{ $entry->user_id }})" class="btn btn-sm btn-link text-primary" data-button-type="add-attempts">
            <i class="la la-plus-circle"></i> Add Attempts
        </a>
    @endif

    @if($isBlocked)
        <a href="javascript:void(0)" onclick="resetVerificationBlock(this, {{ $entry->user_id }})" class="btn btn-sm btn-link text-warning" data-button-type="reset">
            <i class="la la-unlock"></i> Reset Block
        </a>
    @endif

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
                            new Noty({
                                type: "success",
                                text: result && result.message ? result.message : "User has been unblocked and attempts reset (history preserved)."
                            }).show();

                            location.reload();
                        },
                        error: function(result) {
                            new Noty({
                                type: "error",
                                text: "Could not unblock user."
                            }).show();
                        }
                    });
                }
            }
        }

        if (typeof addVerificationAttempts !== 'function') {
            function addVerificationAttempts(userId) {
                var raw = prompt("Enter number of attempts to add (1-20):", "1");
                if (raw === null) return;

                var attempts = parseInt(raw, 10);
                if (!Number.isInteger(attempts) || attempts < 1 || attempts > 20) {
                    new Noty({
                        type: "error",
                        text: "Please enter a valid number from 1 to 20."
                    }).show();
                    return;
                }

                var url = "{{ url(config('backpack.base.route_prefix') . '/ghana-card-verification/add-attempts') }}/" + userId;
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        attempts: attempts
                    },
                    success: function(result) {
                        new Noty({
                            type: "success",
                            text: result && result.message ? result.message : "Additional attempts added."
                        }).show();
                        location.reload();
                    },
                    error: function(xhr) {
                        var message = "Could not add attempts.";
                        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        new Noty({
                            type: "error",
                            text: message
                        }).show();
                    }
                });
            }
        }
    </script>
@endif
