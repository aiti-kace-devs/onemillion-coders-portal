@extends(backpack_view('blank'))

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">{!! $user->name !!}</span>
            <small>Activities</small>
            <small><a href="{{ backpack_url('user') }}" class="d-print-none font-sm"><i class="la la-angle-double-{{ config('backpack.base.html_direction') == 'rtl' ? 'right' : 'left' }}"></i> {{ trans('backpack::crud.back_to_all') }} <span>Users</span></a></small>
        </h2>
    </section>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <strong>User Activities</strong>
            </div>
            <div class="card-body p-0">
                <table id="crudTable" class="bg-white table table-striped table-hover rounded shadow-xs border-xs mt-2" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th data-orderable="true">Date</th>
                            <th data-orderable="false">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td>{{ $activity->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>{{ $activity->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('after_styles')
    @basset('https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css')
    @basset("https://cdn.datatables.net/responsive/3.0.3/css/responsive.dataTables.min.css")
    @basset('https://cdn.datatables.net/fixedheader/4.0.1/css/fixedHeader.dataTables.min.css')
    <style>
        table#crudTable thead th {
            vertical-align: middle;
        }
    </style>
@endsection

@section('after_scripts')
    @basset("https://cdn.datatables.net/2.1.8/js/dataTables.min.js")
    @basset("https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js")
    @basset("https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js")
    @basset('https://cdn.datatables.net/fixedheader/4.0.1/js/dataTables.fixedHeader.min.js')

    <script>
        $(document).ready(function() {
            var table = $('#crudTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "order": [[ 0, "desc" ]],
                "responsive": true,
                "language": {
                    "emptyTable": "No activities found for this user.",
                    "paginate": {
                        "first": "<i class='la la-angle-double-left'></i>",
                        "previous": "<i class='la la-angle-left'></i>",
                        "next": "<i class='la la-angle-right'></i>",
                        "last": "<i class='la la-angle-double-right'></i>"
                    }
                }
            });
        });
    </script>
@endsection
