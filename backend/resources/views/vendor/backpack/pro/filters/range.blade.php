{{-- Example Backpack CRUD filter --}}
<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
	filter-init-function="{{ $filter->init_function ?? 'initRangeFilter' }}"
	filter-debounce="{{ $filter->options['debounce'] ?? 0 }}"
	class="nav-item dropdown {{ Request::get($filter->name)?'active':'' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <div class="dropdown-menu p-0">

			<div class="form-group backpack-filter mb-0">
					<?php
                        $from = '';
                        $to = '';
                        if ($filter->currentValue) {
                            $range = (array) json_decode($filter->currentValue);
                            $from = $range['from'];
                            $to = $range['to'];
                        }
                    ?>
					<div class="input-group">
				        <input class="form-control pull-right from"
				        		type="number"
									@if($from)
										value = "{{ $from }}"
									@endif
									@if(array_key_exists('label_from', $filter->options))
										placeholder = "{{ $filter->options['label_from'] }}"
									@else
										placeholder = "min value"
									@endif
				        		>
								<input class="form-control pull-right to"
				        		type="number"
									@if($to)
										value = "{{ $to }}"
									@endif
									@if(array_key_exists('label_to', $filter->options))
										placeholder = "{{ $filter->options['label_to'] }}"
									@else
										placeholder = "max value"
									@endif
				        		>
				          <a class="input-group-text range-filter-{{ $filter->key }}-clear-button" href=""><i class="la la-times"></i></a>
				    </div>
			</div>
    </div>
  </li>


{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS --}}
{{-- push things in the after_styles section --}}


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('after_scripts')
@bassetBlock('range-filter.js')
<script>
	function initRangeFilter(filter, filterNavbar) {
		let filterName = filter.getAttribute('filter-name');
		let filterKey = filter.getAttribute('filter-key');
		let filterDebounce = filter.getAttribute('filter-debounce');
		let navBarId = filterNavbar.getAttribute('id');
		let filterInputFrom = filter.querySelector('.from');
		let filterInputTo = filter.querySelector('.to');
		let filterClearButton = filter.querySelector(`.range-filter-${filterKey}-clear-button`);
		let shouldUpdateUrl = true;

		// check if the filter was already initialized
		if (filter.getAttribute('data-filter-initialized') === 'true') {
			return;
		}
		filter.setAttribute('data-filter-initialized', 'true');

		[filterInputFrom, filterInputTo].forEach(function(input) {
			input.addEventListener('change', async function(e) {
				e.preventDefault();
				let from = filterInputFrom.value;
				let to = filterInputTo.value;
				if (from || to) {
					var range = {
						'from': from,
						'to': to
					};
					var value = JSON.stringify(range);
				} else {
					var value = '';
				}

				if (value !== '') {
					filter.classList.add('active');
				}

				document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
					detail: {
						filterName: filterName,
						filterValue: value,
						shouldUpdateUrl: shouldUpdateUrl,
						debounce: filterDebounce,
						componentId: filterNavbar.getAttribute('data-component-id'),
					}
				}));
							
			});
		});

		filter.addEventListener('backpack:filter:clear', function(e) {
			filter.classList.remove('active');
			filterInputFrom.value = '';
			filterInputTo.value = '';

			if(!e.detail || !e.detail.clearAllFilters) {
				filterInputTo.dispatchEvent(new Event('change'));
			}
		});

		// focus on the input when filter is open
		filter.querySelector('a').addEventListener('click', function(e) {
			setTimeout(() => {
				filterInputFrom.focus();
			}, 50);
		});

		// range clear button
		filterClearButton.addEventListener('click', function(e) {
			e.preventDefault();
			filter.dispatchEvent(new Event('backpack:filter:clear'));
		});
	};
</script>
@endBassetBlock
@endpush

{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
