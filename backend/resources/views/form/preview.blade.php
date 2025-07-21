@extends('layouts.app') <!-- You can use a public layout -->

@section('content')
<div class="container py-5">
    <h1 class="mb-3">{{ $form->title }}</h1>
    <p>{{ $form->description }}</p>

    <form>
        @foreach ($schema as $field)
            <div class="mb-4">
                <label class="form-label">{{ $field['title'] }} @if(optional($field['validators'])['required']) * @endif</label>

                @php $field_name = $field['field_name']; @endphp

                @if($field['type'] == 'text' || $field['type'] == 'email' || $field['type'] == 'phonenumber')
                    <input type="{{ $field['type'] == 'phonenumber' ? 'tel' : $field['type'] }}" class="form-control" name="{{ $field_name }}" placeholder="{{ $field['title'] }}">
                @elseif($field['type'] == 'textarea')
                    <textarea class="form-control" name="{{ $field_name }}"></textarea>
                @elseif($field['type'] == 'select')
                    <select class="form-select" name="{{ $field_name }}">
                        <option value="">-- Select an option --</option>
                        @foreach(explode(',', $field['options']) as $option)
                            <option value="{{ trim($option) }}">{{ trim($option) }}</option>
                        @endforeach
                    </select>
                @endif

                @if(!empty($field['description']))
                    <small class="form-text text-muted">{{ $field['description'] }}</small>
                @endif
            </div>
        @endforeach

        <button type="submit" class="btn btn-success">Submit</button>
    </form>
</div>
@endsection
