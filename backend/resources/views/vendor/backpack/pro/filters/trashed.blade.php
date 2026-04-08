{{-- Trashed Filter Backpack CRUD filter --}}
<li filter-name="{{ $filter->name }}"
    filter-type="{{ $filter->type }}"
    filter-key="{{ $filter->key }}"
    filter-init-function="{{ $filter->options['init_function'] ?? 'initTrashFilter' }}"
    filter-delete-without-trash="{{ var_export($filter->options['deleteWithoutTrash'] ?? false) }}"
    filter-hide-action-column="{{ var_export($filter->options['hideActionColumn'] ?? false) }}"
    filter-debounce="{{ $filter->options['debounce'] ?? 0 }}"
    filter-has-action-column="{{ var_export($crud->buttons()->where('stack', 'line')->count()) }}"

    class="nav-item {{ Request::get($filter->name)?'active':'' }}">
    <a class="nav-link" href="javascript:void(0);"
        >{{ $filter->label }}</a>
  </li>


{{-- ########################################### --}}
{{-- Extra CSS and JS for this particular filter --}}

@push('after_scripts')
    <script>
        if(typeof setupTrashFilter !== 'function') {
            function setupTrashFilter(filter, filterNavbar, dataTable = null) {
                let filterName = filter.getAttribute('filter-name');
                let filterKey = filter.getAttribute('filter-key');
                let filterAnchor = filter.querySelector('a');
                let filterDebounce = filter.getAttribute('filter-debounce');
                let hasActionColumn = filter.getAttribute('filter-has-action-column') === 'true';
                let hideActionColumn = filter.getAttribute('filter-hide-action-column') === 'true';
                let canDestroyNonTrashedItems = filter.getAttribute('filter-delete-without-trash') === 'true';
                let actionColumnIndex = null;
                let destroyBtn = null;
                let restoreBtn = null;

                if(dataTable) {
                    let ajax_table = new DataTable(dataTable);
                    let tableUrl = ajax_table.ajax.url();
                    let tableId = ajax_table.table().node().id;
                    actionColumnIndex = $(dataTable).find('th[data-action-column=true]').index();
                    destroyBtn = document.querySelector(`#bottom_buttons_${tableId} .bulk-destroy-button`);
                    restoreBtn = document.querySelector(`#bottom_buttons_${tableId} .bulk-restore-button`);
                    
                    // Initialize buttons display
                    if (filterAnchor.classList.contains('active')) {
                        document.querySelectorAll(`#bottom_buttons_${tableId} .btn:not(.bulk-destroy-button):not(.bulk-restore-button)`).forEach(btn => {
                            btn.style.display = 'none';
                        });
                        if (destroyBtn) { destroyBtn.style.display = 'inline-block'; }
                        if (restoreBtn) { restoreBtn.style.display = 'inline-block'; }
                        if(hasActionColumn && hideActionColumn){
                            ajax_table.column(actionColumnIndex).visible(false);
                        }
                    } else {
                        if(!canDestroyNonTrashedItems) {
                            let trashButton = document.querySelector(`#bottom_buttons_${tableId} .trash-button .btn:not(.bulk-trash-button)`);
                            if(trashButton) { trashButton.style.display = 'none'; }
                        } else {
                            if(restoreBtn) { restoreBtn.style.display = 'none'; }
                        }
                    }
                }

                filterAnchor.addEventListener('click', async function(e) {
                    e.preventDefault();

                    let value = filter.classList.contains('active') ? null : true;

                    if (value) {
                        filter.classList.add('active');
                        filterAnchor.classList.add('active'); // Add active class to the anchor as well
                        if(dataTable) {
                            let ajax_table = new DataTable(dataTable);
                            let tableId = ajax_table.table().node().id;
                            document.querySelectorAll(`#bottom_buttons_${tableId} .btn:not(.bulk-destroy-button):not(.bulk-restore-button)`).forEach(btn => {
                                btn.style.display = 'none';
                            });
                            if(destroyBtn) { destroyBtn.style.display = 'inline-block'; }
                            if(restoreBtn) { restoreBtn.style.display = 'inline-block'; }
                            
                            if(hasActionColumn && hideActionColumn){
                                ajax_table.column(actionColumnIndex).visible(false);
                            }
                        }
                    } else {
                        filter.dispatchEvent(new CustomEvent('backpack:filter:clear'));
                    }

                    document.dispatchEvent(new CustomEvent('backpack:filter:changed', {detail: {
                        filterName: filterName, 
                        filterValue: value, 
                        shouldUpdateUrl: true,
                        debounce: filterDebounce,
                        componentId: filterNavbar.getAttribute('data-component-id'), 
                     }}));			
                });

                // clear filter event (used here and by the Remove all filters button)
                filter.addEventListener('backpack:filter:clear', function(e) {
                    if(dataTable) {
                        let ajax_table = new DataTable(dataTable);
                        let tableId = ajax_table.table().node().id;
                        document.querySelectorAll(`#bottom_buttons_${tableId} .btn`).forEach(btn => {
                            btn.style.display = 'inline-block';
                        });
                        if(hasActionColumn && hideActionColumn){
                            ajax_table.column(actionColumnIndex).visible(true);
                        }
                        if(!canDestroyNonTrashedItems) {
                            let trashButton = document.querySelector(`#bottom_buttons_${tableId} .trash-button .btn:not(.bulk-trash-button)`);
                            if(trashButton) { trashButton.style.display = 'none'; }
                        }
                    }
                    filter.classList.remove('active');
                    filterAnchor.classList.remove('active'); // Remove active class from anchor as well
                });			
            }
        }
        function initTrashFilter(filter, filterNavbar) {
        // check if the filter was already initialized
        if (filter.getAttribute('data-filter-initialized') === 'true') {
            return;
        }
        filter.setAttribute('data-filter-initialized', 'true');

        // Check if the filter is already active from URL parameters
        if (new URLSearchParams(window.location.search).has(filter.getAttribute('filter-name'))) {
            filter.classList.add('active');
            filter.querySelector('a').classList.add('active');
        }

        const filterKey = filter.getAttribute('filter-key');
        const componentId = filterNavbar.getAttribute('data-component-id');
        const filterIdentifier = `trash_filter_initialized_${componentId || 'global'}_${filterKey}`;
        
        // Create a map to track initialized tables for this filter
        if (!window.trashFilterInitializedTables) {
            window.trashFilterInitializedTables = new Map();
        }

        // Check if there is a datatable on the page
        if (typeof DataTable !== 'undefined') {
            const eventName = `init.dt.${filterIdentifier}`;
            
            // Remove any existing handler to prevent duplicates
            $(document).off(eventName);
            
            // Add the new handler
            $(document).on(eventName, function(e, settings) {
                if ($(settings.nTable).hasClass('dataTable')) {
                    let tableId = settings.nTable.id;
                    
                    // Skip if this table was already initialized for this filter
                    if (window.trashFilterInitializedTables.get(filterIdentifier)?.includes(tableId)) {
                        return;
                    }
                    
                    if (!componentId || tableId.includes(componentId)) {                    
                        const initializedTables = window.trashFilterInitializedTables.get(filterIdentifier) || [];
                        initializedTables.push(tableId);
                        window.trashFilterInitializedTables.set(filterIdentifier, initializedTables);
                        
                        // Setup the trash filter
                        setupTrashFilter(filter, filterNavbar, settings.nTable);
                    }
                }
            });
            
            // Trigger initialization for already initialized tables
            $('.dataTable').each(function() {
                const tableId = $(this).attr('id');
                if (!window.trashFilterInitializedTables.get(filterIdentifier)?.includes(tableId)) {
                    if (!componentId || tableId.includes(componentId)) {
                        setupTrashFilter(filter, filterNavbar, this);
                        
                        // Mark as initialized
                        const initializedTables = window.trashFilterInitializedTables.get(filterIdentifier) || [];
                        initializedTables.push(tableId);
                        window.trashFilterInitializedTables.set(filterIdentifier, initializedTables);
                    }
                }
            });
        } else {
            setupTrashFilter(filter, filterNavbar);
        }
    }
    </script>
@endpush
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}