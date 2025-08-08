<div class="card h-100">
    <div class="card-header">Admitted Students per Region</div>
    <div class="card-body table-responsive">
        @php
            /** @var \Illuminate\Support\Collection $regions */
            $regions = $widget['data']['regions'] ?? collect();
            $counter = 1;
            $totalAdmitted = max(1, $regions->sum('admitted_students_count')); // avoid divide-by-zero
        @endphp

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>REGION NAME</th>
                    <th>ADMITTED STUDENTS</th>
                    <th>SHARE OF TOTAL</th> {{-- useful extra column --}}
                </tr>
            </thead>
            <tbody>
                @forelse($regions as $region)
                    @php
                        $count = (int) ($region->admitted_students_count ?? 0);
                        $share = round(($count / $totalAdmitted) * 100, 1);
                        $regionParam = urlencode($region->region_name);
                        $url = url("/admin/user?admission_location={$regionParam}");
                    @endphp
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td>{{ $region->region_name }}</td>
                        <td>
                            <a href="{{ $url }}">{{ $count }}</a>
                        </td>
                        <td>{{ $share }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No regions found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end text-center">Total</th>
                    <th>{{ $regions->sum('admitted_students_count') }}</th>
                    <th>100%</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
