@php
    $sessionsPayload = old('centre_sessions_payload', $initialSessionsPayload ?? '[]');
    $sessionFetchUrl = !empty($centreEntry?->id) ? backpack_url('centre/' . $centreEntry->id . '/sessions') : '';
@endphp

<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-1">Centre Sessions</h5>
                <div class="text-muted small">New sessions are added to all centres, but edits to existing sessions stay on this centre.</div>
            </div>
            <button
                type="button"
                class="btn btn-outline-primary"
                onclick="openAddCentreSessionModal(this)"
                data-session-mode="form"
                data-centre-name="{{ e(old('title', $centreEntry?->title ?? 'New Centre')) }}"
                data-fetch-url="{{ $sessionFetchUrl }}"
                data-input-id="centre_sessions_payload"
                data-summary-id="centre_sessions_summary"
            >
                <i class="la la-clock-o me-1"></i> Manage Sessions
            </button>
        </div>

        <input
            type="hidden"
            name="centre_sessions_payload"
            id="centre_sessions_payload"
            value="{{ e((string) $sessionsPayload) }}"
        >

        <div
            id="centre_sessions_summary"
            data-centre-session-summary
            data-input-id="centre_sessions_payload"
            data-fetch-url="{{ $sessionFetchUrl }}"
            data-empty-text="No centre sessions added yet."
        ></div>
    </div>
</div>

@include('admin.centre.add_session_modal')
