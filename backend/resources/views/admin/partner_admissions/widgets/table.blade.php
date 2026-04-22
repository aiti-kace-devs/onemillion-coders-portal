<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <strong><i class="la la-list"></i> Programme Enrollment Status</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0" id="admissionsTable">
                    <thead>
                        <tr>
                            <th>Programme</th>
                            <th>Partner</th>
                            <th class="text-center">Enrolled</th>
                            <th class="text-center">Awaiting</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $tableStats = $widget['data']['stats'] ?? collect(); @endphp
                        @foreach($tableStats as $row)
                            <tr>
                                <td>{{ $row->title }}</td>
                                <td>{{ $row->partner_name }}</td>
                                <td class="text-center">
                                    <span class="badge badge-success" style="font-size: 0.9rem;">{{ number_format($row->enrolled_count) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $row->awaiting_count > 0 ? 'warning' : 'secondary shadow-none' }}" style="font-size: 0.9rem;">
                                        {{ number_format($row->awaiting_count) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    @if($row->awaiting_count > 0)
                                        <form action="{{ url(config('backpack.base.route_prefix') . '/partner-admissions/' . $row->id . '/enrol-programme') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Enrol awaiting students for this programme">
                                                <i class="la la-user-plus"></i> Enrol Awaiting
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary disabled">
                                            <i class="la la-check"></i> All Enrolled
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('after_styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@push('after_scripts')
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (!$.fn.DataTable.isDataTable('#admissionsTable')) {
                $('#admissionsTable').DataTable({
                    "destroy": true,
                    "paging": false,
                    "searching": true,
                    "info": false,
                    "columnDefs": [
                        { "orderable": false, "targets": 4 }
                    ]
                });
            }
        });
    </script>
@endpush
