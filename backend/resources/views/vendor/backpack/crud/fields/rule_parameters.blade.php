@php
    // Field config:
    // - $field['name'] should be the underlying attribute, e.g. 'default_parameters'
    // - $field['value'] is expected to be an array (cast on the model)
    //
    // We present it as key/value rows and let the controller turn it into
    // an associative array before saving.

    $rawValue = $field['value'] ?? [];

    // Normalize to an array of ['key' => ..., 'value' => ...] rows
    $rows = [];
    if (is_array($rawValue)) {
        // If it's already in [ [key=>.., value=>..], ... ] form, keep it
        if (isset($rawValue[0]) && is_array($rawValue[0]) && array_key_exists('key', $rawValue[0])) {
            foreach ($rawValue as $row) {
                $rows[] = [
                    'key' => $row['key'] ?? '',
                    'value' => $row['value'] ?? '',
                ];
            }
        } else {
            // Assume associative array: key => value
            foreach ($rawValue as $k => $v) {
                $rows[] = [
                    'key' => (string) $k,
                    'value' => is_scalar($v) ? (string) $v : json_encode($v),
                ];
            }
        }
    }

    if (empty($rows)) {
        $rows[] = ['key' => '', 'value' => ''];
    }

    $fieldName = $field['name'];
@endphp

@include('crud::fields.inc.wrapper_start')
    {{-- Label --}}
    <label>{!! $field['label'] ?? ucfirst($fieldName) !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <div class="rule-parameters-wrapper" data-field-name="{{ $fieldName }}">
        <div class="rule-parameters-rows row">
            @foreach($rows as $index => $row)
                <div class="form-row mb-2 rule-parameter-row" data-index="{{ $index }}">
                    <div class="col-md-5">
                        <input type="text"
                               class="form-control"
                               name="{{ $fieldName }}[{{ $index }}][key]"
                               value="{{ old($fieldName.'.'.$index.'.key', $row['key']) }}"
                               placeholder="Property (e.g. pass_mark)">
                    </div>
                    <div class="col-md-5">
                        <input type="text"
                               class="form-control"
                               name="{{ $fieldName }}[{{ $index }}][value]"
                               value="{{ old($fieldName.'.'.$index.'.value', $row['value']) }}"
                               placeholder="Value (e.g. 70)">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-rule-parameter">
                            <i class="la la-trash"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-outline-primary btn-sm mt-2 add-rule-parameter">
            <i class="la la-plus"></i> Add parameter
        </button>
    </div>

    {{-- Hint --}}
    @if (isset($field['hint']))
        <p class="help-block mt-2">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.rule-parameters-wrapper').forEach(function (wrapper) {
        const rowsContainer = wrapper.querySelector('.rule-parameters-rows');
        let index = rowsContainer.querySelectorAll('.rule-parameter-row').length || 0;
        const fieldName = wrapper.getAttribute('data-field-name');

        const addButton = wrapper.querySelector('.add-rule-parameter');
        if (addButton) {
            addButton.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'form-row mb-2 rule-parameter-row';
                row.setAttribute('data-index', index);
                row.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <input type="text"
                               class="form-control"
                               name="${fieldName}[${index}][key]"
                               placeholder="Property (e.g. pass_mark)">
                    </div>
                    <div class="col-md-5">
                        <input type="text"
                               class="form-control"
                               name="${fieldName}[${index}][value]"
                               placeholder="Value (e.g. 70)">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-rule-parameter">
                            <i class="la la-trash"></i>
                        </button>
                    </div>
                </div>
                `;
                rowsContainer.appendChild(row);
                index++;
            });
        }

        rowsContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-remove-rule-parameter');
            if (!btn) return;
            const row = btn.closest('.rule-parameter-row');
            if (row) {
                row.remove();
            }
        });
    });
});
</script>
@endpush

