<!-- resources/views/vendor/backpack/crud/fields/rule_parameters.blade.php -->
@php
    $field['value'] = old($field['name']) ? old($field['name']) : (isset($field['value']) ? $field['value'] : (isset($field['default']) ? $field['default'] : []));
    if (is_string($field['value']) && !empty($field['value'])) {
        $field['value'] = json_decode($field['value'], true);
    }
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    
    <div id="rule_parameters_container" class="border p-3 bg-light rounded">
        <div id="rule_parameters_loading" class="d-none">
            <i class="la la-spinner la-spin"></i> Loading default parameters...
        </div>
        <div id="rule_parameters_empty" class="{{ empty($field['value']) ? '' : 'd-none' }}">
            <span class="text-muted fst-italic">Select a rule to see its parameters...</span>
        </div>
        <table id="rule_parameters_table" class="table table-sm table-bordered mb-2 {{ empty($field['value']) ? 'd-none' : '' }}">
            <thead class="thead-light">
                <tr>
                    <th>Parameter (Key)</th>
                    <th>Value</th>
                    <th style="width: 40px;"></th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($field['value']) && is_array($field['value']))
                    @foreach($field['value'] as $key => $val)
                        <tr>
                            <td>
                                <input type="text" class="form-control form-control-sm parameter-key" 
                                    value="{{ $key }}" placeholder="e.g. pass_mark">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm parameter-value" 
                                    data-key="{{ $key }}" 
                                    value="{{ is_array($val) ? json_encode($val) : $val }}">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-parameter-row">
                                    <i class="la la-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
        <button type="button" id="add_parameter_btn" class="btn btn-sm btn-outline-primary {{ empty($field['value']) && !isset($field['rule_id_selector']) ? '' : '' }}">
            <i class="la la-plus"></i> Add Parameter
        </button>
    </div>

    {{-- Hidden field to store JSON --}}
    <input type="hidden" 
        name="{{ $field['name'] }}" 
        id="rule_parameters_hidden" 
        value="{{ is_array($field['value']) ? json_encode($field['value']) : $field['value'] }}"
        @include('crud::fields.inc.attributes')
    >

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ruleIdSelect = document.querySelector('select[name="rule_id"]');
        const container = document.getElementById('rule_parameters_container');
        const tableBody = document.querySelector('#rule_parameters_table tbody');
        const table = document.getElementById('rule_parameters_table');
        const emptyMsg = document.getElementById('rule_parameters_empty');
        const loadingMsg = document.getElementById('rule_parameters_loading');
        const hiddenInput = document.getElementById('rule_parameters_hidden');

        const ruleClassSelect = document.querySelector('select[name="rule_class_path"]');
        const addParamBtn = document.getElementById('add_parameter_btn');
        const trashHtml = '<i class="la la-trash"></i>';

        const loadParameters = function(data) {
            loadingMsg.classList.remove('d-none');
            emptyMsg.classList.add('d-none');
            table.classList.add('d-none');

            $.ajax({
                url: '{{ backpack_url('rule-pipeline/get-rule-parameters') }}',
                data: data,
                success: function(response) {
                    loadingMsg.classList.add('d-none');
                    if (response.success && response.parameters && Object.keys(response.parameters).length > 0) {
                        let html = '';
                        for (const key in response.parameters) {
                            const val = response.parameters[key];
                            html += `
                                <tr>
                                    <td>
                                        <input type="text" class="form-control form-control-sm parameter-key" value="${key}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm parameter-value" 
                                            value="${typeof val === 'object' ? JSON.stringify(val) : val}">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-parameter-row">
                                            ${trashHtml}
                                        </button>
                                    </td>
                                </tr>
                            `;
                        }
                        tableBody.innerHTML = html;
                        table.classList.remove('d-none');
                        updateHiddenInput();
                    } else {
                        emptyMsg.classList.remove('d-none');
                        emptyMsg.innerHTML = '<span class="text-muted fst-italic">No configurable parameters found for this selection.</span>';
                        tableBody.innerHTML = '';
                        table.classList.add('d-none');
                        hiddenInput.value = '{}';
                    }
                },
                error: function() {
                    loadingMsg.classList.add('d-none');
                    emptyMsg.classList.remove('d-none');
                    emptyMsg.innerHTML = '<span class="text-danger">Error loading parameters.</span>';
                }
            });
        };

        // Handle rule selection change
        if (ruleIdSelect) {
            $(ruleIdSelect).on('change', function() {
                const val = $(this).val();
                if (val) loadParameters({ rule_id: val });
                else {
                    table.classList.add('d-none');
                    emptyMsg.classList.remove('d-none');
                }
            });
        }

        // Handle class selection change
        if (ruleClassSelect) {
            $(ruleClassSelect).on('change', function() {
                const val = $(this).val();
                if (val) loadParameters({ rule_class_path: val });
                else {
                    table.classList.add('d-none');
                    emptyMsg.classList.remove('d-none');
                }
            });
        }

        // Add Parameter button logic
        addParamBtn.addEventListener('click', function() {
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <input type="text" class="form-control form-control-sm parameter-key" value="" placeholder="key">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm parameter-value" value="" placeholder="value">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-parameter-row">
                        ${trashHtml}
                    </button>
                </td>
            `;
            tableBody.appendChild(newRow);
            table.classList.remove('d-none');
            emptyMsg.classList.add('d-none');
        });

        // Remove Parameter logic
        $(document).on('click', '.remove-parameter-row', function() {
            $(this).closest('tr').remove();
            updateHiddenInput();
            
            if (tableBody.children.length === 0) {
                table.classList.add('d-none');
                emptyMsg.classList.remove('d-none');
                emptyMsg.innerHTML = '<span class="text-muted fst-italic">No parameters defined.</span>';
            }
        });

        // Update hidden JSON input when parameters change
        $(document).on('input', '.parameter-key, .parameter-value', function() {
            updateHiddenInput();
        });

        function updateHiddenInput() {
            const params = {};
            tableBody.querySelectorAll('tr').forEach(row => {
                const keyInput = row.querySelector('.parameter-key');
                const valInput = row.querySelector('.parameter-value');
                
                if (keyInput && valInput) {
                    let key = keyInput.value.trim();
                    let value = valInput.value;
                    
                    if (key) {
                        // Try to parse if it looks like JSON array/object
                        if (value && (value.startsWith('[') || value.startsWith('{'))) {
                            try {
                                value = JSON.parse(value);
                            } catch(e) {}
                        }
                        params[key] = value;
                    }
                }
            });
            hiddenInput.value = JSON.stringify(params);
        }

        // Logic for "Assign To" dynamic fields (Programme/Course)
        const assignToSelect = document.querySelector('select[name="ruleable_type"]');
        
        function toggleRuleableFields() {
            if (!assignToSelect) return;
            
            const val = $(assignToSelect).val();
            const progField = document.querySelector('[name="programme_select"]');
            const courseField = document.querySelector('[name="course_select"]');
            
            const progWrapper = progField ? progField.closest('.form-group') : null;
            const courseWrapper = courseField ? courseField.closest('.form-group') : null;

            if (val === 'App\\Models\\Programme') {
                if (progWrapper) $(progWrapper).show();
                if (courseWrapper) {
                    $(courseWrapper).hide();
                    $(courseWrapper).find('select').val(null).trigger('change');
                }
            } else if (val === 'App\\Models\\Course') {
                if (progWrapper) {
                    $(progWrapper).hide();
                    $(progWrapper).find('select').val(null).trigger('change');
                }
                if (courseWrapper) $(courseWrapper).show();
            } else {
                if (progWrapper) $(progWrapper).hide();
                if (courseWrapper) $(courseWrapper).hide();
            }
        }

        if (assignToSelect) {
            $(assignToSelect).on('change', toggleRuleableFields);
            // Run after a short delay to ensure select2 and other fields are ready
            setTimeout(toggleRuleableFields, 100);
        }
    });
</script>
@endpush
