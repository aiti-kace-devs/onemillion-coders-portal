@extends(backpack_view('blank'))

@php
    $breadcrumbs = [
        trans('backpack::crud.admin') => backpack_url('dashboard'),
        'Student Management' => backpack_url('manage-student'),
        'Protocol List' => false,
    ];
@endphp

@section('header')
    <section class="container-fluid d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
        <div>
            <h2 class="mb-1">Protocol List</h2>
            <small class="text-muted">
                Upload, preview, edit, and save pending participants before they activate their accounts.
            </small>
        </div>
    </section>
@endsection

@section('content')
    <div class="container-fluid protocol-list-page">
        <div id="protocol-alerts" class="mb-3"></div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-xl-4">
                        <label for="protocol-upload-input" class="form-label fw-semibold">Upload spreadsheet</label>
                        <input id="protocol-upload-input" type="file" class="form-control"
                            accept=".csv,.xls,.xlsx" />
                        <div class="form-text">
                            Supported formats: CSV, XLS, XLSX. Expected columns:
                            <code>first_name</code>, <code>middle_name</code>, <code>last_name</code>,
                            <code>previous_name</code>, <code>email</code>, <code>gender</code>,
                            <code>age</code>, <code>mobile_no</code>, <code>ghcard</code>.
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <label for="protocol-search-input" class="form-label fw-semibold">Filter rows</label>
                        <input id="protocol-search-input" type="search" class="form-control"
                            placeholder="Search by name, email, phone, or Ghana Card" />
                    </div>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="d-flex flex-wrap justify-content-xl-end gap-2">
                            <button id="protocol-upload-btn" type="button" class="btn btn-outline-primary">
                                <i class="la la-upload me-1"></i> Upload List
                            </button>
                            <button id="protocol-add-btn" type="button" class="btn btn-success shadow-sm">
                                <i class="la la-user-plus me-1"></i> Add Participant
                            </button>
                            <button id="protocol-preview-btn" type="button" class="btn btn-outline-secondary">
                                <i class="la la-eye me-1"></i> Preview
                            </button>
                            <button id="protocol-save-btn" type="button" class="btn btn-primary">
                                <i class="la la-save me-1"></i> Save All
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-xxl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <h5 class="mb-1">Recent Import Batches</h5>
                            <small class="text-muted">Server-side spreadsheet history with batch status and save outcomes.</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle protocol-history-table mb-0">
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>Status</th>
                                        <th>Rows</th>
                                        <th>Saved</th>
                                        <th>Uploaded</th>
                                    </tr>
                                </thead>
                                <tbody id="protocol-import-history-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <h5 class="mb-1">Recent Activations</h5>
                            <small class="text-muted">Completed protocol activations stay visible here for long-term auditability.</small>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle protocol-history-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Participant</th>
                                        <th>Contact</th>
                                        <th>Batch</th>
                                        <th>Activated</th>
                                    </tr>
                                </thead>
                                <tbody id="protocol-activation-history-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">Pending Participants</h5>
                        <div id="protocol-summary" class="text-muted small">
                            Loading rows...
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                        <button id="protocol-table-add-btn" type="button" class="btn btn-success btn-sm shadow-sm">
                            <i class="la la-user-plus me-1"></i> Add Participant
                        </button>
                        <div id="protocol-mode-badge" class="protocol-mode-badge badge rounded-pill bg-warning-subtle text-dark">
                            Editing enabled
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle protocol-table mb-0">
                        <thead>
                            <tr>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Last Name</th>
                                <th>Previous Name</th>
                                <th>Gender</th>
                                <th>Age</th>
                                <th>Email</th>
                                <th>Mobile No.</th>
                                <th>GH. Card No.</th>
                                <th>Email Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="protocol-table-body"></tbody>
                    </table>
                </div>

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pt-3 border-top mt-3">
                    <div id="protocol-pagination-info" class="text-muted small"></div>
                    <div class="btn-group" role="group" aria-label="Protocol pagination">
                        <button id="protocol-prev-page" type="button" class="btn btn-outline-secondary btn-sm">
                            Previous
                        </button>
                        <button id="protocol-next-page" type="button" class="btn btn-outline-secondary btn-sm">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade protocol-participant-modal" id="protocol-edit-modal" tabindex="-1"
            aria-labelledby="protocolEditModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <span id="protocol-modal-context"
                                class="badge rounded-pill bg-success-subtle text-success-emphasis mb-2">
                                Add participant
                            </span>
                            <h5 class="modal-title" id="protocolEditModalLabel">Participant Details</h5>
                            <small id="protocol-modal-subtitle" class="text-muted d-block">
                                Review the participant details below, then save the row you want to keep.
                            </small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <form id="protocol-edit-form" class="row g-3">
                            <input type="hidden" id="protocol-edit-local-key" />
                            <div class="col-md-6">
                                <label for="protocol-first-name" class="form-label">First Name</label>
                                <input id="protocol-first-name" name="first_name" class="form-control" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="protocol-middle-name" class="form-label">Middle Name</label>
                                <input id="protocol-middle-name" name="middle_name" class="form-control" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="protocol-last-name" class="form-label">Last Name</label>
                                <input id="protocol-last-name" name="last_name" class="form-control" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="protocol-previous-name" class="form-label">Previous Name</label>
                                <input id="protocol-previous-name" name="previous_name" class="form-control" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="protocol-gender" class="form-label">Gender</label>
                                <select id="protocol-gender" name="gender" class="form-select">
                                    <option value="">Select gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="protocol-age" class="form-label">Age</label>
                                <input id="protocol-age" name="age" type="number" min="0" max="120"
                                    class="form-control" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="protocol-email" class="form-label">Email</label>
                                <input id="protocol-email" name="email" type="email" class="form-control" />
                                <div id="protocol-email-meta" class="form-text"></div>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="protocol-mobile-no" class="form-label">Mobile No.</label>
                                <input id="protocol-mobile-no" name="mobile_no" class="form-control" />
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-12">
                                <label for="protocol-ghcard" class="form-label">GH. Card No.</label>
                                <input id="protocol-ghcard" name="ghcard" class="form-control"
                                    placeholder="GHA-123456789-0" />
                                <div id="protocol-ghcard-meta" class="form-text"></div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="protocol-edit-save-btn"
                            form="protocol-edit-form">
                            Save Participant
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="protocol-delete-modal" tabindex="-1" aria-labelledby="protocolDeleteModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="protocolDeleteModalLabel">Delete Participant</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            This row will be removed from the current protocol list. Do you want to continue?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="protocol-confirm-delete-btn">Confirm Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after_styles')
    <style>
        .protocol-list-page .card {
            border-radius: 1rem;
        }

        .protocol-mode-badge {
            font-size: 0.8rem;
            letter-spacing: 0.01em;
        }

        .protocol-table thead th {
            white-space: nowrap;
            font-size: 0.85rem;
            color: #52607a;
        }

        .protocol-table tbody td {
            vertical-align: top;
        }

        .protocol-history-table thead th {
            white-space: nowrap;
            font-size: 0.8rem;
            color: #52607a;
        }

        .protocol-history-table tbody td {
            vertical-align: top;
        }

        .protocol-row-error {
            background: rgba(255, 243, 205, 0.45);
        }

        .protocol-empty-state {
            padding: 2.5rem 1rem;
            text-align: center;
            color: #69758f;
        }

        .protocol-cell-muted {
            color: #8a94a6;
        }

        .protocol-inline-note {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: #b54708;
        }

        .protocol-table-actions {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .protocol-participant-modal .modal-content {
            border-radius: 1.25rem;
            overflow: hidden;
        }

        .protocol-participant-modal .modal-header {
            background: linear-gradient(135deg, #f8fafc 0%, #eef4ff 100%);
        }
    </style>
@endpush

@push('after_scripts')
    <script>
        (function() {
            const initialRows = @json($rows);
            const initialImportBatches = @json($importBatches ?? []);
            const initialActivationHistory = @json($activationHistory ?? []);
            const uploadUrl = @json(backpack_url('protocol-list/upload'));
            const saveUrl = @json(backpack_url('protocol-list/save'));
            const snapshotUrl = @json(backpack_url('protocol-list/snapshot'));
            const deleteBaseUrl = @json(backpack_url('protocol-list'));
            const csrfToken = @json(csrf_token());
            const pageSize = 10;
            const snapshotPollMs = 10000;

            const state = {
                rows: Array.isArray(initialRows) ? initialRows.map(decorateRow) : [],
                importBatches: Array.isArray(initialImportBatches) ? initialImportBatches.map(decorateImportBatch) : [],
                activationHistory: Array.isArray(initialActivationHistory) ? initialActivationHistory.map(decorateActivationHistory) : [],
                page: 1,
                query: '',
                previewMode: false,
                editingKey: null,
                deletingKey: null,
                modalMode: 'edit',
                savingRowKey: null,
                isSaving: false,
                isUploading: false,
            };

            const dom = {
                alerts: document.getElementById('protocol-alerts'),
                uploadInput: document.getElementById('protocol-upload-input'),
                uploadBtn: document.getElementById('protocol-upload-btn'),
                addBtn: document.getElementById('protocol-add-btn'),
                tableAddBtn: document.getElementById('protocol-table-add-btn'),
                previewBtn: document.getElementById('protocol-preview-btn'),
                saveBtn: document.getElementById('protocol-save-btn'),
                searchInput: document.getElementById('protocol-search-input'),
                tableBody: document.getElementById('protocol-table-body'),
                paginationInfo: document.getElementById('protocol-pagination-info'),
                prevPageBtn: document.getElementById('protocol-prev-page'),
                nextPageBtn: document.getElementById('protocol-next-page'),
                summary: document.getElementById('protocol-summary'),
                modeBadge: document.getElementById('protocol-mode-badge'),
                importHistoryBody: document.getElementById('protocol-import-history-body'),
                activationHistoryBody: document.getElementById('protocol-activation-history-body'),
                editForm: document.getElementById('protocol-edit-form'),
                editSaveBtn: document.getElementById('protocol-edit-save-btn'),
                modalContext: document.getElementById('protocol-modal-context'),
                modalSubtitle: document.getElementById('protocol-modal-subtitle'),
                deleteConfirmBtn: document.getElementById('protocol-confirm-delete-btn'),
            };

            const editableRowFields = [
                'first_name',
                'middle_name',
                'last_name',
                'previous_name',
                'gender',
                'age',
                'email',
                'mobile_no',
                'ghcard',
            ];
            const editModalEl = document.getElementById('protocol-edit-modal');
            const deleteModalEl = document.getElementById('protocol-delete-modal');

            moveModalToBody(editModalEl);
            moveModalToBody(deleteModalEl);

            const editModal = bootstrap.Modal.getOrCreateInstance(editModalEl);
            const deleteModal = bootstrap.Modal.getOrCreateInstance(deleteModalEl);
            let snapshotTimer = null;

            function moveModalToBody(modalElement) {
                if (modalElement && modalElement.parentElement !== document.body) {
                    document.body.appendChild(modalElement);
                }
            }

            function cleanupModalArtifacts() {
                if (document.querySelector('.modal.show')) {
                    return;
                }

                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.body.style.removeProperty('overflow');
                document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
            }

            function decorateRow(row) {
                return {
                    local_key: row.local_key || cryptoRandomKey(),
                    id: row.id ?? null,
                    first_name: row.first_name ?? '',
                    middle_name: row.middle_name ?? '',
                    last_name: row.last_name ?? '',
                    previous_name: row.previous_name ?? '',
                    gender: row.gender ?? '',
                    age: row.age ?? '',
                    email: row.email ?? '',
                    mobile_no: row.mobile_no ?? '',
                    ghcard: normalizeGhcard(row.ghcard ?? ''),
                    import_batch_id: row.import_batch_id ?? null,
                    import_batch_committed: row.import_batch_committed ?? Boolean(row.id),
                    email_change_attempts: Number(row.email_change_attempts ?? 0),
                    ghcard_change_attempts: Number(row.ghcard_change_attempts ?? 0),
                    activation_email_sent_at: row.activation_email_sent_at ?? null,
                    invitation_email_status: row.invitation_email_status ?? 'pending',
                    invitation_email_queued_at: row.invitation_email_queued_at ?? null,
                    invitation_email_last_attempt_at: row.invitation_email_last_attempt_at ?? null,
                    invitation_email_failed_at: row.invitation_email_failed_at ?? null,
                    invitation_email_attempts: Number(row.invitation_email_attempts ?? 0),
                    invitation_email_failure_message: row.invitation_email_failure_message ?? null,
                    created_at: row.created_at ?? null,
                    updated_at: row.updated_at ?? null,
                    __errors: row.__errors ?? null,
                    __dirty: Boolean(row.__dirty ?? (!row.id || !row.import_batch_committed)),
                };
            }

            function decorateImportBatch(batch) {
                return {
                    id: batch.id ?? null,
                    batch_uuid: batch.batch_uuid ?? '',
                    reference: batch.reference ?? '',
                    source_filename: batch.source_filename ?? 'Unknown file',
                    status: batch.status ?? 'parsed',
                    total_rows: Number(batch.total_rows ?? 0),
                    saved_rows: Number(batch.saved_rows ?? 0),
                    created_rows: Number(batch.created_rows ?? 0),
                    updated_rows: Number(batch.updated_rows ?? 0),
                    invalid_rows: Number(batch.invalid_rows ?? 0),
                    invitation_emails_sent: Number(batch.invitation_emails_sent ?? 0),
                    uploaded_by_admin_name: batch.uploaded_by_admin_name ?? '',
                    applied_by_admin_name: batch.applied_by_admin_name ?? '',
                    uploaded_at: batch.uploaded_at ?? null,
                    applied_at: batch.applied_at ?? null,
                };
            }

            function decorateActivationHistory(entry) {
                return {
                    id: entry.id ?? null,
                    full_name: entry.full_name ?? 'Unknown participant',
                    email: entry.email ?? '',
                    ghcard: entry.ghcard ?? '',
                    mobile_no: entry.mobile_no ?? '',
                    protocol_import_batch_id: entry.protocol_import_batch_id ?? null,
                    activation_completed_at: entry.activation_completed_at ?? null,
                };
            }

            function cryptoRandomKey() {
                if (window.crypto && window.crypto.randomUUID) {
                    return window.crypto.randomUUID();
                }

                return 'row-' + Math.random().toString(36).slice(2);
            }

            function normalizeGhcard(value) {
                const digits = String(value || '').replace(/\D/g, '').slice(0, 10);
                if (digits.length === 10) {
                    return `GHA-${digits.slice(0, 9)}-${digits.slice(9)}`;
                }

                return String(value || '').trim().toUpperCase();
            }

            function rowSearchText(row) {
                return [
                    row.first_name,
                    row.middle_name,
                    row.last_name,
                    row.previous_name,
                    row.gender,
                    row.age,
                    row.email,
                    row.mobile_no,
                    row.ghcard,
                    row.invitation_email_status,
                    row.invitation_email_failure_message,
                ].join(' ').toLowerCase();
            }

            function filteredRows() {
                const query = state.query.trim().toLowerCase();
                if (!query) {
                    return state.rows;
                }

                return state.rows.filter((row) => rowSearchText(row).includes(query));
            }

            function pagedRows() {
                const rows = filteredRows();
                const start = (state.page - 1) * pageSize;
                return rows.slice(start, start + pageSize);
            }

            function clearAlerts() {
                dom.alerts.innerHTML = '';
            }

            function showAlert(type, message, timeout = 5000) {
                clearAlerts();

                const wrapper = document.createElement('div');
                wrapper.className = `alert alert-${type} alert-dismissible fade show`;
                wrapper.role = 'alert';
                wrapper.innerHTML = `
                    <div>${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                dom.alerts.appendChild(wrapper);

                if (timeout > 0) {
                    window.setTimeout(() => {
                        wrapper.remove();
                    }, timeout);
                }
            }

            function renderSummary() {
                const rows = filteredRows();
                const total = state.rows.length;
                const modeText = state.previewMode ? 'Preview mode is active. Row editing is temporarily read-only.' :
                    'Editing mode is active. Upload, add, edit, delete, and save as needed.';

                dom.summary.textContent = `${rows.length} visible row(s) out of ${total} total. ${modeText}`;
                dom.modeBadge.textContent = state.previewMode ? 'Preview mode' : 'Editing enabled';
                dom.modeBadge.className = `protocol-mode-badge badge rounded-pill ${state.previewMode ? 'bg-info-subtle text-info-emphasis' : 'bg-warning-subtle text-dark'}`;
            }

            function formatDateTime(value) {
                if (!value) {
                    return '—';
                }

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return '—';
                }

                return new Intl.DateTimeFormat(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                }).format(date);
            }

            function importBatchBadgeClass(status) {
                switch (status) {
                    case 'applied':
                        return 'bg-success-subtle text-success-emphasis';
                    case 'review_needed':
                        return 'bg-warning-subtle text-warning-emphasis';
                    default:
                        return 'bg-info-subtle text-info-emphasis';
                }
            }

            function importBatchLabel(status) {
                switch (status) {
                    case 'applied':
                        return 'Applied';
                    case 'review_needed':
                        return 'Needs Review';
                    default:
                        return 'Parsed';
                }
            }

            function renderImportHistory() {
                if (!state.importBatches.length) {
                    dom.importHistoryBody.innerHTML = `
                        <tr>
                            <td colspan="5" class="protocol-empty-state">
                                No spreadsheet batches have been recorded yet.
                            </td>
                        </tr>
                    `;
                    return;
                }

                dom.importHistoryBody.innerHTML = state.importBatches.map((batch) => `
                    <tr>
                        <td>
                            <div class="fw-semibold">${escapeHtml(batch.source_filename)}</div>
                            <div class="small text-muted">Ref ${escapeHtml(batch.reference || String(batch.id || '—'))}</div>
                        </td>
                        <td>
                            <span class="badge rounded-pill ${importBatchBadgeClass(batch.status)}">
                                ${escapeHtml(importBatchLabel(batch.status))}
                            </span>
                            ${batch.uploaded_by_admin_name ? `<div class="small text-muted mt-1">By ${escapeHtml(batch.uploaded_by_admin_name)}</div>` : ''}
                        </td>
                        <td>
                            <div class="fw-semibold">${batch.total_rows}</div>
                            <div class="small text-muted">${batch.invalid_rows} flagged</div>
                        </td>
                        <td>
                            <div class="fw-semibold">${batch.saved_rows}</div>
                            <div class="small text-muted">${batch.created_rows} new, ${batch.updated_rows} updated</div>
                            <div class="small text-muted">${batch.invitation_emails_sent} invite job(s) queued</div>
                        </td>
                        <td>
                            <div>${escapeHtml(formatDateTime(batch.uploaded_at))}</div>
                            <div class="small text-muted">${batch.applied_at ? `Applied ${escapeHtml(formatDateTime(batch.applied_at))}` : 'Awaiting final save'}</div>
                        </td>
                    </tr>
                `).join('');
            }

            function renderActivationHistory() {
                if (!state.activationHistory.length) {
                    dom.activationHistoryBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="protocol-empty-state">
                                Activated protocol participants will appear here after they complete account setup.
                            </td>
                        </tr>
                    `;
                    return;
                }

                dom.activationHistoryBody.innerHTML = state.activationHistory.map((entry) => `
                    <tr>
                        <td>
                            <div class="fw-semibold">${escapeHtml(entry.full_name)}</div>
                            <div class="small text-muted">${escapeHtml(entry.ghcard || '—')}</div>
                        </td>
                        <td>
                            <div>${escapeHtml(entry.email || '—')}</div>
                            <div class="small text-muted">${escapeHtml(entry.mobile_no || '—')}</div>
                        </td>
                        <td>
                            ${entry.protocol_import_batch_id ? `<span class="badge rounded-pill bg-light text-dark">Batch #${escapeHtml(String(entry.protocol_import_batch_id))}</span>` : '<span class="text-muted small">Manual / prior save</span>'}
                        </td>
                        <td>${escapeHtml(formatDateTime(entry.activation_completed_at))}</td>
                    </tr>
                `).join('');
            }

            function renderTable() {
                const rows = pagedRows();

                if (!rows.length) {
                    dom.tableBody.innerHTML = `
                        <tr>
                            <td colspan="11" class="protocol-empty-state">
                                No participants match the current view. Upload a sheet or add a participant to begin.
                            </td>
                        </tr>
                    `;
                    return;
                }

                function invitationStatusLabel(status) {
                    switch (status) {
                        case 'queued':
                            return 'Queued';
                        case 'sending':
                            return 'Sending';
                        case 'retrying':
                            return 'Retrying';
                        case 'sent':
                            return 'Sent';
                        case 'failed':
                            return 'Failed';
                        default:
                            return 'Pending';
                    }
                }

                function invitationStatusBadgeClass(status) {
                    switch (status) {
                        case 'queued':
                            return 'bg-info-subtle text-info-emphasis';
                        case 'sending':
                            return 'bg-primary-subtle text-primary-emphasis';
                        case 'retrying':
                            return 'bg-warning-subtle text-warning-emphasis';
                        case 'sent':
                            return 'bg-success-subtle text-success-emphasis';
                        case 'failed':
                            return 'bg-danger-subtle text-danger-emphasis';
                        default:
                            return 'bg-secondary-subtle text-secondary-emphasis';
                    }
                }

                dom.tableBody.innerHTML = rows.map((row) => {
                    const hasErrors = row.__errors && row.__errors.length;
                    const errorMarkup = hasErrors ?
                        `<span class="protocol-inline-note">${row.__errors[0]}</span>` :
                        '';
                    const hasLocalChanges = Boolean(row.__dirty);
                    const showSaveButton = Boolean(row.id || row.__dirty);
                    const saveButtonLabel = state.savingRowKey === row.local_key && state.isSaving ? 'Saving...' : 'Save';
                    const saveButtonClass = hasLocalChanges ? 'btn btn-success' : 'btn btn-outline-success';
                    const actionDisabled = state.previewMode || state.isSaving ? 'disabled' : '';
                    const statusLabel = invitationStatusLabel(row.invitation_email_status);
                    const statusBadgeClass = invitationStatusBadgeClass(row.invitation_email_status);
                    const statusTime = row.invitation_email_status === 'sent' ?
                        `Sent ${escapeHtml(formatDateTime(row.activation_email_sent_at))}` :
                        (row.invitation_email_status === 'failed' && row.invitation_email_failed_at ?
                            `Failed ${escapeHtml(formatDateTime(row.invitation_email_failed_at))}` :
                            (row.invitation_email_queued_at ? `Queued ${escapeHtml(formatDateTime(row.invitation_email_queued_at))}` : 'Not queued yet'));
                    const attemptSummary = row.invitation_email_attempts > 0 ?
                        `Attempt${row.invitation_email_attempts === 1 ? '' : 's'}: ${row.invitation_email_attempts}` :
                        'No send attempts yet';
                    const failureSummary = row.invitation_email_failure_message ?
                        `<span class="protocol-inline-note">${escapeHtml(row.invitation_email_failure_message)}</span>` :
                        '';
                    const dirtySummary = hasLocalChanges ?
                        '<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis mt-2">Unsaved changes</span>' :
                        '';

                    return `
                        <tr class="${hasErrors ? 'protocol-row-error' : ''}">
                            <td>${escapeHtml(row.first_name || '—')}${errorMarkup}</td>
                            <td>${escapeHtml(row.middle_name || '—')}</td>
                            <td>${escapeHtml(row.last_name || '—')}</td>
                            <td>${escapeHtml(row.previous_name || '—')}</td>
                            <td>${escapeHtml(capitalize(row.gender) || '—')}</td>
                            <td>${escapeHtml(row.age || '—')}</td>
                            <td>${escapeHtml(row.email || '—')}</td>
                            <td>${escapeHtml(row.mobile_no || '—')}</td>
                            <td>${escapeHtml(row.ghcard || '—')}</td>
                            <td>
                                <span class="badge rounded-pill ${statusBadgeClass}">
                                    ${escapeHtml(statusLabel)}
                                </span>
                                <div class="small text-muted mt-1">${statusTime}</div>
                                <div class="small text-muted">${escapeHtml(attemptSummary)}</div>
                                ${failureSummary}
                                ${dirtySummary}
                            </td>
                            <td class="text-end">
                                <div class="protocol-table-actions">
                                    ${showSaveButton ? `
                                        <button type="button" class="${saveButtonClass} btn-sm protocol-save-row"
                                            data-row-key="${row.local_key}" ${actionDisabled}>
                                            ${saveButtonLabel}
                                        </button>
                                    ` : ''}
                                    <button type="button" class="btn btn-outline-primary protocol-edit-row"
                                        data-row-key="${row.local_key}" ${actionDisabled}>
                                        Edit
                                    </button>
                                    <button type="button" class="btn btn-outline-danger protocol-delete-row"
                                        data-row-key="${row.local_key}" ${actionDisabled}>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function renderPagination() {
                const totalRows = filteredRows().length;
                const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
                if (state.page > totalPages) {
                    state.page = totalPages;
                }

                const firstItem = totalRows === 0 ? 0 : ((state.page - 1) * pageSize) + 1;
                const lastItem = Math.min(state.page * pageSize, totalRows);
                dom.paginationInfo.textContent = totalRows === 0 ?
                    'Showing 0 rows' :
                    `Showing ${firstItem}-${lastItem} of ${totalRows} row(s)`;

                dom.prevPageBtn.disabled = state.page <= 1;
                dom.nextPageBtn.disabled = state.page >= totalPages;
            }

            function renderButtons() {
                dom.previewBtn.innerHTML = state.previewMode ?
                    '<i class="la la-edit me-1"></i> Edit Mode' :
                    '<i class="la la-eye me-1"></i> Preview';
                dom.saveBtn.disabled = state.isSaving || !state.rows.length || state.previewMode;
                dom.uploadBtn.disabled = state.isUploading || state.previewMode || state.isSaving;
                dom.addBtn.disabled = state.previewMode || state.isSaving;
                if (dom.tableAddBtn) {
                    dom.tableAddBtn.disabled = state.previewMode || state.isSaving;
                }
            }

            function render() {
                renderSummary();
                renderImportHistory();
                renderActivationHistory();
                renderTable();
                renderPagination();
                renderButtons();
            }

            function capitalize(value) {
                if (!value) {
                    return '';
                }

                return String(value).charAt(0).toUpperCase() + String(value).slice(1);
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function setFieldError(fieldName, message) {
                const input = dom.editForm.querySelector(`[name="${fieldName}"]`);
                if (!input) {
                    return;
                }

                input.classList.add('is-invalid');
                const feedback = input.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = message;
                }
            }

            function clearFieldErrors() {
                dom.editForm.querySelectorAll('.is-invalid').forEach((element) => {
                    element.classList.remove('is-invalid');
                });
                dom.editForm.querySelectorAll('.invalid-feedback').forEach((element) => {
                    element.textContent = '';
                });
            }

            function getRowByKey(localKey) {
                return state.rows.find((row) => row.local_key === localKey) || null;
            }

            function comparableRowValue(field, value) {
                if (field === 'ghcard') {
                    return normalizeGhcard(value || '');
                }

                if (field === 'gender') {
                    return String(value || '').trim().toLowerCase();
                }

                if (field === 'age') {
                    return value === null || value === undefined ? '' : String(value).trim();
                }

                return String(value || '').trim();
            }

            function rowHasLocalChanges(row, data) {
                return editableRowFields.some((field) => comparableRowValue(field, row[field]) !== comparableRowValue(field, data[field]));
            }

            function moveRowToTop(localKey) {
                const index = state.rows.findIndex((row) => row.local_key === localKey);
                if (index <= 0) {
                    return;
                }

                const [row] = state.rows.splice(index, 1);
                state.rows.unshift(row);
            }

            function clearRowErrors(localKey) {
                const row = getRowByKey(localKey);
                if (row) {
                    row.__errors = null;
                }
            }

            function toPayloadRow(row) {
                return {
                    local_key: row.local_key,
                    id: row.id,
                    first_name: row.first_name,
                    middle_name: row.middle_name,
                    last_name: row.last_name,
                    previous_name: row.previous_name,
                    gender: row.gender,
                    age: row.age === '' ? null : row.age,
                    email: row.email,
                    mobile_no: row.mobile_no,
                    ghcard: row.ghcard,
                    import_batch_id: row.import_batch_id,
                    import_batch_committed: row.import_batch_committed,
                };
            }

            function mergeSavedRow(localKey, savedRow) {
                const decorated = decorateRow(savedRow);
                decorated.__dirty = false;
                decorated.__errors = null;

                const index = state.rows.findIndex((row) => row.local_key === localKey);
                if (index === -1) {
                    state.rows.unshift(decorated);
                    return;
                }

                state.rows.splice(index, 1, decorated);
            }

            function openEditModal(row, {
                mode = 'edit'
            } = {}) {
                clearFieldErrors();
                state.editingKey = row.local_key;
                state.modalMode = mode;

                document.getElementById('protocol-edit-local-key').value = row.local_key;
                document.getElementById('protocol-first-name').value = row.first_name || '';
                document.getElementById('protocol-middle-name').value = row.middle_name || '';
                document.getElementById('protocol-last-name').value = row.last_name || '';
                document.getElementById('protocol-previous-name').value = row.previous_name || '';
                document.getElementById('protocol-gender').value = row.gender || '';
                document.getElementById('protocol-age').value = row.age || '';
                document.getElementById('protocol-email').value = row.email || '';
                document.getElementById('protocol-mobile-no').value = row.mobile_no || '';
                document.getElementById('protocol-ghcard').value = row.ghcard || '';

                const emailMeta = document.getElementById('protocol-email-meta');
                const ghcardMeta = document.getElementById('protocol-ghcard-meta');
                const emailInput = document.getElementById('protocol-email');
                const ghcardInput = document.getElementById('protocol-ghcard');
                const emailChangesUsed = Number(row.email_change_attempts || 0);
                const ghcardChangesUsed = Number(row.ghcard_change_attempts || 0);
                const emailChangesRemaining = Math.max(0, 2 - emailChangesUsed);
                const ghcardChangesRemaining = Math.max(0, 2 - ghcardChangesUsed);
                const isAddMode = mode === 'add';

                emailInput.disabled = Boolean(row.id) && emailChangesRemaining === 0;
                ghcardInput.disabled = Boolean(row.id) && ghcardChangesRemaining === 0;
                dom.modalContext.textContent = isAddMode ? 'Add participant' : 'Edit participant';
                document.getElementById('protocolEditModalLabel').textContent = isAddMode ? 'Add Participant' : 'Edit Participant';
                dom.modalSubtitle.textContent = isAddMode ?
                    'Enter the participant details below. Email status and table actions are added automatically after save.' :
                    'Update the participant details below, then save only this row or save the whole table.';
                dom.editSaveBtn.textContent = isAddMode ? 'Add Participant' : 'Apply Changes';

                emailMeta.textContent = row.id ?
                    `Email changes used: ${emailChangesUsed}/2. Remaining: ${emailChangesRemaining}. A fresh activation email is sent whenever the email changes.` :
                    'A welcome activation email will be sent after the row is saved.';
                ghcardMeta.textContent = row.id ?
                    `Ghana Card changes used: ${ghcardChangesUsed}/2. Remaining: ${ghcardChangesRemaining}. Updating it also triggers a fresh activation email.` :
                    'Use the final Ghana Card number to generate the activation link.';

                cleanupModalArtifacts();
                editModal.show();
            }

            function collectEditFormData() {
                const data = {};
                const formData = new FormData(dom.editForm);
                formData.forEach((value, key) => {
                    data[key] = key === 'ghcard' ? normalizeGhcard(value) : String(value || '').trim();
                });

                return data;
            }

            function validateEditRow(row, data) {
                const errors = {};
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const ghcardRegex = /^GHA-\d{9}-\d$/;

                if (!data.first_name) {
                    errors.first_name = 'First name is required.';
                }

                if (!data.last_name) {
                    errors.last_name = 'Last name is required.';
                }

                if (!data.gender || !['male', 'female'].includes(data.gender.toLowerCase())) {
                    errors.gender = 'Select a valid gender.';
                }

                if (data.age && (!/^\d+$/.test(data.age) || Number(data.age) < 0 || Number(data.age) > 120)) {
                    errors.age = 'Age must be between 0 and 120.';
                }

                if (!data.email || !emailRegex.test(data.email)) {
                    errors.email = 'Provide a valid email address.';
                }

                if (!data.mobile_no) {
                    errors.mobile_no = 'Mobile number is required.';
                }

                if (!data.ghcard || !ghcardRegex.test(data.ghcard)) {
                    errors.ghcard = 'Ghana Card number must match GHA-123456789-0.';
                }

                const changingEmail = Boolean(row.id) && row.email && row.email !== data.email;
                if (changingEmail && Number(row.email_change_attempts || 0) >= 2) {
                    errors.email = 'Email can only be changed twice for a saved participant.';
                }

                const changingGhcard = Boolean(row.id) && row.ghcard && row.ghcard !== data.ghcard;
                if (changingGhcard && Number(row.ghcard_change_attempts || 0) >= 2) {
                    errors.ghcard = 'Ghana Card number can only be changed twice for a saved participant.';
                }

                return errors;
            }

            function applyEditChanges() {
                const localKey = document.getElementById('protocol-edit-local-key').value;
                const row = getRowByKey(localKey);
                if (!row) {
                    return;
                }

                const data = collectEditFormData();
                const errors = validateEditRow(row, data);
                clearFieldErrors();

                if (Object.keys(errors).length > 0) {
                    Object.entries(errors).forEach(([field, message]) => setFieldError(field, message));
                    return;
                }

                const changed = rowHasLocalChanges(row, data);
                Object.assign(row, data, {
                    age: data.age || '',
                    gender: String(data.gender || '').toLowerCase(),
                    __errors: null,
                    __dirty: !row.id || row.__dirty || changed,
                });

                if (state.modalMode === 'add') {
                    moveRowToTop(row.local_key);
                    state.page = 1;
                }

                render();
                editModal.hide();
            }

            function mergeImportedRows(importedRows) {
                importedRows.forEach((incoming) => {
                    const normalized = decorateRow(incoming);
                    const existing = state.rows.find((row) =>
                        (normalized.id && row.id === normalized.id) ||
                        (normalized.ghcard && row.ghcard === normalized.ghcard) ||
                        (normalized.email && row.email === normalized.email)
                    );

                    if (existing) {
                        Object.assign(existing, normalized, {
                            local_key: existing.local_key,
                            id: existing.id ?? normalized.id,
                            email_change_attempts: existing.email_change_attempts ?? 0,
                            ghcard_change_attempts: existing.ghcard_change_attempts ?? 0,
                            activation_email_sent_at: existing.activation_email_sent_at ?? normalized.activation_email_sent_at,
                            invitation_email_status: existing.invitation_email_status ?? normalized.invitation_email_status,
                            invitation_email_queued_at: existing.invitation_email_queued_at ?? normalized.invitation_email_queued_at,
                            invitation_email_last_attempt_at: existing.invitation_email_last_attempt_at ?? normalized.invitation_email_last_attempt_at,
                            invitation_email_failed_at: existing.invitation_email_failed_at ?? normalized.invitation_email_failed_at,
                            invitation_email_attempts: existing.invitation_email_attempts ?? normalized.invitation_email_attempts,
                            invitation_email_failure_message: existing.invitation_email_failure_message ?? normalized.invitation_email_failure_message,
                            created_at: existing.created_at ?? normalized.created_at,
                        });
                    } else {
                        state.rows.unshift(normalized);
                    }
                });

                state.page = 1;
                render();
            }

            function mergeSnapshotRows(serverRows) {
                const incomingRows = Array.isArray(serverRows) ? serverRows.map(decorateRow) : [];
                const incomingById = new Map(
                    incomingRows
                        .filter((row) => row.id !== null)
                        .map((row) => [String(row.id), row]),
                );

                const nextRows = [];
                const seenIds = new Set();

                state.rows.forEach((row) => {
                    if (!row.id) {
                        nextRows.push(row);
                        return;
                    }

                    const incoming = incomingById.get(String(row.id));
                    if (!incoming) {
                        return;
                    }

                    seenIds.add(String(row.id));
                    Object.assign(row, {
                        import_batch_id: incoming.import_batch_id,
                        import_batch_committed: incoming.import_batch_committed,
                        email_change_attempts: incoming.email_change_attempts,
                        ghcard_change_attempts: incoming.ghcard_change_attempts,
                        activation_email_sent_at: incoming.activation_email_sent_at,
                        invitation_email_status: incoming.invitation_email_status,
                        invitation_email_queued_at: incoming.invitation_email_queued_at,
                        invitation_email_last_attempt_at: incoming.invitation_email_last_attempt_at,
                        invitation_email_failed_at: incoming.invitation_email_failed_at,
                        invitation_email_attempts: incoming.invitation_email_attempts,
                        invitation_email_failure_message: incoming.invitation_email_failure_message,
                        created_at: incoming.created_at,
                        updated_at: incoming.updated_at,
                        __dirty: false,
                        __errors: null,
                    });
                    nextRows.push(row);
                });

                incomingRows.forEach((row) => {
                    if (row.id === null || seenIds.has(String(row.id))) {
                        return;
                    }

                    nextRows.unshift(row);
                });

                state.rows = nextRows;
            }

            async function refreshProtocolSnapshot({ silent = true } = {}) {
                if (state.isSaving || state.isUploading) {
                    return;
                }

                try {
                    const response = await fetch(snapshotUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to refresh protocol status.');
                    }

                    mergeSnapshotRows(payload.rows || []);
                    state.importBatches = Array.isArray(payload.import_batches) ?
                        payload.import_batches.map(decorateImportBatch) :
                        state.importBatches;
                    state.activationHistory = Array.isArray(payload.activation_history) ?
                        payload.activation_history.map(decorateActivationHistory) :
                        state.activationHistory;
                    render();
                } catch (error) {
                    if (!silent) {
                        showAlert('warning', error.message || 'Unable to refresh protocol status.');
                    }
                }
            }

            async function uploadSheet() {
                const file = dom.uploadInput.files[0];
                if (!file) {
                    showAlert('warning', 'Choose a CSV or Excel file before uploading.');
                    return;
                }

                state.isUploading = true;
                renderButtons();

                try {
                    const formData = new FormData();
                    formData.append('file', file);

                    const response = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to parse the spreadsheet.');
                    }

                    state.importBatches = Array.isArray(payload.import_batches) ? payload.import_batches.map(decorateImportBatch) : state.importBatches;
                    mergeImportedRows(payload.rows || []);
                    showAlert('success', `${(payload.rows || []).length} row(s) imported into the preview table.`);
                    dom.uploadInput.value = '';
                } catch (error) {
                    showAlert('danger', error.message || 'Unable to parse the spreadsheet.');
                } finally {
                    state.isUploading = false;
                    renderButtons();
                }
            }

            function payloadRows() {
                return state.rows.map((row) => toPayloadRow(row));
            }

            function applyServerErrors(errorRows, { clearExisting = true } = {}) {
                if (clearExisting) {
                    state.rows.forEach((row) => {
                        row.__errors = null;
                    });
                }

                errorRows.forEach((errorRow) => {
                    const target = getRowByKey(errorRow.local_key);
                    if (target) {
                        target.__errors = errorRow.messages || ['This row needs attention.'];
                        target.__dirty = true;
                    }
                });
            }

            async function submitRows(rows) {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        rows,
                    }),
                });

                const payload = await response.json();

                return {
                    response,
                    payload,
                };
            }

            async function saveSingleRow(localKey) {
                const row = getRowByKey(localKey);
                if (!row || state.isSaving || state.previewMode) {
                    return;
                }

                state.isSaving = true;
                state.savingRowKey = localKey;
                clearRowErrors(localKey);
                render();

                try {
                    const {
                        response,
                        payload
                    } = await submitRows([toPayloadRow(row)]);

                    state.importBatches = Array.isArray(payload.import_batches) ?
                        payload.import_batches.map(decorateImportBatch) :
                        state.importBatches;

                    if (response.status === 422) {
                        applyServerErrors(payload.errors || [], {
                            clearExisting: false
                        });
                        render();
                        const rowMessage = (payload.errors || [])[0]?.messages?.[0];
                        showAlert('warning', rowMessage || payload.message || 'This row needs attention before it can be saved.', 7000);
                        return;
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to save the selected participant.');
                    }

                    const savedRow = Array.isArray(payload.rows) ? payload.rows[0] : null;
                    if (savedRow) {
                        mergeSavedRow(localKey, savedRow);
                    }

                    showAlert('success', 'Participant saved successfully.');
                    render();
                } catch (error) {
                    showAlert('danger', error.message || 'Unable to save the selected participant.');
                } finally {
                    state.isSaving = false;
                    state.savingRowKey = null;
                    render();
                }
            }

            async function saveAllRows() {
                if (!state.rows.length || state.isSaving || state.previewMode) {
                    return;
                }

                state.isSaving = true;
                state.savingRowKey = null;
                render();

                try {
                    const {
                        response,
                        payload
                    } = await submitRows(payloadRows());
                    if (response.status === 422) {
                        state.importBatches = Array.isArray(payload.import_batches) ? payload.import_batches.map(decorateImportBatch) : state.importBatches;
                        applyServerErrors(payload.errors || [], {
                            clearExisting: true
                        });
                        render();
                        const message = (payload.errors || [])
                            .slice(0, 3)
                            .map((item) => `Row ${item.row}: ${item.messages[0]}`)
                            .join('<br>');
                        showAlert('warning', message || payload.message || 'Some rows need attention.', 7000);
                        return;
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to save the protocol list.');
                    }

                    state.rows = (payload.rows || []).map(decorateRow);
                    state.importBatches = Array.isArray(payload.import_batches) ? payload.import_batches.map(decorateImportBatch) : state.importBatches;
                    state.page = 1;
                    showAlert('success', payload.message || 'Protocol list saved successfully.');
                    render();
                } catch (error) {
                    showAlert('danger', error.message || 'Unable to save the protocol list.');
                } finally {
                    state.isSaving = false;
                    state.savingRowKey = null;
                    render();
                }
            }

            async function deleteRow() {
                const row = getRowByKey(state.deletingKey);
                if (!row) {
                    deleteModal.hide();
                    return;
                }

                try {
                    if (row.id) {
                        const response = await fetch(`${deleteBaseUrl}/${row.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                        const payload = await response.json();
                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to delete the participant.');
                        }
                    }

                    state.rows = state.rows.filter((item) => item.local_key !== row.local_key);
                    deleteModal.hide();
                    showAlert('success', 'Participant removed successfully.');
                    render();
                } catch (error) {
                    showAlert('danger', error.message || 'Unable to delete the participant.');
                }
            }

            function bindEvents() {
                const handleAddParticipantClick = () => {
                    const localKey = cryptoRandomKey();
                    const newRow = decorateRow({
                        local_key: localKey,
                        __dirty: true,
                    });
                    state.rows.unshift(newRow);
                    state.page = 1;
                    render();
                    openEditModal(newRow, {
                        mode: 'add'
                    });
                };

                dom.searchInput.addEventListener('input', (event) => {
                    state.query = event.target.value || '';
                    state.page = 1;
                    render();
                });

                dom.previewBtn.addEventListener('click', () => {
                    state.previewMode = !state.previewMode;
                    render();
                });

                dom.uploadBtn.addEventListener('click', uploadSheet);
                dom.addBtn.addEventListener('click', handleAddParticipantClick);
                if (dom.tableAddBtn) {
                    dom.tableAddBtn.addEventListener('click', handleAddParticipantClick);
                }
                dom.saveBtn.addEventListener('click', saveAllRows);
                window.addEventListener('focus', () => {
                    refreshProtocolSnapshot();
                });
                dom.prevPageBtn.addEventListener('click', () => {
                    state.page = Math.max(1, state.page - 1);
                    render();
                });
                dom.nextPageBtn.addEventListener('click', () => {
                    const totalPages = Math.max(1, Math.ceil(filteredRows().length / pageSize));
                    state.page = Math.min(totalPages, state.page + 1);
                    render();
                });

                document.getElementById('protocol-ghcard').addEventListener('input', (event) => {
                    event.target.value = normalizeGhcard(event.target.value);
                });

                dom.editForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    applyEditChanges();
                });
                dom.deleteConfirmBtn.addEventListener('click', deleteRow);

                dom.tableBody.addEventListener('click', (event) => {
                    const saveButton = event.target.closest('.protocol-save-row');
                    if (saveButton) {
                        saveSingleRow(saveButton.dataset.rowKey);
                        return;
                    }

                    const editButton = event.target.closest('.protocol-edit-row');
                    if (editButton) {
                        const row = getRowByKey(editButton.dataset.rowKey);
                        if (row) {
                            openEditModal(row, {
                                mode: 'edit'
                            });
                        }
                        return;
                    }

                    const deleteButton = event.target.closest('.protocol-delete-row');
                    if (deleteButton) {
                        state.deletingKey = deleteButton.dataset.rowKey;
                        cleanupModalArtifacts();
                        deleteModal.show();
                    }
                });

                editModalEl.addEventListener('shown.bs.modal', () => {
                    document.getElementById('protocol-first-name')?.focus();
                });
                editModalEl.addEventListener('hidden.bs.modal', () => {
                    cleanupModalArtifacts();
                    clearFieldErrors();
                    const localKey = document.getElementById('protocol-edit-local-key').value;
                    const row = getRowByKey(localKey);
                    if (
                        row &&
                        !row.id &&
                        !row.first_name &&
                        !row.middle_name &&
                        !row.last_name &&
                        !row.previous_name &&
                        !row.email &&
                        !row.mobile_no &&
                        !row.ghcard
                    ) {
                        state.rows = state.rows.filter((item) => item.local_key !== row.local_key);
                        render();
                    }
                });
                deleteModalEl.addEventListener('hidden.bs.modal', cleanupModalArtifacts);

                snapshotTimer = window.setInterval(() => {
                    refreshProtocolSnapshot();
                }, snapshotPollMs);
            }

            bindEvents();
            render();
        })();
    </script>
@endpush
