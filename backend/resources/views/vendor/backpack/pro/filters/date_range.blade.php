{{-- Date Range Backpack CRUD filter --}}

@php
    $filter->options['date_range_options'] = array_replace_recursive([
		'timePicker' => false,
    	'alwaysShowCalendars' => true,
        'autoUpdateInput' => true,
        'startDate' => \Carbon\Carbon::now()->toDateTimeString(),
        'endDate' => \Carbon\Carbon::now()->toDateTimeString(),
        'ranges' => [
            trans('backpack::crud.today') =>  [\Carbon\Carbon::now()->startOfDay()->toDateTimeString(), \Carbon\Carbon::now()->endOfDay()->toDateTimeString()],
            trans('backpack::crud.yesterday') => [\Carbon\Carbon::now()->subDay()->startOfDay()->toDateTimeString(), \Carbon\Carbon::now()->subDay()->endOfDay()->toDateTimeString()],
            trans('backpack::crud.last_7_days') => [\Carbon\Carbon::now()->subDays(6)->startOfDay()->toDateTimeString(), \Carbon\Carbon::now()->toDateTimeString()],
            trans('backpack::crud.last_30_days') => [\Carbon\Carbon::now()->subDays(29)->startOfDay()->toDateTimeString(), \Carbon\Carbon::now()->toDateTimeString()],
            trans('backpack::crud.this_month') => [\Carbon\Carbon::now()->startOfMonth()->toDateTimeString(), \Carbon\Carbon::now()->endOfMonth()->toDateTimeString()],
            trans('backpack::crud.last_month') => [\Carbon\Carbon::now()->subMonth()->startOfMonth()->toDateTimeString(), \Carbon\Carbon::now()->subMonth()->endOfMonth()->toDateTimeString()]
        ],
        'locale' => [
            'firstDay' => 0,
            'format' => config('backpack.ui.default_date_format'),
            'applyLabel'=> trans('backpack::crud.apply'),
            'cancelLabel'=> trans('backpack::crud.cancel'),
            'customRangeLabel' => trans('backpack::crud.custom_range')
        ],


    ], $filter->options['date_range_options'] ?? []);

    //if filter is active we override developer init values
    if($filter->currentValue) {
	    $dates = (array)json_decode($filter->currentValue);
        $filter->options['date_range_options']['startDate'] = $dates['from'];
        $filter->options['date_range_options']['endDate'] = $dates['to'];
    }

@endphp


<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
	filter-init-function="{{ $filter->init_function ?? 'initDateRangeFilter' }}"
	filter-debounce="{{ $filter->options['debounce'] ?? 0 }}"
	filter-locale="{{ app()->getLocale() }}"
	class="nav-item dropdown {{ Request::get($filter->name)?'active':'' }}">
	<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $filter->label }} <span class="caret"></span></a>
	<div class="dropdown-menu p-0">
		<div class="form-group backpack-filter mb-0">
			<div class="input-group date">
				<span class="input-group-text"><i class="la la-calendar"></i></span>
		        <input class="form-control pull-right"
		        		id="daterangepicker-{{ $filter->key }}"
		        		type="text"
                        data-bs-daterangepicker="{{ json_encode($filter->options['date_range_options'] ?? []) }}"
		        		>
                <a class="input-group-text daterangepicker-{{ $filter->key }}-clear-button" href=""><i class="la la-times"></i></a>
		    </div>
		</div>
	</div>
</li>

{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

{{-- FILTERS EXTRA CSS --}}
{{-- push things in the after_styles section --}}

@push('before_styles')
    {{-- include daterangepicker css --}}
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-daterangepicker@3.1.0/daterangepicker.css" rel="stylesheet" crossorigin="anonymous">
	<style>
		.input-group.date {
			width: 320px;
			max-width: 100%; }
		.daterangepicker.dropdown-menu {
			z-index: 3001!important;
		}
	</style>
@endpush


{{-- FILTERS EXTRA JS --}}
{{-- push things in the after_scripts section --}}

@push('after_scripts')
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment-with-locales.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-daterangepicker@3.1.0/daterangepicker.js" crossorigin="anonymous"></script>
@bassetBlock('daterangepicker-filter.js')
<script>
	async function applyDateRangeFilter(start, end, filter, filterNavbar) {
		let filterName = filter.getAttribute('filter-name');
		const componentId = filterNavbar.getAttribute('data-component-id');

		if (start && end) {
			var dates = {
				'from': start.format('YYYY-MM-DD HH:mm:ss'),
				'to': end.format('YYYY-MM-DD HH:mm:ss')
			};

			var value = JSON.stringify(dates);
		} else {
			var value = '';
		}

		if (value !== '') {
			filter.classList.add('active');
		} else {
			filter.dispatchEvent(new CustomEvent('backpack:filter:clear'));
		}

		document.dispatchEvent(new CustomEvent('backpack:filter:changed', {
			detail: {
				filterName: filterName,
				filterValue: value,
				shouldUpdateUrl: true,
				debounce: filter.getAttribute('filter-debounce'),
				componentId: componentId,
			}
		}));
	}

	function initDateRangeFilter(filter, filterNavbar) {
		let filterName = filter.getAttribute('filter-name');
		let filterKey = filter.getAttribute('filter-key');
		let filterDebounce = filter.getAttribute('filter-debounce');
		let filterLocale = filter.getAttribute('filter-locale');
		let dateRangeInput = filter.querySelector('input');
		let filterClearButton = filter.querySelector(`.daterangepicker-${filterKey}-clear-button`);
		let filterOptions = JSON.parse(dateRangeInput.getAttribute('data-bs-daterangepicker'));

		// check if the filter was already initialized
		if (filter.getAttribute('data-filter-initialized') === 'true') {
			return;
		}
		filter.setAttribute('data-filter-initialized', 'true');

		moment.locale(filterLocale);

		let filterRanges = filterOptions.ranges;
		filterOptions.ranges = {};

		//if developer configured ranges we convert it to moment() dates.
		for (var key in filterRanges) {
			if (filterRanges.hasOwnProperty(key)) {
				filterOptions.ranges[key] = [moment(filterRanges[key][0]), moment(filterRanges[key][1])];
			}
		}

		filterOptions.startDate = moment(filterOptions.startDate);
		filterOptions.endDate = moment(filterOptions.endDate);

		$(dateRangeInput).daterangepicker(filterOptions);

		$(dateRangeInput).on('apply.daterangepicker', function(ev, picker) {
			applyDateRangeFilter(picker.startDate, picker.endDate, filter, filterNavbar);
		});

		//focus on input when filter open
		filter.querySelector('a[data-bs-toggle]').addEventListener('click', function(e) {
			setTimeout(() => {
				dateRangeInput.focus();
			}, 50);
		});
		filter.addEventListener('backpack:filter:clear', function() {
			filter.classList.remove('active');
		});
		// datepicker clear button
		filterClearButton.addEventListener('click', function(e) {
			e.preventDefault();
			applyDateRangeFilter(null, null, filter, filterNavbar);
		});
	};
</script>
@endBassetBlock
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
