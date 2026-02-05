<div class="card">
    <div class="card-header">
        <h3 class="card-title">Admitted Students per Region</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Region</th>
                    <th class="text-right">Admitted Students</th>
                </tr>
            </thead>
            <tbody>
                @forelse($widget['data']['regions'] as $region)
                    <tr>
                        <td>{{ $region->region_name }}</td>
                        <td class="text-right">
                            <span
                                class="badge badge-primary">{{ number_format($region->admitted_students_count) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
