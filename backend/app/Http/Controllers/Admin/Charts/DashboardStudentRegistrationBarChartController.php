<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Helpers\CourseVisibilityHelper;
use App\Helpers\DashboardWidgetHelper;
use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStudentRegistrationBarChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->height(260);

        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        $cacheKey = 'chart_registrations_per_region_' . DashboardWidgetHelper::scopeCacheKeySuffix($visibleCourseIds);

        // Cache for 1 hour (with a quick fallback)
        $payload = Cache::flexible($cacheKey, [(60 * 60), 10], function () use ($visibleCourseIds) {
            $rows = DB::table('users as u')
                ->leftJoin('courses as c', 'u.registered_course', '=', 'c.id')
                ->leftJoin('centres as ce', 'c.centre_id', '=', 'ce.id')
                ->leftJoin('branches as br', 'ce.branch_id', '=', 'br.id')
                ->when(is_array($visibleCourseIds), function ($query) use ($visibleCourseIds) {
                    if (empty($visibleCourseIds)) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereIn('u.registered_course', $visibleCourseIds);
                })
                ->selectRaw("COALESCE(br.title, 'Unknown') as region_name, COUNT(u.id) as students_count")
                ->groupBy('region_name')
                ->orderByDesc('students_count')
                ->limit(20)
                ->get();

            return [
                'labels' => $rows->pluck('region_name')->values(),
                'data'   => $rows->pluck('students_count')->map(fn ($v) => (int) $v)->values(),
            ];
        });

        $labels = $payload['labels']->toArray();
        $data   = $payload['data']->toArray();

        $this->chart->labels($labels);

        // Colors (cycled)
        $palette = [
            'rgba(99,102,241,0.6)',   // indigo
            'rgba(16,185,129,0.6)',   // emerald
            'rgba(244,63,94,0.6)',    // rose
            'rgba(234,179,8,0.6)',    // amber
            'rgba(59,130,246,0.6)',   // blue
            'rgba(139,92,246,0.6)',   // violet
            'rgba(34,197,94,0.6)',    // green
            'rgba(251,146,60,0.6)',   // orange
        ];
        $barColors = array_map(fn ($i) => $palette[$i % count($palette)], array_keys($labels));

        $this->chart
            ->dataset('Number of Students', 'bar', $data)
            ->backgroundColor($barColors)
            ->color($barColors)
            ->options([
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => true],
                    'tooltip' => ['enabled' => true],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                    ],
                ],
            ]);
    }
}
