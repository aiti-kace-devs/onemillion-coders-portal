<div class="mb-4 col-md-4">
    <label for="course_id" class="form-label">Select Course</label>

    <select id="course_id" name="course_id" class="form-control">
        <option value="">Choose One</option>
        @foreach ($groupedCourses as $courseName => $courseGroup)
            <optgroup label="{{ $courseName }}">
                @foreach ($courseGroup as $course)
                    <option value="{{ $course->id }}" @if ($course->id == ($selectedCourse?->id ?? 0)) selected @endif>
                        {{ $course->location }} - {{ $course->course_name }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</div>
@if ($sessions ?? false)
    <div class="mb-4 col-md-4">
        <label for="session_id" class="form-label">Select Session</label>
        <br>
        <select name="session_id" multiple id="session_id" class="form-control w-12 multiselect">
            {{-- <option value="">Select Session</option> --}}

            @foreach ($sessions as $session)
                <option value="{{ $session->id }}" @if (in_array($session->id, $selectedSessions ?? [])) selected @endif>
                    {{ $session->session }}</option>
            @endforeach
        </select>
    </div>
@endif

<script @nonce>
    document.addEventListener('DOMContentLoaded', function() {
        const courseSelect = document.getElementById('course_id');
        const sessionSelect = document.getElementById('session_id');
        $(sessionSelect).multiselect();
        // Store all sessions data in a variable
        const allSessions =
            @if ($sessions ?? false)
                @json($sessions?->keyBy('id')?->toArray() ?? []);
            @else
                []
            @endif

        @if ($sessions ?? false)
            courseSelect.addEventListener('change', function() {
                const selectedCourseId = this.value;

                // Clear existing options except the first one
                while (sessionSelect.options.length > 1) {
                    sessionSelect.remove(1);
                }

                // If a course is selected, filter sessions
                if (selectedCourseId) {
                    // Filter sessions that belong to the selected course
                    Object.values(allSessions).forEach(session => {
                        if (session.course_id == selectedCourseId) {
                            const option = new Option(session.session, session.id);
                            sessionSelect.add(option);
                        }
                    });
                }
            });

            // Trigger change event if a course is already selected
            if (courseSelect.value) {
                courseSelect.dispatchEvent(new Event('change'));
            }
            $(sessionSelect).multiselect('rebuild');
            $(sessionSelect).multiselect();
        @endif


    });
</script>
