{{-- Select2 Multiple Backpack CRUD filter --}}
<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
	filter-init-function="{{ $filter->init_function ?? 'initSelect2MultipleFilter' }}"
	filter-debounce="{{ $filter->options['debounce'] ?? 0 }}"
	class="nav-item dropdown {{ Request::get($filter->name)?'active':'' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <div class="dropdown-menu p-0">
      <div class="form-group backpack-filter mb-0">
			<select 
				id="filter_{{ $filter->key }}"
				name="filter_{{ $filter->key }}"
				class="form-control input-sm select2"
				placeholder="{{ $filter->placeholder }}"
				data-filter-key="{{ $filter->key }}"
				data-filter-type="select2_multiple"
				data-filter-name="{{ $filter->name }}"
				data-language="{{ str_replace('_', '-', app()->getLocale()) }}"
				data-close-on-select="{{ $filter->options['close_on_select'] ?? 'false' }}"
				multiple
				>
				@if (is_array($filter->values) && count($filter->values))
					@foreach($filter->values as $key => $value)
						<option value="{{ $key }}"
							@if($filter->isActive() && json_decode($filter->currentValue) && array_search($key, json_decode($filter->currentValue)) !== false)
								selected
							@endif
							>
							{{ $value }}
						</option>
					@endforeach
				@endif
			</select>
		</div>
    </div>
  </li>

{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS --}}
{{-- push things in the after_styles section --}}

@push('before_styles')
    {{-- include select2 css --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
	  .form-inline .select2-container {
	    display: inline-block;
	  }
	  .select2-drop-active {
	  	border:none;
	  }
	  .select2-container .select2-choices .select2-search-field input, .select2-container .select2-choice, .select2-container .select2-choices {
	  	border: none;
	  }
	  .select2-container-active .select2-choice {
	  	border: none;
	  	box-shadow: none;
	  }
	  .select2-container--bootstrap .select2-dropdown {
	  	margin-top: -2px;
	  	margin-left: -1px;
	  }
	  .select2-container--bootstrap {
	  	position: relative!important;
	  	top: 0px!important;
	  }
    </style>
@endpush


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('after_scripts')
	{{-- include select2 js --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js" crossorigin="anonymous"></script>
    @if (app()->getLocale() !== 'en')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/i18n/{{ str_replace('_', '-', app()->getLocale()) }}.js" crossorigin="anonymous"></script>
    @endif

    <script>
        function initSelect2MultipleFilter(filter, filterNavbar) {
			let filterName = filter.getAttribute('filter-name');
			let filterKey = filter.getAttribute('filter-key');
			let selectElement = filter.querySelector('select');
			let closeOnSelect = selectElement.getAttribute('data-close-on-select') === 'true';
			let filterDebounce = filter.getAttribute('filter-debounce');
			let shouldUpdateUrl = true;

			// check if the filter was already initialized
			if (filter.getAttribute('data-filter-initialized') === 'true') {
				return;
			}
			filter.setAttribute('data-filter-initialized', 'true');

            // trigger select2 for each untriggered select2 box
				$(selectElement).select2({
                	allowClear: true,
					closeOnSelect: false,
					theme: "bootstrap",
					dropdownParent: selectElement.closest('.form-group'),
	        	    placeholder: selectElement.getAttribute('placeholder'),
					closeOnSelect: closeOnSelect,
                }).on('change', async function() {
                    var value = '';
                    if (Array.isArray($(this).val())) {
                        // clean array from undefined, null, "".
                        var values = $(this).val().filter(function(e){ return e === 0 || e });
                        // stringify only if values is not empty. otherwise it will be '[]'.
                        value = values.length ? JSON.stringify(values) : '';
                    }

                    if (!value) {
                        return;
                    }
					
					filter.classList.add('active');

                    document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
						detail: {
							filterName: filterName,
							filterValue: value,
							shouldUpdateUrl: shouldUpdateUrl,
							debounce: filterDebounce,
							componentId: filterNavbar.getAttribute('data-component-id'),
						}
					}));
				}).on('select2:unselecting', async function(e) {

                    var unselectingValue = e.params.args.data.id;
                    let currentElementValue = $(this).val();

                    if(currentElementValue.length) {
                        currentElementValue = currentElementValue.filter(function(v) {
                            return v !== unselectingValue
                        });

                        if (!currentElementValue.length) {
                            document.dispatchEvent(new CustomEvent('backpack:filter:changed', {detail: {
								filterName: filterName, 
								filterValue: null, 
								shouldUpdateUrl: true,
								debounce: filterDebounce,
								componentId: filterNavbar.getAttribute('data-component-id'),
							}}));
							filter.classList.remove('active')
							filter.querySelector('.dropdown-menu').classList.remove('show');
						}
                    }
                }).on('select2:clear', async function(e) {
                    // when the "x" clear all button is pressed, we update the table
					filter.classList.remove('active');
					filter.querySelector('.dropdown-menu').classList.remove('show');

                    document.dispatchEvent(new CustomEvent('backpack:filter:changed', {detail: {
						filterName: filterName, 
						filterValue: null, 
						shouldUpdateUrl: true,
						debounce: filterDebounce,
						componentId: filterNavbar.getAttribute('data-component-id'),
					}}));
                });

				// when the dropdown is opened, autofocus on the select2
				filter.addEventListener('show.bs.dropdown', function(e) {
					setTimeout(() => {
						$(selectElement).select2('open');
					}, 50);
				});

				// clear filter event (used here and by the Remove all filters button)
				filter.addEventListener('backpack:filter:clear', function(e) {
					filter.classList.remove('active');
	                $(selectElement).val(null).trigger('change');
				});
            };
	</script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
