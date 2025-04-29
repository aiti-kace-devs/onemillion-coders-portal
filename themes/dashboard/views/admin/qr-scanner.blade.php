@extends('layouts.app')
@section('title', 'Scan QR Code')
@section('content')
    <style @nonce>
        canvas {
            height: 400px;
            width: 400px;
            margin: 0 auto 0 auto;
        }

        #qrcode {
            height: 85vh
        }

        #qr-overlay canvas {
            border: 2px solid white;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }

        #qr-overlay {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Scan/Generate QR Code</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active">Scan QR Code</li>
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
                <form name="qr-form">
                    <div class="row">
                        <x-course-selector :groupedCourses="$groupedCourses"></x-course-selector>
                        <div class="mb-4 col-md-2">
                            <label for="validity" class="form-label">Validity In Mins</label>
                            <input class="form-control" type="number" name="validity" id="" max="120"
                                value="30">
                        </div>
                        <div class="mb-4 col-md-2">
                            <label for="online" class="form-label">Online</label>
                            <select name="online" class="form-control">
                                <option value="false">No</option>
                                <option value="true">Yes</option>
                                <option value="onlineForAll">Online For All</option>

                            </select>
                        </div>
                        <div class="mb-4 col-md-4">
                            <label for="date" class="form-label">Select Date</label>
                            @can('attendance.update')
                                <input class="form-control" type="date" name="date" id=""
                                    max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}">
                            @else
                                <input class="form-control" type="date" name="date" id="dateInput"
                                    min="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"
                                    value="{{ now()->toDateString() }}">
                                {{-- <small class="text-muted">You can only take attendance for today</small> --}}
                            @endcan
                        </div>
                </form>
            </div>
            <div class="row g-3 d-flex justify-content-center align-tems-center mb-4">
                <button type="button" class="btn btn-primary col-auto" id="startScanner">Start
                    QR Code Scanner</button>
                <button type="button" class="btn btn-danger ml-4" id="stopScanner">Stop QR Code Scanner</button>
                @can('attendance.status')
                    <button type="button" class="btn btn-success ml-4" id="generateCode">Generate QR Code</button>
                    <button type="button" class="btn btn-danger ml-4" id="stopCodeGeneration">Stop QR Code
                        Generation</button>
                @endcan

            </div>

            <div class="row g-3 flex justify-content-center align-tems-center">
                <div class="d-flex flex-column" id="qrcode"></div>
                <video class="col-12" id="scanner"></video>
            </div>


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
    <script src="{{ asset('assets/js/easy.qrcode.min.js') }}"></script>
    <script @nonce>
        let codeIinterval = null;

        let qrCode = null;

        const scannerElem = $('#scanner');
        const qrcodeElem = $('#qrcode');

        const startScannerBtn = $('#startScanner');
        const stopScannerBtn = $('#stopScanner');

        const generateCodeBtn = $('#generateCode');
        const stopCodeGenerationBtn = $('#stopCodeGeneration');

        startScannerBtn.on('click', function() {
            startScanner();
        });
        stopScannerBtn.on('click', function() {
            stopScanner();
        });
        generateCodeBtn.on('click', function() {
            generateCode();
        });
        stopCodeGenerationBtn.on('click', function() {
            stopCodeGeneration();
        });


        $(document).ready(function() {
            $('#dateInput').on('keydown', function(e) {
                e.preventDefault();
                return false;
            }).on('change', function() {
                if (this.value !== new Date().toISOString().split('T')[0]) {
                    this.value = new Date().toISOString().split('T')[0];
                    alert('You can only select today\'s date');
                }
            });
        });

        function getFormValues() {
            // const values = $('').serializeArray();
            const values = {};
            let error = false;
            $.each($('[name="qr-form"]').serializeArray(), function(i, field) {
                values[field.name] = field.value;
                if (field.value === '') {
                    error = true;
                }
            });

            if (error) {
                Swal.fire({
                    text: "You need to select all options",
                    timer: 4000,
                    toast: true,
                    icon: 'error',
                    showConfirmButton: false,
                    position: 'top-center'
                });
                return null;
            }
            return values;
        }

        const QREngine = QrScanner.createQrEngine(QrScanner.WORKER_PATH);
        const qrScanner = new QrScanner(
            document.getElementById('scanner'),
            async result => {
                qrScanner.stop();
                const values = getFormValues();

                if (result.data && result.data.length > 5) {
                    Swal.fire({
                        text: 'Confirming Attendance. Please wait...',
                        icon: 'info',
                        toast: true,
                        timer: 3000
                    })
                    try {
                        const url = `/admin/confirm_attendance`;
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                course_id: values['course_id'],
                                date: values['date'],
                                user_id: result.data
                            })
                        });
                        if (response.ok) {
                            const result = await response.json();
                            Swal.fire({
                                text: result.message,
                                icon: result.success ? 'info' : 'error',
                                toast: true,
                                preConfirm: () => {
                                    qrScanner.start();
                                }
                            })
                        }
                    } catch (error) {
                        Swal.fire({
                            text: 'Unable to confirm.',
                            icon: 'error',
                            toast: true,
                            timer: 5000
                        });
                        qrScanner.start();
                    }
                } else {
                    qrScanner.start();
                }
            }, {
                /* your options or returnDetailedScanResult: true if you're not specifying any other options */
                preferredCamera: 'environment',
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 1,
                qrEngine: QREngine,
                alsoTryWithoutScanRegion: true
            },
        );

        function startScanner() {
            qrcodeElem.hide();
            scannerElem.show();
            this.stopCodeGeneration();

            const values = getFormValues();
            if (values !== null) {
                qrScanner.start();
            }

        }

        function stopScanner() {
            try {
                scannerElem.hide();
                qrScanner.stop();
            } catch (e) {}
        }

        async function generateCode() {
            this.stopScanner();
            stopCodeGeneration()
            const values = getFormValues();
            qrcodeElem.show();
            qrcodeElem.html('');
            scannerElem.hide();

            if (values == null) {
                return;
            }
            // interval = setInterval(generateCode, 1000 * 60 * 10);
            const data = await getQRCodeData(values);

            if (data) {
                new QRCode(document.getElementById("qrcode"), {
                    text: data['url'],
                    width: 400,
                    height: 400,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H,
                    quietZone: 20,
                    logo: "{{ asset('assets/images/logo-bt.png') }}",
                    logoWidth: 170,
                    logoHeight: 80,
                });
            }
            qrcodeElem.prepend(
                `<h3>This Code Expires In <span  id="timer" class="js-timeout">${values['validity']}: 00</span>. A new code will re-generate automatically </h3>
                <br>
                <div class="row g-3 d-flex justify-content-center align-tems-center mb-4">
                    <button onclick="copyToClipBoard(this)" class="btn btn-info" data-link="${data['url']}">Click to copy link</button>
                    <button type="button" class="btn btn-info ml-4" id="maximizeQR">Maximize QR Code</button>
                </div>`)
            codeIinterval = setInterval(generateCode, 1000 * 60 * values['validity']);
            countdown();
        }

        function stopCodeGeneration() {
            try {
                clearInterval(codeIinterval);
                qrcodeElem.html('');
                qrcodeElem.hide();
                clearInterval(interval);
                qrCode?.clear();
            } catch (e) {}
        }


        async function getQRCodeData(values) {
            try {
                const url = `/admin/generate_qrcode`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        course_id: values['course_id'],
                        date: values['date'],
                        online: values['online'],
                        validity: values['validity'],
                    })
                });
                if (response.ok) {
                    const token = await response.json();
                    return token;
                }
                return null;
            } catch (error) {
                console.log(error);
                return null;
            }
        }

        let interval;

        function countdown() {
            clearInterval(interval);
            interval = setInterval(function() {
                var timer = $('.js-timeout').html();
                timer = timer.split(':');
                var minutes = timer[0];
                var seconds = timer[1];
                seconds -= 1;
                if (minutes < 0) return;
                else if (seconds < 0 && minutes != 0) {
                    minutes -= 1;
                    seconds = 59;
                } else if (seconds < 10 && length.seconds != 2) seconds = '0' + seconds;

                $('.js-timeout').html(minutes + ':' + seconds);
                if (minutes < 11) {
                    $('.js-timeout').css('color', 'red');
                }
            }, 1000);
        }

        function copyToClipBoard(elem) {
            const link = $(elem).attr('data-link');
            navigator.clipboard.writeText(link);
            Swal.fire({
                text: "Copied to clipboard",
                timer: 2000,
                toast: true,
                icon: 'info',
                showConfirmButton: false,
                position: 'top-end'
            });
        }

        function maximizeQRCode() {
            const qrContainer = document.getElementById('qrcode');
            const qrCanvas = qrContainer.querySelector('canvas');

            if (!qrCanvas) {
                Swal.fire({
                    text: "Please generate a QR code first",
                    icon: 'error',
                    timer: 2000,
                    toast: true,
                    position: 'top'
                });
                return;
            }

            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.backgroundColor = 'rgba(0,0,0,0.98)';
            overlay.style.zIndex = '9999';
            overlay.style.display = 'flex';
            overlay.style.flexDirection = 'column';
            overlay.style.justifyContent = 'center';
            overlay.style.alignItems = 'center';
            overlay.style.cursor = 'pointer';
            overlay.id = 'qr-overlay';

            const qrWrapper = document.createElement('div');
            qrWrapper.style.display = 'flex';
            qrWrapper.style.flexDirection = 'column';
            qrWrapper.style.alignItems = 'center';
            qrWrapper.style.justifyContent = 'center';
            qrWrapper.style.width = '100%';
            qrWrapper.style.height = '100%';


            const newCanvas = document.createElement('canvas');
            const scaleFactor = 2;
            newCanvas.width = qrCanvas.width * scaleFactor;
            newCanvas.height = qrCanvas.height * scaleFactor;

            const context = newCanvas.getContext('2d');
            context.imageSmoothingEnabled = false;
            context.drawImage(qrCanvas, 0, 0, newCanvas.width, newCanvas.height);

            newCanvas.style.maxWidth = '95vw';
            newCanvas.style.maxHeight = '95vh';
            newCanvas.style.width = 'auto';
            newCanvas.style.height = 'auto';
            newCanvas.style.border = '4px solid white';
            newCanvas.style.boxShadow = '0 0 30px rgba(255,255,255,0.7)';

            const closeBtn = document.createElement('button');
            closeBtn.textContent = '✕ Close';
            closeBtn.style.position = 'fixed';
            closeBtn.style.top = '25px';
            closeBtn.style.right = '25px';
            closeBtn.style.padding = '12px 24px';
            closeBtn.style.background = '#dc3545';
            closeBtn.style.color = 'white';
            closeBtn.style.border = 'none';
            closeBtn.style.borderRadius = '6px';
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.zIndex = '10000';
            closeBtn.style.fontSize = '18px';
            closeBtn.style.fontWeight = 'bold';

            qrWrapper.appendChild(newCanvas);
            overlay.appendChild(qrWrapper);
            overlay.appendChild(closeBtn);
            document.body.appendChild(overlay);

            closeBtn.onclick = () => document.body.removeChild(overlay);
            overlay.onclick = (e) => {
                if (e.target === overlay) {
                    document.body.removeChild(overlay);
                }
            };

            const handleKeyDown = (e) => {
                if (e.key === 'Escape') {
                    document.body.removeChild(overlay);
                    document.removeEventListener('keydown', handleKeyDown);
                }
            };
            document.addEventListener('keydown', handleKeyDown);
        }
        $(document).on('click', '#maximizeQR', maximizeQRCode);
    </script>
@endpush
