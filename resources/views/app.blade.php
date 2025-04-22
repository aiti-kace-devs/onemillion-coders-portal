<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets') }}/images/logo.png">
    <link rel="icon" type="image/png" href="{{ asset('assets') }}/images/logo.png">
    @cspMetaTag(\App\Helpers\BasePolicy::class)
    <meta property="csp-nonce" content="{{ csp_nonce() }}">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="/DataTables-1.13.8/css/jquery.dataTables.css">
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"
        integrity="sha512-vKMx8UnXk60zUwyUnUPM3HbQo8QfmNx7+ltw8Pm5zLusl1XIfwcxo8DbWCqMGKaWeNxWA8yrx5v3SaVpMvR3CA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" /> --}}
    <link rel="stylesheet" href="{{ url('/assets/plugin/toastr/toastr.min.css') }}">


    <!-- Scripts -->
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"
        integrity="sha512-aVKKRRi/Q/YV+4mjoKBsE4x3H+BkegoM/em46NNlCqNTmUYADjBbeNefNxYV7giUp0VxICtqdrbqU7iVaeZNXA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script> --}}
    <script src="{{ url('/assets/plugin/jquery/jquery.min.js') }}" referrerpolicy="no-referrer"></script>
    @routes(nonce: csp_nonce())
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

    <script @nonce src="{{ asset('assets') }}/js/core/jquery.min.js"></script>
    <script @nonce src="{{ asset('assets') }}/js/core/popper.min.js"></script>
    <script @nonce src="{{ asset('assets') }}/js/core/bootstrap.min.js"></script>
    <script @nonce src="{{ asset('assets') }}/plugins/toastr/toastr.min.js"></script>
    <script @nonce type="text/javascript" src="/DataTables-1.13.8/js/jquery.dataTables.js"></script>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"
        integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script> --}}
</body>

</html>
