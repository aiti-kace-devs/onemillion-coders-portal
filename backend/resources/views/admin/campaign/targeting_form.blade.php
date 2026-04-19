<div class="card mt-3">
    <div class="card-header">
        <h5 class="card-title">Target Audience</h5>
        <small class="text-muted">Select targeting criteria. Leave empty to send to all members.</small>
    </div>
    <div class="card-body">

        <!-- Target Branches -->
        <div class="form-group">
            <label><strong>Target Branches</strong></label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                @forelse($branches as $branch)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="branch_{{ $branch->id }}" 
                               name="target_branches[]" value="{{ $branch->id }}"
                               @if($campaign && in_array($branch->id, $campaign->target_branches ?? [])) checked @endif>
                        <label class="form-check-label" for="branch_{{ $branch->id }}">
                            {{ $branch->title }}
                        </label>
                    </div>
                @empty
                    <p class="text-muted">No branches available</p>
                @endforelse
            </div>
        </div>

        <!-- Target Districts -->
        <div class="form-group mt-3">
            <label><strong>Target Districts</strong></label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                @forelse($districts as $district)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="district_{{ $district->id }}" 
                               name="target_districts[]" value="{{ $district->id }}"
                               @if($campaign && in_array($district->id, $campaign->target_districts ?? [])) checked @endif>
                        <label class="form-check-label" for="district_{{ $district->id }}">
                            {{ $district->title }}
                        </label>
                    </div>
                @empty
                    <p class="text-muted">No districts available</p>
                @endforelse
            </div>
        </div>

        <!-- Target Centres -->
        <div class="form-group mt-3">
            <label><strong>Target Centres</strong></label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                @forelse($centres as $centre)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="centre_{{ $centre->id }}" 
                               name="target_centres[]" value="{{ $centre->id }}"
                               @if($campaign && in_array($centre->id, $campaign->target_centres ?? [])) checked @endif>
                        <label class="form-check-label" for="centre_{{ $centre->id }}">
                            {{ $centre->title }} @if($centre->branch)({{ $centre->branch->title }})@endif
                        </label>
                    </div>
                @empty
                    <p class="text-muted">No centres available</p>
                @endforelse
            </div>
        </div>

        <!-- Target Programme Batches -->
        <div class="form-group mt-3">
            <label><strong>Target Programme Batches</strong></label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                @forelse($programmeBatches as $batch)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="batch_{{ $batch->id }}" 
                               name="target_programme_batches[]" value="{{ $batch->id }}"
                               @if($campaign && in_array($batch->id, $campaign->target_programme_batches ?? [])) checked @endif>
                        <label class="form-check-label" for="batch_{{ $batch->id }}">
                            {{ $batch->programme->name ?? 'N/A' }} - Batch {{ $batch->batch_number }}
                        </label>
                    </div>
                @empty
                    <p class="text-muted">No programme batches available</p>
                @endforelse
            </div>
        </div>

        <!-- Target Master Sessions -->
        <div class="form-group mt-3">
            <label><strong>Target Master Sessions</strong></label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                @forelse($masterSessions as $session)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="master_session_{{ $session->id }}" 
                               name="target_master_sessions[]" value="{{ $session->id }}"
                               @if($campaign && in_array($session->id, $campaign->target_master_sessions ?? [])) checked @endif>
                        <label class="form-check-label" for="master_session_{{ $session->id }}">
                            {{ $session->name ?? $session->master_name ?? 'Session ' . $session->id }}
                        </label>
                    </div>
                @empty
                    <p class="text-muted">No master sessions available</p>
                @endforelse
            </div>
        </div>

        <!-- Target Course Sessions -->
        <div class="form-group mt-3">
            <label><strong>Target Course Sessions</strong></label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                @forelse($courseSessions as $session)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="course_session_{{ $session->id }}" 
                               name="target_course_sessions[]" value="{{ $session->id }}"
                               @if($campaign && in_array($session->id, $campaign->target_course_sessions ?? [])) checked @endif>
                        <label class="form-check-label" for="course_session_{{ $session->id }}">
                            {{ $session->course->course_name ?? 'N/A' }} - {{ $session->session_date?->format('Y-m-d') ?? 'N/A' }}
                        </label>
                    </div>
                @empty
                    <p class="text-muted">No course sessions available</p>
                @endforelse
            </div>
        </div>

    </div>
</div>


