@if ($crud->hasAccess('bulkRestore') && $crud->get('list.bulkActions'))
	<a href="javascript:void(0)" onclick="bulkRestoreEntries(this)" bp-button="bulkRestore" data-table-id="{{ isset($crudTableId) ? $crudTableId : 'crudTable' }}" class="btn btn-sm btn-secondary bulk-button trash-button bulk-restore-button"><i class="la la-recycle"></i> <span>{{ trans('backpack/pro::trash.restore') }}</span></a>
@endif

@push('after_scripts')
<script>
	if (typeof bulkRestoreEntries != 'function') {
	  function bulkRestoreEntries(button) {
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

	      var message = ("{!! trans('backpack/pro::trash.bulk_restore_confirm') !!}").replace(":number", checkedItems.length);
	      var button = $(this);

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
				  text: "{!! trans('backpack/pro::trash.restore') !!}",
				  value: true,
				  visible: true,
				  className: "bg-info",
				}
			  },
			}).then((value) => {
				if (value) {
					var ajax_calls = [];
					var delete_route = "{{ url($crud->route) }}/bulk-restore";

					// submit an AJAX delete call
					$.ajax({
						url: delete_route,
						type: 'POST',
						data: { entries: checkedItems },
						success: function(result) {
							if (Array.isArray(result)) {
							  // Show a success notification bubble
							  new Noty({
							    type: "success",
							    text: "<strong>{!! trans('backpack/pro::trash.bulk_restore_sucess_title') !!}</strong><br>"+checkedItems.length+"{!! trans('backpack/pro::trash.bulk_restore_sucess_message') !!}"
							  }).show();
							} else {
							  // if the result is an array, it means
							  // we have notification bubbles to show
								  if (result instanceof Object) {
								  	// trigger one or more bubble notifications
								  	Object.entries(result).forEach(function(entry, index) {
								  	  var type = entry[0];
								  	  entry[1].forEach(function(message, i) {
								      	  new Noty({
								            type: type,
								            text: message
								          }).show();
								  	  });
								  	});
								  } else {
								  	// Show a warning notification bubble
									new Noty({
										type: "warning",
										text: "<strong>{!! trans('backpack/pro::trash.bulk_restore_error_title') !!}</strong><br>{!! trans('backpack/pro::trash.bulk_restore_error_message') !!}"
									}).show();
								  }
							}

							// Move to previous page in case of deleting all the items in table
							if(table.rows().count() === checkedItems.length) {
								table.page("previous");
							}

							tableConfig.checkedItems = [];
							table.draw(false);
						},
						error: function(result) {
							// Show an alert with the result
							new Noty({
								type: "warning",
								text: "<strong>{!! trans('backpack/pro::trash.bulk_restore_error_title') !!}</strong><br>{!! trans('backpack/pro::trash.bulk_restore_error_message') !!}"
							}).show();
						}
					});
				}
			});
      }
	}
</script>
@endpush
