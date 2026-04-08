{{-- Modal for adding/updating sessions for a centre --}}
<div class="modal fade" id="addCentreSessionModal" tabindex="-1" role="dialog" aria-labelledby="addCentreSessionModalLabel">
    <div class="modal-dialog modal-xl" role="document" style="margin-top: 50px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCentreSessionModalLabel">Add Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div id="addCentreSessionForm" data-fetch-url="" data-save-url="">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="centre_session_modal_centre_id" value="">
                    <p id="centre_session_modal_centre_name" class="text-muted mb-3"></p>

                    <div id="centreSessionRowsContainer"></div>

                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addCentreSessionRowBtn">
                        <i class="la la-plus"></i> Add Session
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCentreSessionsSubmitBtn">Save Sessions</button>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="centreSessionRowTemplate">
    <div class="card mb-3 centre-session-row">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="centre-session-row-title">Session</strong>
                <button type="button" class="btn btn-link btn-sm text-danger centre-remove-session-row">
                    <i class="la la-trash"></i> Remove
                </button>
            </div>

            <input type="hidden" class="centre-session-id" value="">

            <div class="row">
                <div class="form-group col-md-4">
                    <label>Choose Session *</label>
                    <select class="form-control centre-session-name">
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
                    <input type="number" class="form-control centre-session-limit" min="1" placeholder="eg. 50">
                </div>

                <div class="form-group col-md-3">
                    <label>Duration *</label>
                    <input type="text" class="form-control centre-session-time" placeholder="eg. 8am - 1pm">
                </div>

                <div class="form-group col-md-3">
                    <label>Status</label>
                    <select class="form-control centre-session-status">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="form-group col-md-12">
                    <label>Link</label>
                    <input
                        type="text"
                        class="form-control centre-session-link"
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

    function hideModal(modalElement) {
        if (!modalElement) return;

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
            return;
        }

        const jq = window.jQuery;
        if (jq && jq.fn && jq.fn.modal) {
            jq(modalElement).modal('hide');
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

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function parseSessionRowsPayload(rawValue) {
        if (!rawValue || typeof rawValue !== 'string') {
            return [];
        }

        try {
            const parsed = JSON.parse(rawValue);
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    }

    function renderSessionSummary(summaryEl, rows) {
        if (!summaryEl) return;

        if (!Array.isArray(rows) || rows.length === 0) {
            summaryEl.innerHTML =
                '<div class="text-muted small">' + escapeHtml(summaryEl.dataset.emptyText || 'No centre sessions added yet.') + '</div>';
            return;
        }

        summaryEl.innerHTML = rows.map((row) => {
            const sessionLabel = escapeHtml(row.session || 'Session');
            const timeLabel = row.course_time ? ' <span class="text-muted">(' + escapeHtml(row.course_time) + ')</span>' : '';
            const limitLabel = escapeHtml(row.limit || '-');
            const statusActive = row.status === undefined ? true : toBoolean(row.status);
            const statusClass = statusActive ? 'bg-success' : 'bg-secondary';
            const statusLabel = statusActive ? 'Active' : 'Inactive';

            return `
                <div class="border rounded px-3 py-2 mb-2 bg-light">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div><strong>${sessionLabel}</strong>${timeLabel}</div>
                        <span class="badge ${statusClass}">${statusLabel}</span>
                    </div>
                    <div class="text-muted small mt-1">Limit: ${limitLabel}</div>
                </div>
            `;
        }).join('');
    }

    function syncSessionSummaryFromInput(inputId, summaryId) {
        if (!inputId || !summaryId) return;

        const inputEl = document.getElementById(inputId);
        const summaryEl = document.getElementById(summaryId);
        const rows = parseSessionRowsPayload(inputEl ? inputEl.value : '');

        renderSessionSummary(summaryEl, rows);
    }

    function setSessionRowsPayload(inputId, summaryId, rows) {
        if (!inputId) return;

        const inputEl = document.getElementById(inputId);
        if (!inputEl) return;

        inputEl.value = JSON.stringify(Array.isArray(rows) ? rows : []);
        if (summaryId) {
            syncSessionSummaryFromInput(inputId, summaryId);
        }
    }

    function fetchExistingSessionRows(fetchUrl) {
        if (!fetchUrl || !window.fetch) {
            return Promise.resolve([]);
        }

        return fetch(fetchUrl, {
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
                return payload && Array.isArray(payload.sessions) ? payload.sessions : [];
            });
    }

    function createSessionRow(rowData = {}) {
        const template = document.getElementById('centreSessionRowTemplate');
        if (!template) return null;

        const fragment = template.content.cloneNode(true);
        const rowEl = fragment.querySelector('.centre-session-row');
        if (!rowEl) return null;

        const idInput = rowEl.querySelector('.centre-session-id');
        const nameInput = rowEl.querySelector('.centre-session-name');
        const limitInput = rowEl.querySelector('.centre-session-limit');
        const timeInput = rowEl.querySelector('.centre-session-time');
        const linkInput = rowEl.querySelector('.centre-session-link');
        const statusInput = rowEl.querySelector('.centre-session-status');
        const removeBtn = rowEl.querySelector('.centre-remove-session-row');

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
                const container = document.getElementById('centreSessionRowsContainer');
                rowEl.remove();
                renumberSessionRows();
                if (container && container.querySelectorAll('.centre-session-row').length === 0) {
                    addSessionRow();
                }
            });
        }

        return rowEl;
    }

    function renumberSessionRows() {
        const rows = document.querySelectorAll('#centreSessionRowsContainer .centre-session-row');
        Array.from(rows).forEach((row, index) => {
            const title = row.querySelector('.centre-session-row-title');
            if (title) {
                title.textContent = 'Session ' + (index + 1);
            }
        });
    }

    function addSessionRow(rowData = {}) {
        const container = document.getElementById('centreSessionRowsContainer');
        if (!container) return;

        const row = createSessionRow(rowData);
        if (!row) return;

        container.appendChild(row);
        renumberSessionRows();
    }

    function renderSessionRows(rows) {
        const container = document.getElementById('centreSessionRowsContainer');
        if (!container) return;

        container.innerHTML = '';

        if (!Array.isArray(rows) || rows.length === 0) {
            addSessionRow();
            return;
        }

        rows.forEach((row) => addSessionRow(row));
    }

    function getSessionRowsData() {
        const rows = document.querySelectorAll('#centreSessionRowsContainer .centre-session-row');
        const data = [];

        Array.from(rows).forEach((row) => {
            const id = (row.querySelector('.centre-session-id')?.value || '').trim();
            const session = (row.querySelector('.centre-session-name')?.value || '').trim();
            const limit = (row.querySelector('.centre-session-limit')?.value || '').trim();
            const courseTime = (row.querySelector('.centre-session-time')?.value || '').trim();
            const link = (row.querySelector('.centre-session-link')?.value || '').trim();
            const status = (row.querySelector('.centre-session-status')?.value || '1').trim();

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

        const seenSignatures = new Set();

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

            const signature =
                String(row.session || '').trim().toLowerCase() +
                '|' +
                String(row.course_time || '').trim().replace(/\s+/g, ' ').toLowerCase();

            if (seenSignatures.has(signature)) {
                return 'Session row ' + rowNo + ': the same session type and duration already exists above.';
            }

            seenSignatures.add(signature);
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

    function submitCentreSessions(modalElement) {
        const actionHolder = document.getElementById('addCentreSessionForm');
        const mode = actionHolder && actionHolder.dataset ? (actionHolder.dataset.mode || 'ajax') : 'ajax';
        const actionUrl = actionHolder && actionHolder.dataset ? actionHolder.dataset.saveUrl : '';
        if (!actionUrl) {
            if (mode !== 'form') {
                alert('Unable to submit: missing save URL.');
                return;
            }
        }

        const rows = getSessionRowsData();
        const validationError = validateSessionRows(rows);
        if (validationError) {
            alert(validationError);
            return;
        }

        if (mode === 'form') {
            const inputId = actionHolder && actionHolder.dataset ? actionHolder.dataset.inputId || '' : '';
            const summaryId = actionHolder && actionHolder.dataset ? actionHolder.dataset.summaryId || '' : '';
            const inputEl = inputId ? document.getElementById(inputId) : null;

            if (!inputEl) {
                alert('Unable to store sessions in this form right now.');
                return;
            }

            setSessionRowsPayload(inputId, summaryId, rows);

            hideModal(modalElement);
            return;
        }

        const csrfToken = getCsrfToken(modalElement);
        if (!csrfToken) {
            alert('Unable to submit: missing CSRF token.');
            return;
        }

        const submitBtn = document.getElementById('saveCentreSessionsSubmitBtn');
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

        const actionHolder = document.getElementById('addCentreSessionForm');
        const titleEl = document.getElementById('addCentreSessionModalLabel');
        const centreNameEl = document.getElementById('centre_session_modal_centre_name');
        const centreIdEl = document.getElementById('centre_session_modal_centre_id');
        const submitBtn = document.getElementById('saveCentreSessionsSubmitBtn');
        const mode = triggerEl.dataset.sessionMode || 'ajax';

        const centreId = triggerEl.dataset.centreId || '';
        let centreName = triggerEl.dataset.centreName || 'Centre';
        const fetchUrl = triggerEl.dataset.fetchUrl || '';
        const saveUrl = triggerEl.dataset.saveUrl || '';
        const inputId = triggerEl.dataset.inputId || '';
        const summaryId = triggerEl.dataset.summaryId || '';

        if (mode === 'form') {
            const titleInput = document.querySelector('input[name="title"]');
            const currentTitle = titleInput ? (titleInput.value || '').trim() : '';
            if (currentTitle) {
                centreName = currentTitle;
            }
        }

        if (actionHolder && actionHolder.dataset) {
            actionHolder.dataset.mode = mode;
            actionHolder.dataset.fetchUrl = fetchUrl;
            actionHolder.dataset.saveUrl = saveUrl;
            actionHolder.dataset.inputId = inputId;
            actionHolder.dataset.summaryId = summaryId;
        }

        if (centreIdEl) centreIdEl.value = centreId;
        if (titleEl) titleEl.textContent = mode === 'form' ? 'Manage Centre Sessions' : 'Add Session';
        if (centreNameEl) centreNameEl.textContent = 'Centre: ' + centreName;

        const container = document.getElementById('centreSessionRowsContainer');
        if (container) {
            container.innerHTML = '<p class="text-muted mb-0">Loading sessions...</p>';
        }

        if (submitBtn) {
            submitBtn.disabled = mode !== 'form';
            submitBtn.textContent = mode === 'form' ? 'Apply Sessions' : 'Loading...';
        }

        showModal(modalElement);

        if (mode === 'form') {
            const inputEl = inputId ? document.getElementById(inputId) : null;
            const rows = parseSessionRowsPayload(inputEl ? inputEl.value : '');
            if (rows.length > 0 || !fetchUrl) {
                renderSessionRows(rows);

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Apply Sessions';
                }

                return;
            }

            fetchExistingSessionRows(fetchUrl)
                .then((fetchedRows) => {
                    setSessionRowsPayload(inputId, summaryId, fetchedRows);
                    renderSessionRows(fetchedRows);
                })
                .catch(() => {
                    renderSessionRows([]);
                    alert('Unable to load existing sessions. You can still add new ones.');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Apply Sessions';
                    }
                });

            return;
        }

        if (!fetchUrl || !window.fetch) {
            renderSessionRows([]);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Sessions';
            }
            return;
        }

        fetchExistingSessionRows(fetchUrl)
            .then((rows) => {
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
        const modalElement = document.getElementById('addCentreSessionModal');
        if (!modalElement) return;

        if (document.body && modalElement.parentElement !== document.body) {
            document.body.appendChild(modalElement);
        }

        const addRowBtn = document.getElementById('addCentreSessionRowBtn');
        if (addRowBtn) {
            addRowBtn.addEventListener('click', () => addSessionRow());
        }

        const submitBtn = document.getElementById('saveCentreSessionsSubmitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function (e) {
                e.preventDefault();
                submitCentreSessions(modalElement);
            });
        }

        const summaryEls = document.querySelectorAll('[data-centre-session-summary]');
        Array.from(summaryEls).forEach((summaryEl) => {
            const inputId = summaryEl.dataset.inputId || '';
            const fetchUrl = summaryEl.dataset.fetchUrl || '';
            if (!inputId || !summaryEl.id) return;

            const inputEl = document.getElementById(inputId);
            const rows = parseSessionRowsPayload(inputEl ? inputEl.value : '');
            if (rows.length > 0 || !fetchUrl) {
                syncSessionSummaryFromInput(inputId, summaryEl.id);
                return;
            }

            fetchExistingSessionRows(fetchUrl)
                .then((fetchedRows) => {
                    setSessionRowsPayload(inputId, summaryEl.id, fetchedRows);
                })
                .catch(() => {
                    syncSessionSummaryFromInput(inputId, summaryEl.id);
                });
        });

        window.openAddCentreSessionModal = function (triggerEl) {
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
