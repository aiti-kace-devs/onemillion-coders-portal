{{-- Modal for adding/updating sessions for a course --}}
<div class="modal fade" id="addSessionModal" tabindex="-1" role="dialog" aria-labelledby="addSessionModalLabel">
    <div class="modal-dialog modal-xl" role="document" style="margin-top: 50px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSessionModalLabel">Add Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div id="addSessionForm" data-fetch-url="" data-save-url="">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="session_modal_course_id" value="">
                    <p id="session_modal_course_name" class="text-muted mb-3"></p>

                    <div id="sessionRowsContainer"></div>

                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addSessionRowBtn">
                        <i class="la la-plus"></i> Add Session
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveSessionsSubmitBtn">Save Sessions</button>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="sessionRowTemplate">
    <div class="card mb-3 batch-session-row">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="batch-session-row-title">Session</strong>
                <button type="button" class="btn btn-link btn-sm text-danger batch-remove-session-row">
                    <i class="la la-trash"></i> Remove
                </button>
            </div>

            <input type="hidden" class="batch-session-id" value="">

            <div class="row">
                <div class="form-group col-md-4">
                    <label>Choose Session *</label>
                    <select class="form-control batch-session-name">
                        <option value="">Select Session</option>
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                        <option value="Evening">Evening</option>
                        <option value="Fullday">Fullday</option>
                        <option value="Online">Online</option>
                    </select>
                </div>

                <div class="form-group col-md-2">
                    <label>Limit *</label>
                    <input type="number" class="form-control batch-session-limit" min="1" placeholder="eg. 50">
                </div>

                <div class="form-group col-md-3">
                    <label>Duration *</label>
                    <input type="text" class="form-control batch-session-time" placeholder="eg. 8am - 1pm">
                </div>

                <div class="form-group col-md-3">
                    <label>Status</label>
                    <select class="form-control batch-session-status">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="form-group col-md-12">
                    <label>Link</label>
                    <input
                        type="text"
                        class="form-control batch-session-link"
                        placeholder="eg. https://chat.whatsapp.com/BekTu3PWEqc8UtydifN8Mt"
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script>
(() => {
    'use strict';

    function showModal(modalElement) {
        if (!modalElement) return;

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return;
        }

        const jq = window.jQuery;
        if (jq && jq.fn && jq.fn.modal) {
            jq(modalElement).modal('show');
        }
    }

    function getCsrfToken(modalElement) {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const fromMeta = csrfMeta ? csrfMeta.getAttribute('content') : null;
        if (fromMeta) return fromMeta;
        if (!modalElement) return '';
        const tokenInput = modalElement.querySelector('input[name="_token"]');
        return tokenInput ? tokenInput.value : '';
    }

    function toBoolean(value) {
        if (value === true || value === false) return value;
        if (value === 1 || value === '1') return true;
        if (value === 0 || value === '0') return false;
        if (typeof value === 'string') {
            return value.toLowerCase() === 'true';
        }
        return false;
    }

    function createSessionRow(rowData = {}) {
        const template = document.getElementById('sessionRowTemplate');
        if (!template) return null;

        const fragment = template.content.cloneNode(true);
        const rowEl = fragment.querySelector('.batch-session-row');
        if (!rowEl) return null;

        const idInput = rowEl.querySelector('.batch-session-id');
        const nameInput = rowEl.querySelector('.batch-session-name');
        const limitInput = rowEl.querySelector('.batch-session-limit');
        const timeInput = rowEl.querySelector('.batch-session-time');
        const linkInput = rowEl.querySelector('.batch-session-link');
        const statusInput = rowEl.querySelector('.batch-session-status');
        const removeBtn = rowEl.querySelector('.batch-remove-session-row');

        if (idInput) idInput.value = rowData.id ? String(rowData.id) : '';
        if (nameInput) nameInput.value = rowData.session ? String(rowData.session) : '';
        if (limitInput) limitInput.value = rowData.limit ? String(rowData.limit) : '';
        if (timeInput) timeInput.value = rowData.course_time ? String(rowData.course_time) : '';
        if (linkInput) linkInput.value = rowData.link ? String(rowData.link) : '';
        if (statusInput) {
            const status = rowData.status === undefined ? true : toBoolean(rowData.status);
            statusInput.value = status ? '1' : '0';
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                const container = document.getElementById('sessionRowsContainer');
                rowEl.remove();
                renumberSessionRows();
                if (container && container.querySelectorAll('.batch-session-row').length === 0) {
                    addSessionRow();
                }
            });
        }

        return rowEl;
    }

    function renumberSessionRows() {
        const rows = document.querySelectorAll('#sessionRowsContainer .batch-session-row');
        Array.from(rows).forEach((row, index) => {
            const title = row.querySelector('.batch-session-row-title');
            if (title) {
                title.textContent = 'Session ' + (index + 1);
            }
        });
    }

    function addSessionRow(rowData = {}) {
        const container = document.getElementById('sessionRowsContainer');
        if (!container) return;

        const row = createSessionRow(rowData);
        if (!row) return;

        container.appendChild(row);
        renumberSessionRows();
    }

    function renderSessionRows(rows) {
        const container = document.getElementById('sessionRowsContainer');
        if (!container) return;

        container.innerHTML = '';

        if (!Array.isArray(rows) || rows.length === 0) {
            addSessionRow();
            return;
        }

        rows.forEach((row) => addSessionRow(row));
    }

    function getSessionRowsData() {
        const rows = document.querySelectorAll('#sessionRowsContainer .batch-session-row');
        const data = [];

        Array.from(rows).forEach((row) => {
            const id = (row.querySelector('.batch-session-id')?.value || '').trim();
            const session = (row.querySelector('.batch-session-name')?.value || '').trim();
            const limit = (row.querySelector('.batch-session-limit')?.value || '').trim();
            const courseTime = (row.querySelector('.batch-session-time')?.value || '').trim();
            const link = (row.querySelector('.batch-session-link')?.value || '').trim();
            const status = (row.querySelector('.batch-session-status')?.value || '1').trim();

            if (!session && !limit && !courseTime && !link) {
                return;
            }

            data.push({
                id,
                session,
                limit,
                course_time: courseTime,
                link,
                status,
            });
        });

        return data;
    }

    function validateSessionRows(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            return 'Add at least one session row before saving.';
        }

        for (let i = 0; i < rows.length; i += 1) {
            const row = rows[i];
            const rowNo = i + 1;

            if (!row.session) {
                return 'Session row ' + rowNo + ': choose a session.';
            }

            const limitInt = parseInt(row.limit, 10);
            if (Number.isNaN(limitInt) || limitInt < 1) {
                return 'Session row ' + rowNo + ': limit must be at least 1.';
            }

            if (!row.course_time) {
                return 'Session row ' + rowNo + ': enter duration.';
            }
        }

        return '';
    }

    function appendHiddenInput(form, name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value == null ? '' : String(value);
        form.appendChild(input);
    }

    function submitCourseSessions(modalElement) {
        const actionHolder = document.getElementById('addSessionForm');
        const actionUrl = actionHolder && actionHolder.dataset ? actionHolder.dataset.saveUrl : '';
        if (!actionUrl) {
            alert('Unable to submit: missing save URL.');
            return;
        }

        const rows = getSessionRowsData();
        const validationError = validateSessionRows(rows);
        if (validationError) {
            alert(validationError);
            return;
        }

        const csrfToken = getCsrfToken(modalElement);
        if (!csrfToken) {
            alert('Unable to submit: missing CSRF token.');
            return;
        }

        const submitBtn = document.getElementById('saveSessionsSubmitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }

        const postForm = document.createElement('form');
        postForm.method = 'POST';
        postForm.action = actionUrl;
        postForm.style.display = 'none';

        appendHiddenInput(postForm, '_token', csrfToken);

        rows.forEach((row, index) => {
            if (row.id) appendHiddenInput(postForm, `sessions[${index}][id]`, row.id);
            appendHiddenInput(postForm, `sessions[${index}][session]`, row.session);
            appendHiddenInput(postForm, `sessions[${index}][limit]`, row.limit);
            appendHiddenInput(postForm, `sessions[${index}][course_time]`, row.course_time);
            appendHiddenInput(postForm, `sessions[${index}][link]`, row.link);
            appendHiddenInput(postForm, `sessions[${index}][status]`, row.status || '1');
        });

        document.body.appendChild(postForm);
        postForm.submit();
    }

    function openSessionModal(triggerEl, modalElement) {
        if (!triggerEl || !triggerEl.dataset) return;

        const actionHolder = document.getElementById('addSessionForm');
        const titleEl = document.getElementById('addSessionModalLabel');
        const courseNameEl = document.getElementById('session_modal_course_name');
        const courseIdEl = document.getElementById('session_modal_course_id');
        const submitBtn = document.getElementById('saveSessionsSubmitBtn');

        const courseId = triggerEl.dataset.courseId || '';
        const courseName = triggerEl.dataset.courseName || 'Course';
        const fetchUrl = triggerEl.dataset.fetchUrl || '';
        const saveUrl = triggerEl.dataset.saveUrl || '';

        if (actionHolder && actionHolder.dataset) {
            actionHolder.dataset.fetchUrl = fetchUrl;
            actionHolder.dataset.saveUrl = saveUrl;
        }

        if (courseIdEl) courseIdEl.value = courseId;
        if (titleEl) titleEl.textContent = 'Add Session';
        if (courseNameEl) courseNameEl.textContent = 'Course: ' + courseName;

        const container = document.getElementById('sessionRowsContainer');
        if (container) {
            container.innerHTML = '<p class="text-muted mb-0">Loading sessions...</p>';
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Loading...';
        }

        showModal(modalElement);

        if (!fetchUrl || !window.fetch) {
            renderSessionRows([]);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Sessions';
            }
            return;
        }

        fetch(fetchUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) throw new Error('Unable to load sessions.');
                return response.json();
            })
            .then((payload) => {
                const rows = payload && Array.isArray(payload.sessions) ? payload.sessions : [];
                renderSessionRows(rows);
            })
            .catch(() => {
                renderSessionRows([]);
                alert('Unable to load existing sessions. You can still add new ones.');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save Sessions';
                }
            });
    }

    function init() {
        const modalElement = document.getElementById('addSessionModal');
        if (!modalElement) return;

        if (document.body && modalElement.parentElement !== document.body) {
            document.body.appendChild(modalElement);
        }

        const addRowBtn = document.getElementById('addSessionRowBtn');
        if (addRowBtn) {
            addRowBtn.addEventListener('click', () => addSessionRow());
        }

        const submitBtn = document.getElementById('saveSessionsSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function (e) {
                e.preventDefault();
                submitCourseSessions(modalElement);
            });
        }

        window.openAddSessionModal = function (triggerEl) {
            openSessionModal(triggerEl, modalElement);
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
</script>
