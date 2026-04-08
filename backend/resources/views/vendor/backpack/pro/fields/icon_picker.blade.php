{{-- icon picker input --}}
@php
    // if no iconset was provided, set the default iconset to Font-Awesome
    $field['iconset'] = $field['iconset'] ?? 'fontawesome';
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <div>
        <button type="button" class="btn btn-light iconpicker btn-sm" role="icon-selector"></button>
        <input
            type="hidden"
            name="{{ $field['name'] }}"
            data-iconset="{{ $field['iconset'] }}"
            bp-field-main-input
            data-init-function="bpFieldInitIconPickerElement"
            value="{{ old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '' }}"
            @include('crud::fields.inc.attributes')
        >
    </div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}

    {{-- FIELD EXTRA CSS --}}
    @push('crud_fields_styles')
        {{-- The chosen font - CSS loads fonts via @font-face --}}
        @switch ($field['iconset'])
            @case('ionicon')
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-iconpicker@1.8.2/icon-fonts/ionicons-1.5.2/css/ionicons.min.css" rel="stylesheet" crossorigin="anonymous">
                @break
            @case('weathericon')
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-iconpicker@1.8.2/icon-fonts/weather-icons-1.2.0/css/weather-icons.min.css" rel="stylesheet" crossorigin="anonymous">
                @break
            @case('mapicon')
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-iconpicker@1.8.2/icon-fonts/map-icons-2.1.0/css/map-icons.min.css" rel="stylesheet" crossorigin="anonymous">
                @break
            @case('octicon')
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-iconpicker@1.8.2/icon-fonts/octicons-2.1.2/css/octicons.min.css" rel="stylesheet" crossorigin="anonymous">
                @break
            @case('typicon')
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-iconpicker@1.8.2/icon-fonts/typicons-2.0.6/css/typicons.min.css" rel="stylesheet" crossorigin="anonymous">
                @break
            @case('elusiveicon')
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-iconpicker@1.8.2/icon-fonts/elusive-icons-2.0.0/css/elusive-icons.min.css" rel="stylesheet" crossorigin="anonymous">
                @break
            @case('meterialdesign')
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-iconpicker@1.8.2/icon-fonts/material-design-1.1.1/css/material-design-iconic-font.min.css" rel="stylesheet" crossorigin="anonymous">
                @break
        @endswitch
        {{-- Bootstrap-Iconpicker --}}
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-iconpicker-latest@1.12.0/dist/css/bootstrap-iconpicker.min.css" rel="stylesheet" crossorigin="anonymous">
        {{-- Font Awesome - CSS loads fonts via @font-face --}}
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" crossorigin="anonymous">

        <style>
            button.btn-icon-selected>i.empty, button.iconpicker>i.empty {
                padding: 0px;
            }
            .iconpicker {
                display: block;
            }
            /* the default z-index from icon picker package is 1050 */
            /* we increment it here so that it's higher than our modals */
            .iconpicker-popover {
                z-index: 1055 !important;
            }
        </style>
    @endpush

    {{-- FIELD EXTRA JS --}}
    @push('crud_fields_scripts')
        {{-- Bootstrap-Iconpicker --}}
        @basset(base_path('vendor/backpack/pro/resources/assets/js/icon-picker.js'))

        {{-- Bootstrap-Iconpicker - set hidden input value --}}
        @bassetBlock('backpack/pro/fields/icon-picker-field.js')
        <script>
            function bpFieldInitIconPickerElement(element) {
                var $iconset = element.attr('data-iconset');
                var $iconButton = element.siblings('button[role=icon-selector]');
                var $icon = element.attr('value');

                // we explicit init the iconpicker on the button element.
                // this way we can init the iconpicker in InlineCreate as in future provide aditional configurations.
                    $($iconButton).iconpicker({
                        iconset: $iconset,
                        icon: $icon
                    });

                    element.siblings('button[role=icon-selector]').on('change', function(e) {
                        $(this).siblings('input[type=hidden]').val(e.icon).trigger('change');
                    });

                    element.on('CrudField:enable', function(e) {
                        $iconButton.removeAttr('disabled');
                    });

                    element.on('CrudField:disable', function(e) {
                        $iconButton.attr('disabled', 'disabled');
                    });
            }
        </script>
        @endBassetBlock
    @endpush
