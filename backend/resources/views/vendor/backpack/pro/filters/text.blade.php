{{-- Text Backpack CRUD filter --}}

<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
	filter-init-function="{{ $filter->options['init_function'] ?? 'initTextFilter' }}"
	class="nav-item dropdown {{ Request::get($filter->name) ? 'active' : '' }}">
	<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
	<div class="dropdown-menu p-0">
		<div class="form-group backpack-filter mb-0">
			<div class="input-group">
		        <input class="form-control pull-right"
		        		id="text-filter-{{ $filter->key }}"
		        		type="text"
						@if ($filter->currentValue)
							value="{{ $filter->currentValue }}"
						@endif
		        		>
                <a class="input-group-text text-filter-{{ $filter->key }}-clear-button" href=""><i class="la la-times"></i></a>
		    </div>
		</div>
	</div>
</li>
{{-- FILTERS EXTRA JS --}}
{{-- push things in the crud_list_scripts section --}}
@push('after_scripts')
  <script>
		function initTextFilter(filter, filterNavbar) {
			let filterName = filter.getAttribute('filter-name');
			let filterKey = filter.getAttribute('filter-key');
			let navBarId = filterNavbar.getAttribute('id');
			let textInput = filter.querySelector('input');
			let clearButton = filter.querySelector('.text-filter-{{ $filter->key }}-clear-button');
			let filterDebounce = filter.getAttribute('filter-debounce');
			let shouldUpdateUrl = true;

			// check if the filter was already initialized
			if (filter.getAttribute('data-filter-initialized') === 'true') {
				return;
			}
			filter.setAttribute('data-filter-initialized', 'true');

			// focus on the input when filter is open
			filter.querySelector('a').addEventListener('click', function(e) {
				setTimeout(() => {
					textInput.focus();
				}, 50);
			});

			textInput.addEventListener('change', async function(e) {
				let value = this.value;

				if (value) {
					filter.classList.add('active');
				} else {
					filter.dispatchEvent(new Event('backpack:filter:clear'));
				}

				document.dispatchEvent(new CustomEvent('backpack:filter:changed', {detail: {
					filterName: filterName, 
					filterValue: value, 
					shouldUpdateUrl: true,
					debounce: filterDebounce,
					componentId: filterNavbar.getAttribute('data-component-id'),
				 }}));
			});
				 

			filter.addEventListener('backpack:filter:clear', function(e) {
				filter.classList.remove('active');
				textInput.value = '';
			});

			// clear button for text filter
			clearButton.addEventListener('click', function(e) {
				e.preventDefault();
				filter.dispatchEvent(new Event('backpack:filter:clear'));
				filter.classList.remove('active');
				textInput.value = '';
				textInput.dispatchEvent(new Event('change'));
			})
		};
  </script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
