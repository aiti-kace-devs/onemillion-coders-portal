@php
    $column['value'] = $column['value'] ?? data_get($entry, $column['name']);
    $column['escaped'] = $column['escaped'] ?? false;
    $column['limit'] = 999999999;

    if($column['value'] instanceof \Closure) {
        $column['value'] = $column['value']($entry);
    }

    if(!empty($column['value'])) {
        $column['value'] = '<i class="'.$column['value'].'"></i>';
    }
@endphp

@include('crud::columns.text')

@push('after_styles')
@switch ($column['iconset'])
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
    @default
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
        @break
@endswitch
@endpush