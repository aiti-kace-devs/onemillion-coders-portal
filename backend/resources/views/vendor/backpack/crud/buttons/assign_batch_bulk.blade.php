@if (backpack_user()->can('update'))
    <div class="btn-group me-2" role="group" style="vertical-align: middle;">
        <select id="batch_selector" class="form-select" style="width:auto; display:inline-block; min-width:160px; height:38px;">
            <option value="">-- Select Batch --</option>
            @foreach(\App\Models\Batch::orderBy('title')->get() as $batch)
                <option value="{{ $batch->id }}">{{ $batch->title }} ({{ $batch->year }})</option>
            @endforeach
        </select>
        <button class="btn btn-primary" id="assignBatchBtn" onclick="assignSelectedToBatch()" style="margin-left:4px;">
            <i class="la la-random"></i> Assign to Batch
        </button>
    </div>
@endif

@push('after_scripts')
{{-- SweetAlert2 fallback --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function getSelectedStudentIds() {
    // Use Backpack's internal checkedItems tracking
    if (typeof crud !== 'undefined' && crud.checkedItems && crud.checkedItems.length > 0) {
        return crud.checkedItems;
    }
    // Fallback: Get from checkbox data attributes
    let checkboxes = $(".crud_bulk_actions_line_checkbox:checked");
    let selectedIds = [];
    checkboxes.each(function() {
        let primaryKeyValue = $(this).data('primary-key-value');
        if (primaryKeyValue) {
            selectedIds.push(primaryKeyValue);
        }
    });
    return selectedIds;
}

function assignSelectedToBatch() {
    var btn = $('#assignBatchBtn');
    var batch_id = $('#batch_selector').val();
    var batch_name = $('#batch_selector option:selected').text();
    var ids = getSelectedStudentIds();

    if (ids.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'No students selected',
                text: 'Please select at least one student to assign to a batch.'
            });
        } else {
            alert('Please select at least one student to assign to a batch.');
        }
        return;
    }
    if (!batch_id) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'No batch selected',
                text: 'Please select a batch.'
            });
        } else {
            alert('Please select a batch.');
        }
        return;
    }

    // Show confirmation dialog with the number of students and batch name
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'question',
            title: 'Assign Students to Batch?',
            html: `You are about to assign <b>${ids.length}</b> student${ids.length > 1 ? 's' : ''} to the batch <b>${batch_name}</b>. Continue?`,
            showCancelButton: true,
            confirmButtonText: 'Yes, assign them',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                doAssignBatch(btn, ids, batch_id);
            }
        });
    } else {
        if (confirm(`You are about to assign ${ids.length} student${ids.length > 1 ? 's' : ''} to the batch ${batch_name}. Continue?`)) {
            doAssignBatch(btn, ids, batch_id);
        }
    }
}

function doAssignBatch(btn, ids, batch_id) {
    btn.prop('disabled', true);
    $.ajax({
        url: '{{ url('admin/user/assign-batch') }}',
        method: 'POST',
        data: {
            student_ids: ids,
            batch_id: batch_id,
            _token: '{{ csrf_token() }}'
        },
        success: function(result) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Batch assigned successfully'
                }).then(() => crud.table.ajax.reload());
            } else {
                new Noty({ type: "success", text: "Batch assigned successfully" }).show();
                crud.table.ajax.reload();
            }
        },
        error: function(err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.responseJSON?.message || 'Error assigning batch'
                });
            } else {
                new Noty({ type: "error", text: "Error assigning batch" }).show();
            }
        },
        complete: function() {
            btn.prop('disabled', false);
        }
    });
}
</script>
@endpush
