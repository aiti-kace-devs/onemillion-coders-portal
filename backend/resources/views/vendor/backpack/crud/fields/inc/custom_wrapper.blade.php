<div @if (isset($field['wrapperAttributes'])) 
        @foreach ($field['wrapperAttributes'] as $attribute => $value)
            {{ $attribute }}="{{ $value }}"
        @endforeach
     @endif
     @if (!isset($field['wrapperAttributes']) && isset($field['wrapper']))
        class="{{ $field['wrapper']['class'] ?? '' }}"
     @endif
></div>