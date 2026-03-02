@php
    $id = $entry->getKey();
    $columnName = $column['name'] ?? 'status';
    $value = data_get($entry, $columnName);

    $toggleable = $column['toggleable'] ?? null;
    if (is_callable($toggleable)) {
        $toggleable = (bool) $toggleable($entry);
    } elseif (is_array($column['toggleable_if'] ?? null)) {
        $toggleableIf = $column['toggleable_if'];
        $toggleable = data_get($entry, $toggleableIf['field'] ?? '') == ($toggleableIf['equals'] ?? null);
    } elseif (is_bool($toggleable)) {
    } else {
        $toggleable = is_bool($value) || in_array($value, [0, 1, '0', '1'], true);
    }

    $toggleUrl = $column['toggle_url'] ?? null;
    if (is_callable($toggleUrl)) {
        $toggleUrl = $toggleUrl($entry);
    }
    $crudRoute = '';
    if (isset($crud) && is_object($crud)) {
        $crudRoute = $crud->route ?? '';
    }
    $toggleUrl = $toggleUrl ?: trim($crudRoute, '/') . '/' . $id . '/toggle';
    $toggleUrl = str_replace('{id}', $id, $toggleUrl);

    if (is_string($toggleUrl) && !str_contains($toggleUrl, '://')) {
        if (str_starts_with($toggleUrl, '/')) {
            $toggleUrl = url($toggleUrl);
        } else {
            $adminPrefix = trim((string) config('backpack.base.route_prefix', 'admin'), '/');
            $toggleUrl = str_starts_with($toggleUrl, $adminPrefix . '/')
                ? url($toggleUrl)
                : backpack_url($toggleUrl);
        }
    }

    $toggleMethod = strtoupper($column['toggle_method'] ?? 'POST');
    $valueParam = $column['toggle_value_param'] ?? 'value';
    $checkedValue = $column['toggle_checked_value'] ?? 1;
    $uncheckedValue = $column['toggle_unchecked_value'] ?? 0;
    $successMessage = $column['toggle_success_message'] ?? 'Updated successfully';
    $errorMessage = $column['toggle_error_message'] ?? 'Error updating value';
@endphp

@if($toggleable)
    <label class="form-check form-switch mb-0">
        <input
            class="form-check-input"
            type="checkbox"
            data-toggle-field="{{ $columnName }}"
            data-toggle-url="{{ $toggleUrl }}"
            data-toggle-method="{{ $toggleMethod }}"
            data-toggle-value-param="{{ $valueParam }}"
            data-toggle-checked-value="{{ $checkedValue }}"
            data-toggle-unchecked-value="{{ $uncheckedValue }}"
            data-toggle-success-message="{{ $successMessage }}"
            data-toggle-error-message="{{ $errorMessage }}"
            {{ ((bool) $value) ? 'checked' : '' }}
            onclick="if (window.bpToggleColumn) window.bpToggleColumn(this)"
        >
    </label>
@else
    <span title="{{ $value }}">{{ \Illuminate\Support\Str::limit($value, 40) }}</span>
@endif

@once
<script>
if (typeof window.bpToggleColumn !== 'function') {
    window.bpToggleColumn = function (element) {
        if (typeof window.$ === 'undefined' || typeof window.$.ajax !== 'function') {
            console.error('bpToggleColumn requires jQuery.');
            return;
        }

        const checked = element.checked;
        const checkedValue = element.dataset && element.dataset.toggleCheckedValue !== undefined
            ? element.dataset.toggleCheckedValue
            : '1';
        const uncheckedValue = element.dataset && element.dataset.toggleUncheckedValue !== undefined
            ? element.dataset.toggleUncheckedValue
            : '0';
        const value = checked ? checkedValue : uncheckedValue;

        const url = element.dataset.toggleUrl;
        const method = (element.dataset.toggleMethod || 'POST').toUpperCase();
        const valueParam = element.dataset.toggleValueParam || 'value';
        const successMessageDefault = element.dataset.toggleSuccessMessage || 'Updated successfully';
        const errorMessageDefault = element.dataset.toggleErrorMessage || 'Error updating value';

        if (!url) {
            new Noty({ type: "error", text: "Missing toggle URL" }).show();
            element.checked = !checked;
            return;
        }

        element.disabled = true;

        window.$.ajax({
            url: url,
            type: method,
            data: { [valueParam]: value },
            success: function (result) {
                const message = result && (result.message || result.msg) ? (result.message || result.msg) : successMessageDefault;
                new Noty({ type: "success", text: message }).show();

                // Optional: sync other toggle columns in the same row (useful when one toggle affects another).
                if (result && result.updates && typeof result.updates === 'object') {
                    const row = element.closest ? element.closest('tr') : null;
                    if (row) {
                        Object.entries(result.updates).forEach(([field, val]) => {
                            if (!field) return;
                            const selector = `input.form-check-input[data-toggle-field="${String(field).replace(/"/g, '\\"')}"]`;
                            const target = row.querySelector(selector);
                            if (target && target !== element) {
                                target.checked = Boolean(Number(val));
                            }
                        });
                    }
                }
            },
            error: function (xhr) {
                element.checked = !checked;

                let message = errorMessageDefault;
                if (xhr && xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.error) {
                        message = xhr.responseJSON.error;
                    } else if (xhr.responseJSON.errors) {
                        const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                        const firstErr = firstKey ? xhr.responseJSON.errors[firstKey] : null;
                        if (Array.isArray(firstErr) && firstErr.length) {
                            message = firstErr[0];
                        }
                    }
                }

                new Noty({ type: "error", text: message }).show();
            },
            complete: function () {
                element.disabled = false;
            }
        });
    };
}
</script>
@endonce
