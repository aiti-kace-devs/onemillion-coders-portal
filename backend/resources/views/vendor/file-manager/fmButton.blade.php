<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'File Manager') }}</title>

    <!-- Styles -->
    @basset('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css')
    @basset('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css')
    @basset('vendor/file-manager/css/file-manager.css')
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12" id="fm-main-block">
                <div id="fm"></div>
            </div>
        </div>
    </div>

    <!-- File manager -->
    @bassetBlock('custom/file-manager/override-ajax.js')
        <script>
            var originalOpen = XMLHttpRequest.prototype.open;
            const urlParams = new URLSearchParams(window.location.search);
            const disks = urlParams.get('disks');
            const fieldName = urlParams.get('field-name');


            // Override the open method
            XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
                // Call the original open method first
                originalOpen.apply(this, arguments);

                // Add your default headers
                this.setRequestHeader('X-Disks', disks);

                // Add more headers if needed
                // this.setRequestHeader('Another-Header', 'Another-Value');
            };
        </script>
    @endBassetBlock
    @basset('vendor/file-manager/js/file-manager.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var originalOpen = XMLHttpRequest.prototype.open;
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id');
            // set fm height
            document.getElementById('fm-main-block').setAttribute('style', 'height:' + window.innerHeight + 'px');

            // Add callback to file manager
            fm.$store.commit('fm/setFileCallBack', function(fileUrl) {
                window.opener.fmSetLink(fileUrl, id);
                window.close();
            });
        });
    </script>
</body>

</html>
