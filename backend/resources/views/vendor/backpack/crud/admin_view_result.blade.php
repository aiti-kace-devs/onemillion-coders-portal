@extends(backpack_view('blank'))
@section('header')
    <section class="content-header">
        <h1>Result</h1>
        <ol class="breadcrumb">
            <li><a href="{{ backpack_url('dashboard') }}">Dashboard</a></li>
            <li class="active">Exam</li>
        </ol>
    </section>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="card mt-4">
            <div class="card-body">
                <h2>Student information</h2>
                <table class="table">
                    <tr>
                        <td>Name : </td>
                        <td>{{ $student_info->name}}</td>
                    </tr>
                    <tr>
                        <td>E-mail : </td>
                        <td>{{ $student_info->email}}</td>
                    </tr>
                    <tr>
                        <td>Exam name : </td>
                        <td>{{ $exam_info->title}}</td>
                    </tr>
                    <tr>
                        <td>Exam date : </td>
                        <td>{{ $exam_info->exam_date}}</td>
                    </tr>
                </table>
                <br>
                <h2>Exam Result</h2>
                <table class="table">
                    <tr>
                        <td>Number of correct answers : </td>
                        <td>{{ $result_info->yes_ans}}</td>
                    </tr>
                    <tr>
                        <td>Number of wrong answers : </td>
                        <td>{{ $result_info->no_ans}}</td>
                    </tr>
                    <tr>
                        <td>Total marks: </td>
                        <td>{{ $result_info->yes_ans}}/30</td>
                    </tr>
                    <tr>
                        <td>Percentage score :</td>
                        <td>{{ round(($result_info->yes_ans / 30) * 100) }}%</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
