<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets') }}/images/logo-short.png">
    <link rel="icon" type="image/png" href="{{ asset('assets') }}/images/logo-short.png">
    <meta property="csp-nonce" content="{{ csp_nonce() }}">
    <script nonce="{{ csp_nonce() }}">
        if (window.self !== window.top) {
            window.top.postMessage({ type: 'LARAVEL_IFRAME_DETECTED' }, '*');
        }
    </script>
    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300" />

    <!-- CSS -->
    {{-- <link rel="stylesheet" type="text/css" href="/DataTables-1.13.8/css/jquery.dataTables.css"> --}}
    <link rel="stylesheet" href="{{ url('/assets/plugins/toastr/toastr.min.css') }}">
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
    
    @if($cssFiles)
        @foreach($cssFiles as $css)
            <link rel="stylesheet" href="{{ asset('build/' . $css) }}">
        @endforeach
    @endif
    
    @if($jsFile)
        <script type="module" src="{{ asset('build/' . $jsFile) }}"></script>
    @endif
    
    @routes(nonce: csp_nonce())
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

    <script @nonce src="{{ asset('assets') }}/js/core/jquery.min.js"></script>
    <script @nonce src="{{ asset('assets') }}/js/core/popper.min.js"></script>
    <script @nonce src="{{ asset('assets') }}/js/core/bootstrap.min.js"></script>
    <script @nonce src="{{ asset('assets') }}/plugins/toastr/toastr.min.js"></script>
    <script @nonce type="text/javascript" src="/DataTables-1.13.8/js/jquery.dataTables.js"></script>
    <script @nonce src="{{ asset('assets/js/jquery.inputmask.bundle.min.js') }}"></script>
    <script @nonce src="{{ asset('assets/js/easy.qrcode.min.js') }}"></script>
</body>

</html>