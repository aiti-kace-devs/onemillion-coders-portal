<div class="card h-100">
    <div class="card-header">Top 5 Batches by Admission</div>
    <div class="card-body table-responsive">
        @php
            $batches = $widget['data']['batches'] ?? [];
            $counter = 1;
            $totalAdmitted = $batches->sum('admitted_students_count');
        @endphp

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>BATCH TITLE - YEAR</th>
                    <th>ADMITTED STUDENTS</th>
                    <th>COMPLETED STATUS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td>{{ $batch->title }} - {{ $batch->year }}</td>
                        <td>
                            @if($batch->admitted_students_count > 0)
                                <a href="{{ url("/admin/user?batch_id={$batch->id}&confirmed_admission=1") }}">
                                    {{ $batch->admitted_students_count }}
                                </a>
                            @endif
                        </td>

                        <td>
                            {{ $batch->completed ? 'Completed' : 'Ongoing' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No batches found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end text-center">Total</th>
                    <th>{{ $totalAdmitted }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
