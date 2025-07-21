@extends(backpack_view('layouts.plain'))

@section('content')
<div class="container mt-5">
    <h4>Assign {{ count($entries) }} Students to Batch</h4>

    <form method="POST" action="{{ url('admin/user/assign-batch') }}">
        @csrf
        <input type="hidden" name="student_ids" value="{{ implode(',', $entries) }}">

        <div class="form-group">
            <label>Select Batch</label>
            <select name="batch_id" class="form-control" required>
                @foreach(\App\Models\Batch::orderBy('title')->get() as $batch)
                    <option value="{{ $batch->id }}">{{ $batch->title }} ({{ $batch->year }})</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success mt-3">Assign Batch</button>
    </form>
</div>
@endsection
