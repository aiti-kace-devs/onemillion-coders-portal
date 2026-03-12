@if ($entry->verification_date)
    <span class="badge badge-success p-2" style="font-size: 12px; font-weight:bolder">Verified</span>

    <form method="POST" action="{{ route('student-verification.reset', $entry->id) }}" style="display:inline;">
        @csrf
        @method('POST')
        <button type="submit" class="badge badge-warning p-2" style="font-size: 12px; background-color: gray" onclick="return confirm('Are you sure you want to reset?')">
            <i class="la la-redo"></i> Reset
        </button>
    </form>
@else
    <span class="badge badge-danger p-2" style="font-size: 12px;">Ask student to update details</span>
@endif
