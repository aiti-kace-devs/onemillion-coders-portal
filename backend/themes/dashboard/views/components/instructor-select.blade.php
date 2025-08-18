<div>
    <label class="h5 font-weight-normal" for="instructor-select">
        Select instructors that taught you?
        <span class="text-danger">*</span>
    </label>
</div>
@foreach ($instructors as $idx => $instructor)
@php
$optionValue = trim($instructor->id);
$selectedValue = old('response_data[instructors]', $responses[$idx] ?? '');
@endphp


<div class="form-check">
    <input type="checkbox"
        class="form-check-input"
        name="response_data[instructors][]"
        id="instructor-opt-{{ $idx }}"
        value="{{ trim($instructor->id) }}"
        @checked($selectedValue==$optionValue)>
    <label class="form-check-label" for="instructor-opt-{{ $idx }}">
        {{ ucfirst(trim($instructor->name)) }}
    </label>
</div>
@endforeach
<span class="response_data_instructors_error font-weight-bold invalid-feedback" style="display: block;" role="alert"></span>

<input type="hidden" name="response_data[instructors_select]" value="true">