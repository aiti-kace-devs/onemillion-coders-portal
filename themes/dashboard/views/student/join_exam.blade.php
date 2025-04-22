@extends('layouts.student')
@section('title', 'Exams')
@section('content')
    <style @nonce type="text/css">
        .question_options>li {
            list-style: none;
            height: auto;
            line-height: auto;
        }

        #examination_form {
            height: calc(85vh);
            overflow-y: scroll;
            scroll-behavior: smooth;
            scrollbar-width: 1px;
            overflow-x: hidden;
            width: 99% !important;
            font-size: 20px;
            display: none;
        }

        .exam-info-card {
            display: flex;
            flex-direction: row
        }

        @media screen and (max-width: 600px) {

            #examination_form p {
                font-size: 16px !important;
            }

            #examination_form ul>p {
                text-align: left;
                font-size: 1rem;
            }

            .exam-info-card>div>h3 {
                font-size: 1rem !important;
                text-align: center;
            }

            .exam-info-card>div>button {
                line-height: 1 !important;
                font-size: 1rem !important;

            }

            .exam-info-card>div:first-child {
                display: none !important;

            }


            .exam-info-card {
                grid-column: column;
            }

            .questions-container {
                padding: 0.5rem !important;
            }

            .questions-wrapper {
                padding: 0.5rem !important
            }

            .question_options li {
                font-size: 1rem
            }

            .question_options {
                padding: 8px;
            }
        }

        input[type="radio"] {
            height: 30px;
        }

        div:where(.swal2-container) div:where(.swal2-html-container) {
            padding: 1rem 1rem .3em;
        }

        .questions-container {
            padding: 1.5rem;
        }

        .question-body {}
    </style>
    <!-- /.content-header -->
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Exams</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Exam</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <div class="card">

                                <div class="card-body">
                                    <div class="exam-info-card">
                                        <div class="col-sm-3">
                                            <h3 class="text-center">Time : {{ $exam->exam_duration }} min</h3>
                                        </div>
                                        <div class="col-sm-3">
                                            <h3><b>Time Left</b> : <span
                                                    id="timer">{{ $exam['exam_duration'] - $usedTime }}:00</span>
                                            </h3>
                                        </div>
                                        <div class="col-sm-3">
                                            <button class="btn btn-primary btn-lg" onclick="handleSubmitTest()">
                                                SUBMIT TEST
                                            </button>
                                        </div>
                                        <div class="col-sm-3">
                                            <h3 class="text-right text-success"><b>Status</b> :Running</h3>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                            <div class="card mt-4 w-100">

                                <div class="card-body questions-wrapper">

                                    <form action="{{ url('student/submit_questions') }}" method="POST"
                                        name="examination_form" id="examination_form">
                                        <input type="hidden" name="exam_id" value="{{ Request::segment(3) }}">
                                        {{ csrf_field() }}
                                        <div class="row">
                                            <div class="card mt-4 col-12 questions-container">

                                                <div class="card-body question-body">
                                                    @foreach ($question as $key => $q)
                                                        <div class="col-sm-12 mt-4 border border-dark">
                                                            <p>{{ $key + 1 }}. {{ $q->questions }}</p>
                                                            <?php
                                                            $options = json_decode(json_decode(json_encode($q->options)), true);
                                                            ?>
                                                            <input type="hidden" name="question{{ $key + 1 }}"
                                                                value="{{ $q['id'] }}">
                                                            <ul class="question_options">
                                                                <li><input type="radio" value="{{ $options['option1'] }}"
                                                                        name="ans{{ $key + 1 }}">
                                                                    {{ $options['option1'] }}
                                                                </li>
                                                                <li><input type="radio" value="{{ $options['option2'] }}"
                                                                        name="ans{{ $key + 1 }}">
                                                                    {{ $options['option2'] }}
                                                                </li>
                                                                <li><input type="radio" value="{{ $options['option3'] }}"
                                                                        name="ans{{ $key + 1 }}">
                                                                    {{ $options['option3'] }}
                                                                </li>
                                                                <li><input type="radio" value="{{ $options['option4'] }}"
                                                                        name="ans{{ $key + 1 }}">
                                                                    {{ $options['option4'] }}
                                                                </li>

                                                                <li class="none"><input value="null" type="radio"
                                                                        checked="checked" name="ans{{ $key + 1 }}">
                                                                    {{ $options['option4'] }}</li>
                                                            </ul>
                                                        </div>
                                                    @endforeach

                                                </div>
                                            </div>

                                            <div class="col-sm-12 mb-4">
                                                <input type="hidden" name="index" value="{{ $key + 1 }}">
                                                <button type="submit" class="btn btn-primary btn-lg" id="myCheck">SUBMIT
                                                    TEST</button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                                <!-- /.card-body -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- /.content-header -->

        <!-- Modal -->
    @endsection

    @push('scripts')
        <script @nonce>
            var warn = 0;
            var timeLeft = 10;
            let timeUp = false;


            const startTest = () => {
                document.addEventListener('visibilitychange', handleVisibilityChange)
                document.addEventListener('fullscreenchange', handleFullscreenChange)
                const timer = document.getElementById('timer');
                $(timer).addClass('js-timeout');
                countdown();
                openFullscreen();
            }



            const showWarning = (e) => {
                const form = $('[name="examination_form"]');
                if (warn === 3) {
                    Swal.fire({
                        title: 'Violation!',
                        text: `Test submitted due to repeated violations`,
                        icon: 'error',
                        backdrop: `rgba(0,0,0,0.95)`,
                        confirmButtonText: 'Okay',
                        allowOutsideClick: false,
                        position: 'center',
                        timer: 5000,
                        target: document.querySelector('div.content-wrapper > div > section.content')
                    });
                    setTimeout(() => {
                        timeLeft = 0;
                        form.submit();
                    }, 3000);
                } else {

                    Swal.fire({
                        title: 'Violation!',
                        text: `You are in violation of exam rules. Please DO NOT exit fullscreen or changed tabs.
                        Your test may be automatically submitted if you keep on violating the rules. Warning Count: ${warn}`,
                        icon: 'error',
                        backdrop: `rgba(0,0,0,0.95)`,
                        confirmButtonText: 'Okay',
                        preConfirm: () => {
                            openFullscreen();
                        },
                        allowOutsideClick: false,
                        target: document.querySelector('div.content-wrapper > div > section.content')
                    })
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }

            const openFullscreen = () => {

                // const elem = document.documentElement;

                const elem = document.querySelector('div.content-wrapper > div > section.content');

                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) {
                    /* Safari */
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) {
                    /* IE11 */
                    elem.msRequestFullscreen();
                }
            }

            // document
            const handleVisibilityChange = async (e) => {
                if (document.hidden) {
                    warn++;
                    showWarning(e);
                }
            }

            const handleFullscreenChange = async (e) => {
                //    await checkWarningCount()
                if (!document.fullscreenElement) {
                    warn++;
                    showWarning(e);
                }
            }


            const handleSubmitTest = () => {
                const submitButton = document.getElementById('myCheck');
                submitButton.click();
            }

            let modalWidth = '100%';
            let fontSize = '1rem'

            if (window.innerWidth > 600) {
                modalWidth = '70%'; // large screens
                fontSize = '1.7rem';
            }

            Swal.fire({
                title: '{{ $exam->title }}',
                html: `<ol class = "text-left" style = "font-size:${fontSize};color:red" >
                    <li>Make sure you answer all questions</li>
                    <li>DO NOT exit fullscreen</li>
                    <li>DO NOT switch tabs</li>
                    <li>You will be warned when you violate these rules</li>
                    <li>Your test may be automatically SUBMITTED if you keep on violating rules</li>
                    <li>The duration of the test is {{ $exam->exam_duration }} mins from the time you click on 'START'</li>
                    <li>Ensure you have good and stable internet</li>
                    <li>Click on 'START' work to begin the test</li>
                    <li>Click on 'Submit Test' after you have completed</li>
                </ol>
                <h3>Good luck</h3>
                `,
                icon: 'info',
                confirmButtonText: 'START',
                backdrop: `rgba(0,0,0,0.99)`,
                width: modalWidth,
                allowOutsideClick: false,
                preConfirm: async () => {
                    try {
                        const url = `/student/start-exam/{{ $exam->id }}`;
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            body: JSON.stringify({
                                exam_id: '{{ $exam->id }}'
                            })
                        });
                        if (!response.ok) {
                            return Swal.showValidationMessage(`
                            ${JSON.stringify(await response.json())}
                            `);
                        }
                        $('#examination_form').show();
                        startTest();
                        Swal.close();

                    } catch (error) {
                        Swal.showValidationMessage(`
                            Request failed: ${error}
                        `);
                    }
                },
            });


            const form = document.getElementById('examination_form');
            form.addEventListener('submit', function(e) {

                // if time is up, just submit
                if (timeLeft == 0 || timeUp) {
                    form.submit();
                }

                e.preventDefault();

                const values = $('#examination_form').serializeArray();

                const questionCount = values.filter(d => d.name.startsWith('question')).length;
                const answerCount = values.filter(d => d.name.startsWith('ans') && d.value !== 'null').length;
                let remainder = questionCount - answerCount;

                Swal.fire({
                    title: 'Confirm Submission',
                    text: `Are you sure you want to submit this test. This cannot be undone. ${ remainder != 0 ? `${remainder} question(s) left to answer`: '' }`,
                    icon: 'info',
                    backdrop: `rgba(0,0,0,0.95)`,
                    confirmButtonText: 'Yes, Submit',
                    cancelButtonText: 'No, Review Work',
                    showCancelButton: true,
                    allowOutsideClick: false,
                    position: 'center',
                    target: document.querySelector('div.content-wrapper > div > section.content'),
                    preConfirm: () => {
                        Swal.fire({
                            title: 'Submitting Test....',
                            icon: 'info',
                            backdrop: `rgba(0,0,0,0.95)`,
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            position: 'center',
                            timer: 3000,
                            target: document.querySelector(
                                'div.content-wrapper > div > section.content'),
                        });
                        timeLeft = 0;
                        form.submit();
                    },
                });

            });


            var interval;

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

                    if (minutes == 0 && seconds == 0) {
                        clearInterval(interval);
                        const elem = document.querySelector('div.content-wrapper > div > section.content');
                        timeUp = true;
                        sAlert('TIME UP', {
                            target: elem,
                            title: 'END OF TEST',
                            icon: 'warning'
                        });
                        timeLeft = 0;
                        handleSubmitTest();
                    }
                }, 1000);
            }
        </script>
    @endpush
