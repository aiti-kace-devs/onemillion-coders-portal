@extends(backpack_view('blank'))
@section('header')
    <section class="content-header">
        <h1 class="text-center">Result</h1>
    </section>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card mt-4 mx-auto">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Result</span>
                {{-- Back to student preview --}}
                <a href="{{ url(config('backpack.base.route_prefix').'/manage-student/'.$student_info->id.'/show') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="la la-arrow-left"></i> Back to Preview
                </a>
            </div>
            <div class="card-body">
                <h2 class="text-center">Student information</h2>
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
