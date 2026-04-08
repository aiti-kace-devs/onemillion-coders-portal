{{-- Date Range Backpack CRUD filter --}}
<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
	filter-init-function="{{ $filter->init_function ?? 'initDateFilter' }}"
	filter-language="{{ $filter->options['language'] ?? \App::getLocale() }}"
	filter-debounce="{{ $filter->options['debounce'] ?? 0 }}"
	class="nav-item dropdown {{ Request::get($filter->name)?'active':'' }}">
	<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
	<div class="dropdown-menu p-0">
		<div class="form-group backpack-filter mb-0">
			<div class="input-group date">
                <span class="input-group-text"><i class="la la-calendar"></i></span>
		        <input class="form-control pull-right"
		        		id="datepicker-{{ $filter->key }}"
		        		type="text"
						@if ($filter->currentValue)
							value="{{ $filter->currentValue }}"
						@endif
		        		>
                <a class="input-group-text datepicker-{{ $filter->key }}-clear-button" href=""><i class="la la-times"></i></a>
		    </div>
		</div>
	</div>
</li>

{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS --}}
{{-- push things in the after_styles section --}}

@push('before_styles')
	{{-- include datepicker css --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker3.min.css" rel="stylesheet" crossorigin="anonymous">
	<style>
		.input-group.date {
			width: 320px;
			max-width: 100%;
		}
	</style>
@endpush


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('after_scripts')
	{{-- include datepicker js --}}
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js" crossorigin="anonymous"></script>
	@php $language = $filter->options['language'] ?? \App::getLocale(); @endphp
	@if ($language !== 'en')
		<script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/locales/bootstrap-datepicker.{{ $language }}.min.js" charset="UTF-8" crossorigin="anonymous"></script>
	@endif
  <script>
		function initDateFilter(filter, filterNavbar) {
			let filterName = filter.getAttribute('filter-name');
			let filterKey = filter.getAttribute('filter-key');
			let filterLanguage = filter.getAttribute('filter-language');
			let navBarId = filterNavbar.getAttribute('id');
			let datepickerInput = filter.querySelector('input');
			let filterClearButton = filter.querySelector(`.datepicker-${filterKey}-clear-button`);
			let filterDebounce = filter.getAttribute('filter-debounce');
			let shouldUpdateUrl = true;

			// check if the filter was already initialized
			if (filter.getAttribute('data-filter-initialized') === 'true') {
				return;
			}
			filter.setAttribute('data-filter-initialized', 'true');

			var dateInput = $(datepickerInput).datepicker({
				autoclose: true,
				format: 'yyyy-mm-dd',
				todayHighlight: true,
				language: filterLanguage,
			})
			.on('changeDate', async function(e) {
				var d = new Date(e.date);

				if (isNaN(d.getFullYear())) {
					var value = '';
				} else {
					var value = d.getFullYear() + "-" + ("0"+(d.getMonth()+1)).slice(-2) + "-" + ("0" + d.getDate()).slice(-2);
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

			// open the datepicker when filter is open
			filter.querySelector('a[data-bs-toggle]').addEventListener('click', function(e) {
				setTimeout(() => {
					dateInput.focus();
				}, 50);
			});

			filter.addEventListener('backpack:filter:clear', function(e) {
				filter.classList.remove('active');
				$(datepickerInput).datepicker('update', '');
				if(!e.detail || !e.detail.clearAllFilters) {
					datepickerInput.dispatchEvent(new Event('changeDate'));
				}
			});

			// datepicker clear button
			filterClearButton.addEventListener('click', function(e) {
				e.preventDefault();
				filter.dispatchEvent(new CustomEvent('backpack:filter:clear'));
			})
		};
  </script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
