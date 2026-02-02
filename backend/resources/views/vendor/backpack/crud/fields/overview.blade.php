@php
    // Set default values
    $field['value'] = $field['value'] ?? [
        'what_you_will_learn' => [],
        'why_choose_this_course' => []
    ];
    
    // Ensure arrays exist
    if (!isset($field['value']['what_you_will_learn'])) {
        $field['value']['what_you_will_learn'] = [];
    }
    if (!isset($field['value']['why_choose_this_course'])) {
        $field['value']['why_choose_this_course'] = [];
    }
@endphp

<!-- Hidden inputs to ensure empty arrays are sent -->
<input type="hidden" name="overview[what_you_will_learn][]" value="">
<input type="hidden" name="overview[why_choose_this_course][]" value="">

<div class="form-group col-md-12 overview-field-container">
    <label class="font-weight-bold">{!! $field['label'] !!}</label>
    
    <div class="row">
        <!-- What You Will Learn Section -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">What You Will Learn</h5>
                </div>
                <div class="card-body">
                    <div class="what-you-will-learn-container">
                        @foreach($field['value']['what_you_will_learn'] as $item)
                            @if(!empty($item))
                                <div class="form-group learning-item">
                                    <div class="input-group mb-2">
                                        <input type="text" 
                                               name="overview[what_you_will_learn][]" 
                                               value="{{ $item }}" 
                                               class="form-control" 
                                               placeholder="Enter learning objective">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-danger remove-item" type="button">
                                                <i class="la la-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-primary add-learning-item mt-2">
                        <i class="la la-plus"></i> Add Learning Objective
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Why Choose This Course Section -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Why Choose This Course</h5>
                </div>
                <div class="card-body">
                    <div class="why-choose-container">
                        @foreach($field['value']['why_choose_this_course'] as $item)
                            @if(!empty($item))
                                <div class="form-group benefit-item">
                                    <div class="input-group mb-2">
                                        <input type="text" 
                                               name="overview[why_choose_this_course][]" 
                                               value="{{ $item }}" 
                                               class="form-control" 
                                               placeholder="Enter benefit">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-danger remove-item" type="button">
                                                <i class="la la-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-success add-benefit-item mt-2">
                        <i class="la la-plus"></i> Add Benefit
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if (isset($field['hint']))
        <p class="help-block text-muted mt-2">{!! $field['hint'] !!}</p>
    @endif
</div>

@push('crud_fields_styles')
<style>
    .overview-field-container .card {
        height: 100%;
    }
    .overview-field-container .card-header {
        padding: 0.5rem 1rem;
    }
    .remove-item {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .what-you-will-learn-container,
    .why-choose-container {
        min-height: 50px;
    }
</style>
@endpush

@push('crud_fields_scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add learning item
    document.querySelector('.add-learning-item').addEventListener('click', function() {
        const container = document.querySelector('.what-you-will-learn-container');
        
        // Remove empty hidden input if it exists
        const hiddenInput = container.querySelector('input[type="hidden"]');
        if (hiddenInput) {
            hiddenInput.remove();
        }
        
        const newItem = document.createElement('div');
        newItem.className = 'form-group learning-item';
        newItem.innerHTML = `
            <div class="input-group mb-2">
                <input type="text" 
                       name="overview[what_you_will_learn][]" 
                       class="form-control" 
                       placeholder="Enter learning objective">
                <div class="input-group-append">
                    <button class="btn btn-outline-danger remove-item" type="button">
                        <i class="la la-trash"></i>
                    </button>
                </div>
            </div>`;
        
        container.appendChild(newItem);
    });

    // Add benefit item
    document.querySelector('.add-benefit-item').addEventListener('click', function() {
        const container = document.querySelector('.why-choose-container');
        
        // Remove empty hidden input if it exists
        const hiddenInput = container.querySelector('input[type="hidden"]');
        if (hiddenInput) {
            hiddenInput.remove();
        }
        
        const newItem = document.createElement('div');
        newItem.className = 'form-group benefit-item';
        newItem.innerHTML = `
            <div class="input-group mb-2">
                <input type="text" 
                       name="overview[why_choose_this_course][]" 
                       class="form-control" 
                       placeholder="Enter benefit">
                <div class="input-group-append">
                    <button class="btn btn-outline-danger remove-item" type="button">
                        <i class="la la-trash"></i>
                    </button>
                </div>
            </div>`;
        
        container.appendChild(newItem);
    });

    // Remove item
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            const item = e.target.closest('.form-group');
            const container = item.parentElement;
            
            // If this is the last item being removed, add back the hidden input
            if (container.querySelectorAll('.form-group').length <= 1) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = container.classList.contains('what-you-will-learn-container') 
                    ? 'overview[what_you_will_learn][]' 
                    : 'overview[why_choose_this_course][]';
                hiddenInput.value = '';
                container.appendChild(hiddenInput);
            }
            
            item.remove();
        }
    });

    // Clean empty values before form submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            // Remove empty inputs
            document.querySelectorAll('input[name^="overview["]').forEach(input => {
                if (input.value.trim() === '' && input.type !== 'hidden') {
                    input.remove();
                }
            });
        });
    }
});
</script>
@endpush