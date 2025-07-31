@php
    // Dynamically get the count field (default: 'count')
    $countField = $column['count_field'] ?? 'count';
    $count = $entry->{$countField} ?? 0;

    // Get the route path and query param name
    $route = $column['route'] ?? null;
    if ($route && $entry->{$route} ?? false) {
        $route = $entry->{$route};
    }

    $queryParam = $column['query_param'] ?? 'id';

    $entityKey = $column['entity_key'] ?? 'id';
    $queryValue = $entry->{$entityKey} ?? null;

    $url = $route ? backpack_url($route) . "?{$queryParam}={$queryValue}" : '#';
@endphp

@if ($count > 0 && $route)
    <a href="{{ $url }}">{{ $count }}</a>
@else
    {{ $count }}
@endif
