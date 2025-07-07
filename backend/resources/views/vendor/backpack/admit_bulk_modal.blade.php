<div class="modal fade" id="admitBulkModal" tabindex="-1" role="dialog" aria-labelledby="admitBulkModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="admitBulkModalLabel">Admit Students</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="admitBulkForm">
                    <div class="form-group">
                        <label for="admit_course_id">Course</label>
                        <select class="form-control" id="admit_course_id" name="course_id" required>
                            <option value="">Select Course</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="admit_session_id">Session</label>
                        <select class="form-control" id="admit_session_id" name="session_id" required>
                            <option value="">Select Session</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="admitBulkSubmit">Admit</button>
            </div>
        </div>
    </div>
</div>

@push('after_scripts')
    <script>
        (function() {
            // Wait for DOM to be ready and jQuery to be available
            function initModal() {
                // Check if we're on the right page (User CRUD)
                if (typeof crud === 'undefined' || !crud.table) {
                    return;
                }

                if (typeof $ === 'undefined') {
                    // If jQuery is not available, try again in a moment
                    setTimeout(initModal, 100);
                    return;
                }

                // Store selected IDs globally for the modal
                let selectedStudentIds = [];

                // Listen for Backpack bulk admit action
                $(document).off('click.admitBulk').on('click.admitBulk', '[data-bulk-action="admit"]', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    try {
                        // Get selected IDs from Backpack's bulk actions
                        if (typeof crud !== 'undefined' && crud.table) {
                            selectedStudentIds = crud.table.rows({
                                selected: true
                            }).data().pluck('id').toArray();
                        } else {
                            // Fallback: get selected checkboxes
                            selectedStudentIds = $('input[name="bulk_action_row"]:checked').map(function() {
                                return $(this).val();
                            }).get();
                        }

                        if (selectedStudentIds.length === 0) {
                            if (typeof Noty !== 'undefined') {
                                new Noty({
                                    type: 'warning',
                                    text: 'No students selected.'
                                }).show();
                            } else {
                                alert('No students selected.');
                            }
                            return;
                        }
                        // Populate courses and sessions
                        populateCourses();
                        $('#admitBulkModal').modal('show');
                    } catch (error) {
                        console.error('Error in bulk admit click handler:', error);
                    }
                });

                function populateCourses() {
                    $.get('/admin/course/ajax-list', function(data) {
                        const courseSelect = $('#admit_course_id');
                        if (courseSelect.length) {
                            courseSelect.empty().append('<option value="">Select Course</option>');
                            data.forEach(function(course) {
                                courseSelect.append('<option value="' + course.id + '">' + course
                                    .course_name + '</option>');
                            });
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('Failed to load courses:', error);
                    });
                }

                $('#admit_course_id').off('change.admitBulk').on('change.admitBulk', function() {
                    const courseId = $(this).val();
                    const sessionSelect = $('#admit_session_id');
                    if (sessionSelect.length) {
                        sessionSelect.empty().append('<option value="">Select Session</option>');
                        if (!courseId) return;
                        $.get('/admin/course-session/ajax-list?course_id=' + courseId, function(data) {
                            data.forEach(function(session) {
                                sessionSelect.append('<option value="' + session.id + '">' +
                                    session.name + '</option>');
                            });
                        }).fail(function(xhr, status, error) {
                            console.error('Failed to load sessions:', error);
                        });
                    }
                });

                $('#admitBulkSubmit').off('click.admitBulk').on('click.admitBulk', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    try {
                        const courseId = $('#admit_course_id').val();
                        const sessionId = $('#admit_session_id').val();
                        if (!courseId || !sessionId) {
                            if (typeof Noty !== 'undefined') {
                                new Noty({
                                    type: 'warning',
                                    text: 'Please select both course and session.'
                                }).show();
                            } else {
                                alert('Please select both course and session.');
                            }
                            return;
                        }
                        $.ajax({
                            url: '/admin/user/bulk-admit',
                            method: 'POST',
                            data: {
                                student_ids: selectedStudentIds,
                                course_id: courseId,
                                session_id: sessionId,
                                _token: window.csrf_token || $('meta[name="csrf-token"]').attr(
                                    'content')
                            },
                            success: function(response) {
                                if (typeof Noty !== 'undefined') {
                                    new Noty({
                                        type: 'success',
                                        text: response.message ||
                                            'Students admitted successfully!'
                                    }).show();
                                } else {
                                    alert(response.message || 'Students admitted successfully!');
                                }
                                $('#admitBulkModal').modal('hide');
                                if (typeof crud !== 'undefined' && crud.table) {
                                    crud.table.ajax.reload();
                                } else {
                                    location.reload();
                                }
                            },
                            error: function(xhr) {
                                const message = xhr.responseJSON?.message ||
                                    'Failed to admit students.';
                                if (typeof Noty !== 'undefined') {
                                    new Noty({
                                        type: 'error',
                                        text: message
                                    }).show();
                                } else {
                                    alert(message);
                                }
                            }
                        });
                    } catch (error) {
                        console.error('Error in bulk admit submit:', error);
                    }
                });
            }

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initModal);
            } else {
                // Small delay to ensure Backpack is fully loaded
                setTimeout(initModal, 500);
            }
        })();
    </script>
@endpush
