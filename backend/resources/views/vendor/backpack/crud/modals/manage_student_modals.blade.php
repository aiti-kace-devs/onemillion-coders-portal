{{-- Admit / Change Admission Modal --}}
<div class="modal fade" id="admitModal" tabindex="-1" aria-labelledby="admitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="admitModalLabel">Admit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="admit_user_id">
                <input type="hidden" id="admit_change" value="false">
                <div class="mb-3">
                    <label for="admit_course_id" class="form-label">Course</label>
                    <select id="admit_course_id" class="form-select" required>
                        <option value="">Loading courses...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="admit_session_id" class="form-label">Session</label>
                    <select id="admit_session_id" class="form-select" disabled>
                        <option value="">Select course first</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="admit_form_submit">Admit</button>
            </div>
        </div>
    </div>
</div>

{{-- Choose Session Modal --}}
<div class="modal fade" id="chooseSessionModal" tabindex="-1" aria-labelledby="chooseSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chooseSessionModalLabel">Choose Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="choose_session_user_id">
                <div class="mb-3">
                    <label for="choose_session_course_id" class="form-label">Course</label>
                    <select id="choose_session_course_id" class="form-select" required>
                        <option value="">Loading courses...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="choose_session_session_id" class="form-label">Session</label>
                    <select id="choose_session_session_id" class="form-select" disabled required>
                        <option value="">Select course first</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="choose-session-modal-submit">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = "{{ url(config('backpack.base.route_prefix')) }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    // Clean up any stuck modal backdrops
    function cleanupBackdrop() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    // Helper function to open modal properly
    function openModal(modalId) {
        cleanupBackdrop();
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }
    
    // Load courses for admit modal
    function loadAdmitCourses() {
        const courseSelect = document.getElementById('admit_course_id');
        if (!courseSelect) return;
        
        courseSelect.innerHTML = '<option value="">Loading courses...</option>';
        
                fetch(baseUrl + '/manage-student/courses-ajax')
            .then(response => response.json())
            .then(data => {
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                data.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = course.display_name || course.course_name;
                    courseSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading courses:', error);
                courseSelect.innerHTML = '<option value="">Error loading courses</option>';
            });
    }

    // Load sessions for a course
    function loadSessions(courseId, selectId) {
        const sessionSelect = document.getElementById(selectId);
        if (!sessionSelect) return;
        
        sessionSelect.innerHTML = '<option value="">Loading...</option>';
        sessionSelect.disabled = true;
        
        if (!courseId) {
            sessionSelect.innerHTML = '<option value="">Select course first</option>';
            return;
        }
        
        fetch(baseUrl + '/manage-student/sessions-ajax?course_id=' + courseId)
            .then(response => response.json())
            .then(data => {
                sessionSelect.innerHTML = '<option value="">Select Session</option>';
                sessionSelect.disabled = false;
                data.forEach(session => {
                    const option = document.createElement('option');
                    option.value = session.id;
                    option.textContent = session.name;
                    sessionSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading sessions:', error);
                sessionSelect.innerHTML = '<option value="">Error loading sessions</option>';
            });
    }

    // Admit button click handler
    document.querySelectorAll('.admit-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            const isChange = this.getAttribute('data-is-change') === 'true';
            
            document.getElementById('admit_user_id').value = userId;
            document.getElementById('admit_change').value = isChange ? 'true' : 'false';
            document.getElementById('admitModalLabel').textContent = isChange ? 'Change Admission' : 'Admit Student';
            document.getElementById('admit_form_submit').textContent = isChange ? 'Change' : 'Admit';
            
            loadAdmitCourses();
            
            openModal('admitModal');
        });
    });

    // Course change in admit modal
    document.getElementById('admit_course_id')?.addEventListener('change', function() {
        loadSessions(this.value, 'admit_session_id');
    });

    // Admit form submit
    document.getElementById('admit_form_submit')?.addEventListener('click', function() {
        const userId = document.getElementById('admit_user_id').value;
        const courseId = document.getElementById('admit_course_id').value;
        const sessionId = document.getElementById('admit_session_id').value;
        const change = document.getElementById('admit_change').value;
        
        if (!userId || !courseId) {
            alert('Please select a course.');
            return;
        }
        
        fetch(baseUrl + '/manage-student/bulk-admit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                user_id: userId,
                course_id: courseId,
                session_id: sessionId || null,
                change: change
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.message) {
                alert(data.message || 'Operation successful');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });

    // Choose Session button click handler
    document.querySelectorAll('.choose-session-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            const currentCourseId = this.getAttribute('data-current-course');
            
            document.getElementById('choose_session_user_id').value = userId;
            
            // Load courses
            const courseSelect = document.getElementById('choose_session_course_id');
            courseSelect.innerHTML = '<option value="">Loading courses...</option>';
            
            fetch(baseUrl + '/manage-student/courses-ajax')
                .then(response => response.json())
                .then(data => {
                    courseSelect.innerHTML = '<option value="">Select Course</option>';
                    data.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        option.textContent = course.display_name || course.course_name;
                        if (currentCourseId == course.id) {
                            option.selected = true;
                        }
                        courseSelect.appendChild(option);
                    });
                    
                    // If we have a current course, load sessions
                    if (currentCourseId) {
                        loadSessions(currentCourseId, 'choose_session_session_id');
                    }
                })
                .catch(error => {
                    console.error('Error loading courses:', error);
                    courseSelect.innerHTML = '<option value="">Error loading courses</option>';
                });
            
            openModal('chooseSessionModal');
        });
    });

    // Course change in choose session modal
    document.getElementById('choose_session_course_id')?.addEventListener('change', function() {
        loadSessions(this.value, 'choose_session_session_id');
    });

    // Choose session form submit
    document.getElementById('choose-session-modal-submit')?.addEventListener('click', function() {
        const userId = document.getElementById('choose_session_user_id').value;
        const sessionId = document.getElementById('choose_session_session_id').value;
        
        if (!userId || !sessionId) {
            alert('Please select a session.');
            return;
        }
        
        fetch(baseUrl + '/manage-student/' + userId + '/choose-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                session_id: sessionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.message) {
                alert(data.message || 'Session chosen successfully');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });

    // Delete admission button handler
    document.querySelectorAll('.delete-admission-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            
            if (!confirm('Are you sure you want to delete this student\'s admission?')) {
                return;
            }
            
            fetch(baseUrl + '/manage-student/delete-admission/' + userId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || data.message) {
                    alert(data.message || 'Admission deleted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });
});
</script>
