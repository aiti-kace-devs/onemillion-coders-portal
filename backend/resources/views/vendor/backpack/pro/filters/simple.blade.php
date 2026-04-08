{{-- Simple Backpack CRUD filter --}}
<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
	filter-debounce="{{ $filter->options['debounce'] ?? 0 }}"
	filter-init-function="{{ $filter->options['init_function'] ?? 'initSimpleFilter' }}"
	class="nav-item {{ Request::get($filter->name)?'active':'' }}">
    <a class="nav-link" href="javascript:void(0);">{{ $filter->label }}</a>
  </li>

{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}
@push('after_scripts')
@bassetBlock('simple-filter.js')
    <script>
		// init function for simple filter
		function initSimpleFilter(filter, filterNavbar) {
			let filterName = filter.getAttribute('filter-name');
			let filterKey = filter.getAttribute('filter-key');
			let filterAnchor = filter.querySelector('a');
			let filterDebounce = filter.getAttribute('filter-debounce');
			let navBarId = filterNavbar.getAttribute('id');
			let shouldUpdateUrl = true;

			// check if the filter was already initialized
			if (filter.getAttribute('data-filter-initialized') === 'true') {
				return;
			}
			filter.setAttribute('data-filter-initialized', 'true');

			filterAnchor.addEventListener('click', async function(e) {
				e.preventDefault();

				// get the filter value
				let filterValue = filter.classList.contains('active') ? true : false;

				switch (filterValue) {
					case true:
						filterValue = null;
						break;
					default:
						filterValue = true;
				}

				if (filterValue) {
					filter.classList.add('active');
				}else{
					filter.dispatchEvent(new CustomEvent('backpack:filter:clear'));
				} 

				document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
					detail: {
						filterName: filterName, 
						filterValue: filterValue, 
						shouldUpdateUrl: shouldUpdateUrl,
						debounce: filterDebounce,
						componentId: filterNavbar.getAttribute('data-component-id'),
					}
				}));
			});	

			// clear filter event (used here and by the Remove all filters button)
			filter.addEventListener('backpack:filter:clear', function(e) {
				filter.classList.remove('active');
			});
		};			
	</script>
@endBassetBlock
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
