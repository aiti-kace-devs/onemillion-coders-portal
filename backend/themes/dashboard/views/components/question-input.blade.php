@props([
'instructors' => [],
'sectionQuestions' => [],
'hideLabel' => false,
'sectionIndex',
'sectionTitle',
'responses' => [],
])

@foreach ($sectionQuestions as $index => $question)
@php
$fieldName = "response_data[{$question['field_name']}]";
$fieldId = "field-{$sectionIndex}-{$index}";
$required = !empty($question['validators']['required']) ? 'required' : '';
$options = isset($question['options']) ? explode(',', $question['options']) : [];
@endphp

<div class="form-group">
    <div>
        <label class="h5 font-weight-normal" for="field-{{ $sectionIndex }}-{{ $index }}">
            {{ $question['title'] }}

            @if($question['validators']['required'] ?? false)
            <span class="text-danger">*</span>
            @endif
        </label>
    </div>
    {{-- Input types --}}


    @if (in_array($question['type'], ['text', 'email', 'number', 'password']))
    <input type="{{ $question['type'] }}"
        name="{{ $fieldName }}"
        id="{{ $fieldId }}"
        class="form-control"
        placeholder="{{ $question['title'] }}">
    @elseif ($question['type'] === 'checkbox')
    @foreach ($options as $idx => $option)
    @php
    $optionValue = trim($option);
    $selectedValue = old($fieldName, ($sectionTitle === 'instructors' ? $responses[$question['field_name']] : $responses[$sectionTitle][$question['field_name']] ?? ''));
    @endphp

    <div class="form-check">
        <input type="checkbox"
            class="form-check-input"
            name="{{ $fieldName }}[]"
            id="{{ $fieldId }}-opt-{{ $idx }}"
            value="{{ $optionValue }}"
            @checked($selectedValue==$optionValue)>
        <label class="form-check-label" for="{{ $fieldId }}-opt-{{ $idx }}">
            {{ ucfirst(trim($option)) }}
        </label>
    </div>
    @endforeach
    @elseif ($question['type'] === 'radio')
    @foreach ($options as $idx => $option)
    @php
    $optionValue = trim($option);
    $selectedValue = old($fieldName, ($sectionTitle === 'instructors' ? $responses[$question['field_name']] ?? '' : $responses[$sectionTitle][$question['field_name']] ?? ''));
    @endphp

    <div class="form-check form-check-inline">
        <input type="radio"
            class="form-check-input"
            name="{{ $fieldName }}"
            id="{{ $fieldId }}-opt-{{ $idx }}"
            value="{{ $optionValue }}"
            @checked($selectedValue==$optionValue)>

        <label class="form-check-label text-capitalize" for="{{ $fieldId }}-opt-{{ $idx }}">
            {{
                match ($optionValue) {
                    '1' => 'Very Bad',
                    '2' => 'Bad',
                    '3' => 'Good',
                    '4' => 'Very Good',
                    '5' => 'Excellent',
                    default => ucfirst($optionValue)
                }
            }}
        </label>
    </div>
    @endforeach


    @endif

    @if (!empty($question['description']))
    <small class="form-text text-info">{{ $question['description'] }}</small>
    @endif
    <span class="{{ str_replace(['[', ']'], ['_', ''], $fieldName) }}_error font-weight-bold invalid-feedback" style="display: block;" role="alert"></span>
</div>
@endforeach