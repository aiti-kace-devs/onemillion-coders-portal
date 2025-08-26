@extends(backpack_view('blank'))

@section('after_scripts')
    @include('vendor.elfinder.common_scripts')
    @include('vendor.elfinder.common_styles')
    <style>
        #elfinder {
            height: 75vh !important;
        }
    </style>
    <!-- elFinder initialization (REQUIRED) -->
    <script type="text/javascript" charset="utf-8">
        // Documentation for client options:
        // https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
        $(document).ready(function() {
            try {
                var myCommands = Object.keys(elFinder.prototype.commands);
                var disabled = ['extract', 'archive'];
                $.each(disabled, function(i, cmd) {
                    (idx = $.inArray(cmd, myCommands)) !== -1 && myCommands.splice(idx, 1);
                });
                var elf = $('#elfinder').elfinder({
                    // set your elFinder options here
                    @if ($locale)
                        lang: '{{ $locale }}', // locale
                    @endif
                    customData: {
                        _token: '{{ csrf_token() }}'
                    },
                    url: '{{ route('elfinder.connector') }}', // connector URL
                    // soundPath: '{{ Basset::getUrl(base_path('vendor/studio-42/elfinder/sounds')) }}',
                    // cssAutoLoad: false,
                    sync: 10000,
                    debug: true,
                    // autoLoad: false,
                    // autoConnect: false,
                    // syncMinMs: false,
                    // syncChkAsTs: false,
                    // syncChkAs2: false,
                    // checkUpdate: false,
                    // autoReload: false,
                    // reload: false,
                    commands: myCommands,
                });

                // Override the sync method to prevent automatic calls
                if (elf && elf.sync) {
                    var originalSync = elf.sync;
                    elf.sync = function() {
                        console.log('Sync call intercepted and prevented');
                        return false;
                    };
                }
            } catch (error) {
                console.error('elFinder initialization error:', error);
            }
        });
    </script>
@endsection

@php
    $breadcrumbs = [
        trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
        trans('backpack::crud.file_manager') => false,
    ];
@endphp

@section('header')
    <section class="container-fluid" bp-section="page-header">
        <h1 bp-section="page-heading">{{ trans('backpack::crud.file_manager') }}</h1>
    </section>
@endsection

@section('content')
    <!-- Element where elFinder will be created (REQUIRED) -->
    <div id="elfinder"></div>
@endsection
