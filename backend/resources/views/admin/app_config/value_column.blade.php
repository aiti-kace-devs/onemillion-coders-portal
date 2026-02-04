@php
    $value = $entry->value;
    $type = $entry->type;
    $id = $entry->getKey();
@endphp

@if($type === 'boolean')
    <label class="form-check form-switch mb-0">
        <input class="form-check-input app-config-toggle" type="checkbox" 
               data-id="{{ $id }}" 
               {{ $value ? 'checked' : '' }}
               onclick="toggleAppConfig(this, {{ $id }})">
    </label>
@else
    <span title="{{ $value }}">{{ \Illuminate\Support\Str::limit($value, 40) }}</span>
@endif

<script>
    if (typeof toggleAppConfig !== 'function') {
        function toggleAppConfig(element, id) {
            let checked = element.checked;
            let value = checked ? 1 : 0;
            
            // Disable to prevent double click
            element.disabled = true;

            $.ajax({
                url: 'app-config/' + id + '/toggle',
                type: 'POST',
                data: {
                    value: value
                },
                success: function(result) {
                    new Noty({
                        type: "success",
                        text: "Config updated successfully",
                    }).show();
                },
                error: function(result) {
                    // Revert change
                    element.checked = !checked;
                    new Noty({
                        type: "error",
                        text: "Error updating config",
                    }).show();
                },
                complete: function() {
                    element.disabled = false;
                }
            });
        }
    }
</script>
