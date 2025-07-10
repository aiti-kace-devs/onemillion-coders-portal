@extends('layouts.student')
@section('title', 'Portal dashboard')
@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">My Details</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active">My Details</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Small boxes (Stat box) -->
                <video id="scanner"></video>

                <!-- /.row -->
                <!-- Main row -->

                <!-- /.row (main row) -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
@endsection


@push('scripts')
    <script src="{{ asset('assets/js/qr-scanner.min.js') }}"></script>
    <script @nonce>
        const qrScanner = new QrScanner(
            document.getElementById('scanner'),
            result => {
                console.log('decoded qr code:', result)
                if (result.data) {
                    Swal.fire({
                        text: 'Confirming Attendance. Please wait...',
                        icon: 'info',
                    })
                    qrScanner.stop();
                }
            }, {
                /* your options or returnDetailedScanResult: true if you're not specifying any other options */
                preferredCamera: 'environment',
                highlightScanRegion: true,
                highlightCodeOutline: true,
            },
        );

        qrScanner.start();
    </script>
@endpush
