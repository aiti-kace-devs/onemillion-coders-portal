@once
    @push('crud_fields_styles')
        <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @endpush

    @push('crud_fields_scripts')
        <script src="{{ asset('assets/plugins/select2/js/select2.full.min.js') }}"></script>
        @if (app()->getLocale() !== 'en')
            <script src="{{ asset('assets/plugins/select2/js/i18n/' . str_replace('_', '-', app()->getLocale()) . '.js') }}"></script>
        @endif
    @endpush
@endonce

<div class="mb-3">
    <button type="button" class="btn btn-primary" onclick="openAddDistrictCentreModal()">
        <i class="la la-plus"></i> Add Centre
    </button>
</div>

@if($assignedCentres->isNotEmpty())
    <div class="mb-3">
        <input type="search" id="districtCentresSearch" class="form-control" placeholder="Search assigned centres..." autocomplete="off">
    </div>
@endif

@if($assignedCentres->isEmpty())
    <p id="districtCentresEmptyMsg" class="text-muted">No centres assigned to this district yet.</p>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Centre</th>
                    <th>Branch</th>
                    <th style="width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody id="districtCentresTableBody">
                @foreach($assignedCentres as $centre)
                    <tr>
                        <td>{{ $centre->title }}</td>
                        <td>{{ $centre->branch?->title ?? '-' }}</td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-sm btn-link text-danger"
                                data-delete-url="{{ backpack_url('district/' . $district->id . '/remove-centre/' . $centre->id) }}"
                                data-centre-title="{{ $centre->title }}"
                                onclick="confirmDeleteDistrictCentre(this)"
                            >
                                <i class="la la-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p id="districtCentresNoResultsMsg" class="text-muted text-center py-3" style="display:none;">No matching centres found.</p>
@endif

