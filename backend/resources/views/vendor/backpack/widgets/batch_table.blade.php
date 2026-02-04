<div class="card">
    <div class="card-header">
        <h3 class="card-title">Top Admission Batches</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Batch Title</th>
                    <th>Year</th>
                    <th class="text-center">Students</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($widget['data']['batches'] as $batch)
                    <tr>
                        <td>{{ $batch->title }}</td>
                        <td>{{ $batch->year }}</td>
                        <td class="text-center">
                            <span class="badge badge-info">{{ number_format($batch->admitted_students_count) }}</span>
                        </td>
                        <td class="text-center">
                            @if ($batch->completed)
                                <span class="badge badge-success">Completed</span>
                            @else
                                <span class="badge badge-warning">Ongoing</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No batches found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
