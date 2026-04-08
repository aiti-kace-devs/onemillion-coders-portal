{{-- code mirror editor column --}}
@php
    $column['value'] = $column['value'] ?? data_get($entry, $column['name']);
    $column['escaped'] = $column['escaped'] ?? true;
    $column['limit'] = $column['limit'] ?? 40;
    $column['prefix'] = $column['prefix'] ?? '';
    $column['suffix'] = $column['suffix'] ?? '';
    $column['text'] = $column['default'] ?? '-';

    if($column['value'] instanceof \Closure) {
        $column['value'] = $column['value']($entry);
    }

    if(is_array($column['value'])) {
        $column['value'] = json_encode($column['value']);
    }

    if(!empty($column['value'])) {
        $column['text'] = $column['prefix'].Str::limit(strip_tags($column['value']), $column['limit'], '…').$column['suffix'];
    }
@endphp

<span>
    @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_start')
        @if(!empty($column['value']))
            <a href="javascript:void(0)" onclick="var el = this.nextElementSibling; el.style.display = el.style.display == 'none' ? 'block' : 'none'">
                {{ $column['text'] }} <i class="la la-eye"></i>
            </a>
            <div style="display: none; max-width: 400px; white-space: pre-wrap;">
                <code>{{ $column['value'] }}</code>
            </div>
        @else
            {{ $column['text'] }}
        @endif
    @includeWhen(!empty($column['wrapper']), 'crud::columns.inc.wrapper_end')
</span>
