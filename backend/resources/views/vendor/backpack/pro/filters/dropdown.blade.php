{{-- Dropdown Backpack CRUD filter --}}
<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
	filter-init-function="{{ $filter->init_function ?? 'initDropdownFilter' }}"
	filter-debounce="{{ $filter->options['debounce'] ?? 0 }}"
	class="nav-item dropdown {{ Request::get($filter->name)?'active':'' }}">
    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
    <ul class="dropdown-menu">
		<a class="dropdown-item" parameter="{{ $filter->name }}" dropdownkey="" href="">-</a>
		<div role="separator" class="dropdown-divider"></div>
		@if (is_array($filter->values) && count($filter->values))
			@foreach($filter->values as $key => $value)
				@if ($key === 'dropdown-separator')
					<div role="separator" class="dropdown-divider"></div>
				@else
					<a  class="dropdown-item {{ ($filter->isActive() && $filter->currentValue == $key)?'active':'' }}"
						parameter="{{ $filter->name }}"
						href=""
						dropdownkey="{{ $key }}"
						>{{ $value }}</a>
				@endif
			@endforeach
		@endif
    </ul>
  </li>


{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS --}}
{{-- push things in the after_styles section --}}


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('after_scripts')
    <script>
		function initDropdownFilter(filter, filterNavbar) {

			let filterName = filter.getAttribute('filter-name');
			let filterKey = filter.getAttribute('filter-key');
			let filterDebounce = filter.getAttribute('filter-debounce');
			let navBarId = filterNavbar.getAttribute('id');
			let filterDropdownAnchor = filter.querySelectorAll('.dropdown-menu a');
			let filterDropdownSelected = filter.querySelector('.dropdown-menu a.active') ?? null;

			// check if the filter was already initialized
			if (filter.getAttribute('data-filter-initialized') === 'true') {
				return;
			}
			filter.setAttribute('data-filter-initialized', 'true');

			filterDropdownAnchor.forEach(function(dropdown) {
				dropdown.addEventListener('click', async function(e) {
					e.preventDefault();

					let value = this.getAttribute('dropdownkey');

					// mark this filter as active in the navbar-filters
					// mark dropdown items active accordingly
					if (value) {
						filter.classList.add('active');
						filterDropdownAnchor.forEach(function(anchor) {
							anchor.classList.remove('active');
						});
						this.classList.add('active');
					} else {
						filter.dispatchEvent(new CustomEvent('backpack:filter:clear'));
					}
					
					document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
						detail: {
							filterName: filterName, 
							filterValue: value, 
							shouldUpdateUrl: true,
							debounce: filterDebounce,
							componentId: filterNavbar.getAttribute('data-component-id'), // Include the table ID in the event
						}
					}));
				});
			});

			// clear filter event (used here and by the Remove all filters button)
			filter.addEventListener('backpack:filter:clear', function(e) {
				this.classList.remove('active');
				this.querySelectorAll('.dropdown-menu a').forEach(function(anchor) {
					anchor.classList.remove('active');
				});
			});
		};
	</script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