<div class="modal fade district-centre-modal" id="addDistrictCentreModal" tabindex="-1" role="dialog" aria-labelledby="addDistrictCentreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="margin-top: 50px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDistrictCentreModalLabel">Add Centres to District</h5>
                <button
                    type="button"
                    class="close district-modal-close-btn"
                    data-bs-dismiss="modal"
                    data-dismiss="modal"
                    aria-label="Close"
                    onclick="closeDistrictCentreModal()"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                @if($availableCentres->isEmpty())
                    <p class="text-muted mb-0">No available centres to add for this district.</p>
                @else
                    <div class="form-group mb-0">
                        <label for="districtCentreIds">Centres</label>
                        <select
                            id="districtCentreIds"
                            class="form-control select2_multiple"
                            data-placeholder="Select one or more centres"
                            multiple
                        >
                            @foreach($availableCentres as $centre)
                                <option value="{{ $centre->id }}">
                                    {{ $centre->title }} @if($centre->branch?->title) ({{ $centre->branch->title }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Select one or more centres.</small>
                    </div>
                @endif
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                    data-dismiss="modal"
                    onclick="closeDistrictCentreModal()"
                >
                    Close
                </button>
                <button type="button" class="btn btn-primary" onclick="submitAddDistrictCentres()" @if($availableCentres->isEmpty()) disabled @endif>
                    Add Selected Centres
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #addDistrictCentreModal {
        z-index: 2060;
    }

    .select2-container--open {
        z-index: 2070;
    }

    .district-centre-modal .modal-header {
        display: flex;
        align-items: center;
    }

    .district-centre-modal .modal-title {
        margin: 0;
    }

    .district-modal-close-btn {
        margin-left: auto;
        background: transparent;
        border: 0;
        font-size: 1.6rem;
        line-height: 1;
        padding: 0.15rem 0.4rem;
        cursor: pointer;
    }

    .district-modal-close-btn:focus {
        outline: none;
        box-shadow: none;
    }
</style>

<script>
(() => {
    'use strict';

    if (window.__districtCentresManagementInitialized) {
        return;
    }
    window.__districtCentresManagementInitialized = true;

    const modalId = 'addDistrictCentreModal';
    const addCentresUrl = @json(backpack_url('district/' . $district->id . '/add-centres'));
    const csrfToken = @json(csrf_token());

    function getModalElement() {
        return document.getElementById(modalId);
    }

    function ensureModalInBody() {
        const modalEl = getModalElement();
        if (!modalEl) return null;

        if (document.body && modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        return modalEl;
    }

    function showModal(modalElement) {
        if (!modalElement) return;

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return;
        }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery(modalElement).modal('show');
        }
    }

    function hideModal(modalElement) {
        if (!modalElement) return;

        if (window.bootstrap && window.bootstrap.Modal) {
            const instance = window.bootstrap.Modal.getOrCreateInstance(modalElement);
            instance.hide();
            return;
        }

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) {
            window.jQuery(modalElement).modal('hide');
        }
    }

    function initSelect2InModal(modalElement) {
        const jq = window.jQuery;
        if (!jq || !jq.fn || !jq.fn.select2 || !modalElement) return;
        if (modalElement.dataset && modalElement.dataset.select2Initialized === '1') return;

        const $modal = jq(modalElement);
        const $dropdownParent = $modal.find('.modal-content').length ? $modal.find('.modal-content') : jq(document.body);
        const $centres = $modal.find('#districtCentreIds');

        if ($centres.length && !$centres.hasClass('select2-hidden-accessible')) {
            $centres.select2({
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $dropdownParent,
                placeholder: $centres.attr('data-placeholder') || 'Select one or more centres',
                closeOnSelect: false,
                allowClear: true,
            });
        }

        if (modalElement.dataset) {
            modalElement.dataset.select2Initialized = '1';
        }
    }

    function getSelectedCentreIds() {
        const selectEl = document.getElementById('districtCentreIds');
        if (!selectEl) return [];

        const jq = window.jQuery;
        if (jq) {
            const val = jq(selectEl).val();
            if (Array.isArray(val)) {
                return val.filter((item) => item !== null && item !== '');
            }
        }

        return Array.from(selectEl.selectedOptions || [])
            .map((opt) => opt.value)
            .filter((value) => value !== '');
    }

    function appendHiddenInput(form, name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }

    function submitPostForm(actionUrl, payload, methodOverride) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = actionUrl;
        form.style.display = 'none';

        appendHiddenInput(form, '_token', csrfToken);
        if (methodOverride && methodOverride !== 'POST') {
            appendHiddenInput(form, '_method', methodOverride);
        }

        Object.keys(payload || {}).forEach((key) => {
            const value = payload[key];
            if (Array.isArray(value)) {
                value.forEach((item) => appendHiddenInput(form, key, item));
                return;
            }

            appendHiddenInput(form, key, value);
        });

        document.body.appendChild(form);
        form.submit();
    }

    function showWarning(title, text) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({ icon: 'warning', title, text });
            return;
        }

        if (window.swal && typeof window.swal === 'function') {
            window.swal(title, text, 'warning');
            return;
        }

        alert(text);
    }

    function confirmDelete(title, text) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                icon: 'warning',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: 'Yes, remove',
                cancelButtonText: 'Cancel',
            }).then((result) => !!result.isConfirmed);
        }

        if (window.swal && typeof window.swal === 'function') {
            return new Promise((resolve) => {
                window.swal({
                    title,
                    text,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then((isConfirmed) => resolve(!!isConfirmed));
            });
        }

        return Promise.resolve(window.confirm(text));
    }

    function applyDistrictCentresSearchFilter() {
        const tbody = document.getElementById('districtCentresTableBody');
        if (!tbody) return;

        const searchInput = document.getElementById('districtCentresSearch');
        const query = searchInput ? String(searchInput.value || '').trim().toLowerCase() : '';
        const noResultsMsg = document.getElementById('districtCentresNoResultsMsg');

        let visibleCount = 0;
        Array.from(tbody.querySelectorAll('tr')).forEach((row) => {
            const rowText = String(row.textContent || '').toLowerCase();
            const match = !query || rowText.includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        if (noResultsMsg) {
            noResultsMsg.style.display = query && visibleCount === 0 ? 'block' : 'none';
        }
    }

    function initDistrictCentresSearch() {
        const searchInput = document.getElementById('districtCentresSearch');
        if (!searchInput) return;

        const onChange = () => applyDistrictCentresSearchFilter();
        searchInput.addEventListener('input', onChange);
        searchInput.addEventListener('search', onChange);
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                searchInput.value = '';
                applyDistrictCentresSearchFilter();
            }
        });

        applyDistrictCentresSearchFilter();
    }

    window.openAddDistrictCentreModal = function () {
        const modalEl = ensureModalInBody();
        initSelect2InModal(modalEl);
        showModal(modalEl);
    };

    window.closeDistrictCentreModal = function () {
        hideModal(getModalElement());
    };

    window.submitAddDistrictCentres = function () {
        const selectedCentreIds = getSelectedCentreIds();
        if (selectedCentreIds.length === 0) {
            showWarning('No centres selected', 'Please select at least one centre.');
            return;
        }

        window.closeDistrictCentreModal();
        submitPostForm(addCentresUrl, {
            'centre_ids[]': selectedCentreIds,
        });
    };

    window.confirmDeleteDistrictCentre = function (triggerEl) {
        if (!triggerEl) return;

        const deleteUrl = triggerEl.getAttribute('data-delete-url');
        const centreTitle = triggerEl.getAttribute('data-centre-title') || 'this centre';

        if (!deleteUrl) return;

        confirmDelete(
            'Remove centre?',
            'This will remove ' + centreTitle + ' from this district only.'
        ).then((isConfirmed) => {
            if (!isConfirmed) return;
            submitPostForm(deleteUrl, {}, 'DELETE');
        });
    };

    function init() {
        const modalEl = ensureModalInBody();
        initSelect2InModal(modalEl);
        initDistrictCentresSearch();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
</script>
