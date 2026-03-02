@php
    // Backwards-compatible wrapper for AppConfig, powered by the generic toggle column view.
    $column = array_merge($column ?? [], [
        'toggleable_if' => ['field' => 'type', 'equals' => 'boolean'],
        'toggle_url' => 'app-config/{id}/toggle',
        'toggle_success_message' => 'Config updated successfully',
        'toggle_error_message' => 'Error updating config',
    ]);
@endphp

@include('admin.status_toggle.status_column')
