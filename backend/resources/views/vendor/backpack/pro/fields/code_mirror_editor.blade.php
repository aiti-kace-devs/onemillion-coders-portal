{{-- CodeMirror - Code Editor Field --}}

@php
    $field['configuration'] = $field['configuration'] ?? [];
    $theme = $field['configuration']['theme'] ?? 'monokai';
    $mode = $field['configuration']['mode'] ?? 'javascript';

    $themes = [
        'monokai'   => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/theme/monokai.min.css',
        'dracula'   => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/theme/dracula.min.css',
        'material'  => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/theme/material.min.css',
        'eclipse'   => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/theme/eclipse.min.css',
        'idea'      => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/theme/idea.min.css',
    ];

    $modes = [
        'javascript' => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/javascript/javascript.min.js',
        'xml'        => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/xml/xml.min.js',
        'css'        => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/css/css.min.js',
        'htmlmixed'  => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/htmlmixed/htmlmixed.min.js',
        'php'        => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/php/php.min.js',
        'sql'        => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/sql/sql.min.js',
        'python'     => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/python/python.min.js',
    ];
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    <textarea
        name="{{ $field['name'] }}"
        data-init-function="bpFieldInitCodeMirrorElement"
        bp-field-main-input
        data-configuration="{{ isset($field['configuration']) ? json_encode($field['configuration']) : '{}' }}"
        @include('crud::fields.inc.attributes', ['default_class' => 'form-control'])
    >{{ old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '' }}</textarea>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.css" rel="stylesheet" crossorigin="anonymous">
        <link href="{{ $themes[$theme] }}" rel="stylesheet" crossorigin="anonymous">

        @bassetBlock('backpack/crud/fields/code_mirror_editor-field.css')
        <style type="text/css">
            .CodeMirror {
                height: auto;
                min-height: 300px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
        </style>
        @endBassetBlock
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.js" crossorigin="anonymous"></script>
        <script src="{{ $modes[$mode] }}" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/edit/closebrackets.min.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/addon/edit/matchbrackets.min.js" crossorigin="anonymous"></script>
        @bassetBlock('backpack/crud/fields/code_mirror_editor-field.js')
        <script>
            function bpFieldInitCodeMirrorElement(element) {
                if (element.attr('data-initialized') === 'true') {
                    return;
                }

                // Default configuration
                var defaultConfig = {
                    mode: 'javascript',
                    theme: 'monokai',
                    lineNumbers: true,
                    lineWrapping: false,
                    autoCloseBrackets: true,
                    matchBrackets: true,
                    indentUnit: 4,
                    tabSize: 4,
                    indentWithTabs: false,
                    readOnly: false
                };

                // Get user configuration
                var userConfig = {};
                try {
                    userConfig = JSON.parse(element.attr('data-configuration'));
                } catch (e) {
                    console.error('CodeMirror field: Invalid configuration JSON');
                }

                // Merge configurations
                var config = {...defaultConfig, ...userConfig};

                // Initialize CodeMirror
                var editor = CodeMirror.fromTextArea(element[0], config);

                // Set minimum height
                editor.setSize(null, config.height || '300px');

                // Update the original textarea on change
                editor.on('change', function() {
                    element.val(editor.getValue()).trigger('change');
                });

                // Handle CRUD field events
                element.on('CrudField:disable', function(e) {
                    editor.setOption('readOnly', true);
                });

                element.on('CrudField:enable', function(e) {
                    editor.setOption('readOnly', false);
                });

                // Refresh editor when shown in tabs
                $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                    setTimeout(function() { editor.refresh(); }, 10);
                });

                // Mark as initialized
                element.attr('data-initialized', 'true');
            }
        </script>
        @endBassetBlock
    @endpush

{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
