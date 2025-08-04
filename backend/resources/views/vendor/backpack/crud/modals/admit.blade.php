<div class="modal fade" id="admitModal" tabindex="-1" aria-labelledby="admitModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="admitModalLabel">Admit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('user.admit-student') }}" name="admit_form" method="POST">
                    {{ csrf_field() }}
                    <input id="user_id" name="user_id" type="hidden" class="form-control" required>
                    <input id="change" name="change" value="false" type="hidden" class="form-control" required>
                    <div class="form-group mb-3">
                        <label for="course_id" class="form-label">Select Course</label>
                        <select id="course_id" name="course_id" class="form-select" required>
                            <option value="">Choose One Course</option>
                            @foreach ($courses as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="session_id" class="form-label">Choose Session</label>
                        <select id="session_id" name="session_id" class="form-select" @if (empty($sessions)) disabled @endif>
                            @if (empty($sessions))
                                <option value="">No sessions available</option>
                            @else
                                <option value="">Choose One Session</option>
                                @foreach ($sessions as $session)
                                    <option data-course="{{ $session->course_id }}" value="{{ $session->id }}">
                                        {{ $session->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        @if (empty($sessions))
                            <small class="text-muted">Sessions are not configured. Please contact support.</small>
                        @endif
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary" @if (empty($sessions)) disabled @endif>Admit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
