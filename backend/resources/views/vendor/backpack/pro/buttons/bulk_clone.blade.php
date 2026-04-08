@if ($crud->hasAccess('bulkClone') && $crud->get('list.bulkActions'))
	<a href="javascript:void(0)" onclick="bulkCloneEntries(this)" bp-button="bulkClone" data-table-id="{{ isset($crudTableId) ? $crudTableId : 'crudTable' }}" class="btn btn-sm btn-secondary bulk-button"><i class="la la-copy"></i> <span>{{ trans('backpack::crud.clone') }}</span></a>
@endif

@push('after_scripts')
<script>
	if (typeof bulkCloneEntries != 'function') {
	  function bulkCloneEntries(button) {
		  var tableId = button.getAttribute('data-table-id');
		  var tableConfig = window.crud.tableConfigs[tableId] || window.crud;
		  var table = window.crud.tables[tableId] || window.crud.table;
		  var checkedItems = tableConfig.checkedItems;

	      if (typeof checkedItems === 'undefined' || checkedItems.length == 0)
	      {
  	        new Noty({
	          type: "warning",
	          text: "<strong>{!! trans('backpack::crud.bulk_no_entries_selected_title') !!}</strong><br>{!! trans('backpack::crud.bulk_no_entries_selected_message') !!}"
	        }).show();

	      	return;
	      }

	      var message = "{!! trans('backpack::crud.bulk_clone_are_you_sure') !!}";
	      message = message.replace(":number", checkedItems.length);

	      // show confirm message
	      swal({
			  title: "{!! trans('backpack::base.warning') !!}",
			  text: message,
			  icon: "warning",
			  buttons: {
			  	cancel: {
				  text: "{!! trans('backpack::crud.cancel') !!}",
				  value: null,
				  visible: true,
				  className: "bg-secondary",
				  closeModal: true,
				},
			  	delete: {
				  text: "{{ trans('backpack::crud.clone') }}",
				  value: true,
				  visible: true,
				  className: "bg-primary",
				}
			  },
			}).then((value) => {
				if (value) {
					var ajax_calls = [];
		      		var clone_route = "{{ url($crud->route) }}/bulk-clone";

					// submit an AJAX delete call
					$.ajax({
						url: clone_route,
						type: 'POST',
						data: { entries: checkedItems },
						success: function(result) {
						  // Show an alert with the result
		    	          new Noty({
				            type: "success",
				            text: "<strong>{!! trans('backpack::crud.bulk_clone_sucess_title') !!}</strong><br>"+checkedItems.length+" {!! trans('backpack::crud.bulk_clone_sucess_message') !!}"
				          }).show();

						  tableConfig.checkedItems = [];
						  table.draw(false);
						},
						error: function(result) {
						  // Show an alert with the result
		    	          new Noty({
				            type: "danger",
				            text: "<strong>{!! trans('backpack::crud.bulk_clone_error_title') !!}</strong><br>"+checkedItems.length+" {!! trans('backpack::crud.bulk_clone_error_message') !!}"
				          }).show();
						}
					});
				}
			});
      }
	}
</script>
@endpush