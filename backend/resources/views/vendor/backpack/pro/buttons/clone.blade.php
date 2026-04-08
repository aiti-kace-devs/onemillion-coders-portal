@if ($crud->hasAccess('clone', $entry))
	<a 
    href="javascript:void(0)" 
    onclick="cloneEntry(this)" 
    bp-button="clone" 
    data-route="{{ url($crud->route.'/'.$entry->getKey().'/clone') }}" 
    data-table-id="{{ isset($crudTableId) ? $crudTableId : 'crudTable' }}"
    data-success-text="{!! trans('backpack::crud.clone_success') !!}"
    data-error-text="{!! trans('backpack::crud.clone_failure') !!}"
    class="btn btn-sm btn-link" 
    data-button-type="clone"
    ><i class="la la-copy"></i> <span>{{ trans('backpack::crud.clone') }}</span></a>
@endif

{{-- Button Javascript --}}
{{-- - used right away in AJAX operations (ex: List) --}}
{{-- - pushed to the end of the page, after jQuery is loaded, for non-AJAX operations (ex: Show) --}}
@push('after_scripts') @if (request()->ajax()) @endpush @endif
@bassetBlock('backpack/crud/buttons/clone-button.js')
<script>
	if (typeof cloneEntry != 'function') {
	  $("[data-button-type=clone]").unbind('click');

	  function cloneEntry(cloneButton) {
	      // ask for confirmation before deleting an item
	      // e.preventDefault();
	      let button = $(cloneButton);
	      let route = button.attr('data-route');
		    let tableId = button.attr('data-table-id');
		    let table = window.crud.tables[tableId] || window.crud.table;

          $.ajax({
              url: route,
              type: 'POST',
              success: function(result) {
                  // Show an alert with the result
                  new Noty({
                    type: "success",
                    text: button.attr('data-success-text')
                  }).show();

                  // Hide the modal, if any
                  $('.modal').modal('hide');

                  // if result has a redirect, redirect to that location string
                  if (result.redirect) {
                      window.location = result.redirect;
                  }


                  if (typeof table !== 'undefined') {
                    table.draw(false);
                  }
              },
              error: function(result) {
                  // Show an alert with the result
                  new Noty({
                    type: "warning",
                    text: button.attr('data-error-text')
                  }).show();
              }
          });
      }
	}
</script>
@endBassetBlock
@if (!request()->ajax()) @endpush @endif
