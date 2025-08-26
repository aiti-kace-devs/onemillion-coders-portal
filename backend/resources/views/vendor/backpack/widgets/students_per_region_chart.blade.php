<div class="card h-100">
    <div class="card-header">Students per Region</div>
    <div class="card-body">
        <canvas id="studentsPerRegionChart" height="220"></canvas>
    </div>
</div>

@php
    /** @var \Illuminate\Support\Collection $regions */
    $regions = ($widget['data']['regions'] ?? collect())->values();

    $labels = $regions->pluck('region_name')->map(function ($v) {
        return $v ?? 'Unknown';
    });

    $data = $regions->pluck('admitted_students_count')->map(fn($v) => (int) $v);

    // Optional: generate a few repeating colors to make bars distinct
    $colors = collect([
        'rgba(99, 102, 241, 0.6)',   // indigo
        'rgba(16, 185, 129, 0.6)',   // emerald
        'rgba(244, 63, 94, 0.6)',    // rose
        'rgba(234, 179, 8, 0.6)',    // amber
        'rgba(59, 130, 246, 0.6)',   // blue
        'rgba(139, 92, 246, 0.6)',   // violet
        'rgba(34, 197, 94, 0.6)',    // green
        'rgba(251, 146, 60, 0.6)',   // orange
    ]);

    $barColors = $labels->keys()->map(fn($i) => $colors[$i % $colors->count()]);
@endphp

{{-- Load Chart.js if it’s not already present --}}
<script>
    (function loadChartJsOnce(cb){
        if (window.Chart) return cb();
        var s = document.createElement('script');
        s.src = "https://cdn.jsdelivr.net/npm/chart.js";
        s.onload = cb;
        document.head.appendChild(s);
    })(function initStudentsPerRegionChart() {
        var ctx = document.getElementById('studentsPerRegionChart').getContext('2d');

        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($labels),
                datasets: [{
                    label: 'Number of Students',
                    data: @json($data),
                    backgroundColor: @json($barColors),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true },
                    tooltip: { enabled: true }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });

        // (Optional) click a bar to open the Users list filtered by region
        document.getElementById('studentsPerRegionChart').onclick = function(evt){
            var points = chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
            if (points.length) {
                var firstPoint = points[0];
                var label = chart.data.labels[firstPoint.index];
                var url = @json(url('/admin/user?admission_location=')) + encodeURIComponent(label);
                window.location.href = url;
            }
        };
    });
</script>
