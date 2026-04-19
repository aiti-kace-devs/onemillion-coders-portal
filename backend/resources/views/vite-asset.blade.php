@if (file_exists(public_path('hot')))
    {{-- DEV MODE: Use the Vite server --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@elseif (file_exists(public_path('build/manifest.json')))
    {{-- PRODUCTION: Manual parse to avoid Nginx 502 header issues --}}
    @php
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $cssFiles = [];
        $jsFile = null;

        foreach ($manifest as $key => $value) {
            if (str_ends_with($key, 'app.js') && isset($value['file'])) {
                $jsFile = $value['file'];
            }
            if (isset($value['css'])) {
                foreach ($value['css'] as $css) {
                    $cssFiles[] = $css;
                }
            }
        }
    @endphp

    @if($jsFile)
        <script type="module" src="{{ asset('build/' . $jsFile) }}"></script>
    @endif

    @foreach($cssFiles as $css)
        <link rel="stylesheet" href="{{ asset('build/' . $css) }}">
    @endforeach
@else
    {{-- FALLBACK: Optional message or empty state --}}
    <!-- Vite manifest or hot file not found -->
@endif
