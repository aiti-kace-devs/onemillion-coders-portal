{{-- Select2 Ajax Backpack CRUD filter --}}

@php
    $filter->options['quiet_time'] = $filter->options['quiet_time'] ?? $filter->options['delay'] ?? 500;
@endphp

<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
    filter-init-function="{{ $filter->options['init_function'] ?? 'initSelect2AjaxFilter' }}"
    filter-debounce="{{ $filter->options['debounce'] ?? 0 }}"
    filter-ajax-url="{{ $filter->values }}"
	class="nav-item dropdown {{ Request::get($filter->name)?'active':'' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <div class="dropdown-menu p-0 ajax-select">
	    <div class="form-group mb-0">
            <select
                name="filter_{{ $filter->name }}"
                class="form-control input-sm select2"
                placeholder="{{ $filter->placeholder }}"
                data-filter-key="{{ $filter->key }}"
                data-filter-type="select2_ajax"
                data-filter-name="{{ $filter->name }}"
                data-select-key="{{ $filter->options['select_key'] ?? 'id' }}"
                data-select-attribute="{{ $filter->options['select_attribute'] ?? 'name' }}"
                data-language="{{ str_replace('_', '-', app()->getLocale()) }}"
                filter-minimum-input-length="{{ $filter->options['minimum_input_length'] ?? 2 }}"
                filter-method="{{ $filter->options['method'] ?? 'GET' }}"
                filter-quiet-time="{{ $filter->options['quiet_time'] }}"
            >
				@if (Request::get($filter->name))
					<option value="{{ Request::get($filter->name) }}" selected="selected"> {{ Request::get($filter->name.'_text') ?? 'Previous selection' }} </option>
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
        function initSelect2AjaxFilter(filter, filterNavbar) {
            	let filterName = filter.getAttribute('filter-name');
                let filterKey = filter.getAttribute('filter-key');
                let selectElement = filter.querySelector('select');
                let filterAjaxUrl = filter.getAttribute('filter-ajax-url');
                let selectAttribute = selectElement.getAttribute('data-select-attribute');
                let selectKey = selectElement.getAttribute('data-select-key');
                let filterMethod = selectElement.getAttribute('filter-method');
                let filterQuietTime = selectElement.getAttribute('filter-quiet-time');
                let filterDebounce = filter.getAttribute('filter-debounce');
                let shouldUpdateUrl = true;

				// check if the filter was already initialized
				if (filter.getAttribute('data-filter-initialized') === 'true') {
					return;
				}
				filter.setAttribute('data-filter-initialized', 'true');

            	$(selectElement).select2({
				    theme: "bootstrap",
				    minimumInputLength: selectElement.getAttribute('filter-minimum-input-length'),
	            	allowClear: true,
	        	    placeholder: selectElement.getAttribute('placeholder'),
					closeOnSelect: false,
					dropdownParent: selectElement.closest('.form-group'),
				    ajax: {
				        url: filterAjaxUrl,
				        dataType: 'json',
				        type: filterMethod,
				        delay: filterQuietTime,
				        processResults: function (data) {
                            //if we have data.data here it means we returned a paginated instance from controller.
                            //otherwise we returned one or more entries unpaginated.
                            if (data.data) { 
                                return {
                                    results: data.data.map(item => ({
                                        text: item[selectAttribute],
                                        id: item[selectKey]
                                    })),
                                    pagination: {
                                        more: typeof data.next_page_url !== 'undefined' && data.next_page_url !== null,
                                    },
                                };
                            }

                            //it's non-paginated result
                            return {
                                results: Object.entries(data).map(([key, value]) => ({
                                    text: value,
                                    id: key
                                }))
                            };
                        }
				    }
				}).on('change', async function (evt) {
                        var val = $(this).val();
                        var val_text = $(this).select2('data')[0]?$(this).select2('data')[0].text:null;
                        var extra_param = filterName + '_text';

                        if (!val_text) {
                           return;
                        }

                        filter.classList.add('active');

                        document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
                            detail: {
                                filterName: filterName,
                                filterValue: val,
                                shouldUpdateUrl: false,
                                debounce: filterDebounce,
                                componentId: filterNavbar.getAttribute('data-component-id'),
                            }
                        }));

                        document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
                            detail: {
                                filterName: extra_param,
                                filterValue: val_text,
                                shouldUpdateUrl: shouldUpdateUrl,
                                debounce: filterDebounce,
                                componentId: filterNavbar.getAttribute('data-component-id'),
                            }
                        }));
    

                        
                    }).on('select2:unselecting', async function (e) {
                        var extra_param = filterName + '_text';
                        filter.classList.remove('active');
                        filter.querySelector('.dropdown-menu').classList.remove('show');

                        document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
                            detail: {
                                filterName: filterName,
                                filterValue: null,
                                shouldUpdateUrl: false,
                                debounce: filterDebounce,
                                componentId: filterNavbar.getAttribute('data-component-id'),
                            }
                        }));

                        document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
                            detail: {
                                filterName: extra_param,
                                filterValue: null,
                                shouldUpdateUrl: true,
                                debounce: filterDebounce,
                                componentId: filterNavbar.getAttribute('data-component-id'),
                            }
                        }));    
                    });

                // when the dropdown is opened, autofocus on the select2
				filter.addEventListener('show.bs.dropdown', function(e) {
					setTimeout(() => {
						$(selectElement).select2('open');
						$(selectElement).data('select2').dropdown.$search.get(0).focus();
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
