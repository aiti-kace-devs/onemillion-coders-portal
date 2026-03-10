<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Helpers\CourseVisibilityHelper;
use App\Helpers\DashboardWidgetHelper;
use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardAgeGroupBarChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->height(260);

        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        $cacheKey = 'chart_age_groups_dynamic_decades_' . DashboardWidgetHelper::scopeCacheKeySuffix($visibleCourseIds);

        // Cache for 1 hour (fallback 10s)
        $payload = Cache::flexible($cacheKey, [(60 * 60), 10], function () use ($visibleCourseIds) {
            // Handle all types of dashes, hyphens, and plus signs
            $rows = DB::table('users as u')
                ->when(is_array($visibleCourseIds), function ($query) use ($visibleCourseIds) {
                    if (empty($visibleCourseIds)) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereIn('u.registered_course', $visibleCourseIds);
                })
                ->selectRaw("
                    CASE 
                        WHEN u.age IS NULL OR u.age = '' THEN 'Unknown'
                        WHEN u.age LIKE '%-%' OR u.age LIKE '%–%' OR u.age LIKE '%—%' THEN u.age
                        WHEN u.age LIKE '%+%' THEN u.age
                        WHEN u.age REGEXP '^[0-9]+$' THEN 
                            CONCAT(
                                FLOOR(CAST(u.age AS UNSIGNED) / 10) * 10, 
                                '-', 
                                FLOOR(CAST(u.age AS UNSIGNED) / 10) * 10 + 9
                            )
                        ELSE 'Unknown'
                    END AS age_range,
                    COUNT(*) AS total,
                    CASE 
                        WHEN u.age IS NULL OR u.age = '' THEN 9999
                        WHEN u.age LIKE '%-%' OR u.age LIKE '%–%' OR u.age LIKE '%—%' THEN 
                            CAST(SUBSTRING_INDEX(u.age, '-', 1) AS UNSIGNED)
                        WHEN u.age LIKE '%+%' THEN 
                            CAST(SUBSTRING_INDEX(u.age, '+', 1) AS UNSIGNED)
                        WHEN u.age REGEXP '^[0-9]+$' THEN 
                            FLOOR(CAST(u.age AS UNSIGNED) / 10)
                        ELSE 9999
                    END AS bucket_order
                ")
                ->groupBy('age_range', 'bucket_order')
                ->orderBy('bucket_order')
                ->get();

            return [
                'labels' => $rows->pluck('age_range')->values(),
                'data'   => $rows->pluck('total')->map(fn ($v) => (int) $v)->values(),
            ];
        });

        $labels = $payload['labels']->toArray();
        $data   = $payload['data']->toArray();

        $this->chart->labels($labels);

        // Simple rotating palette
        $palette = [
            'rgba(99,102,241,0.6)',
            'rgba(16,185,129,0.6)',
            'rgba(244,63,94,0.6)',
            'rgba(234,179,8,0.6)',
            'rgba(59,130,246,0.6)',
            'rgba(139,92,246,0.6)',
            'rgba(34,197,94,0.6)',
            'rgba(251,146,60,0.6)',
        ];
        $barColors = array_map(fn ($i) => $palette[$i % count($palette)], array_keys($labels));

        $this->chart
            ->dataset('Number of Students', 'bar', $data)
            ->backgroundColor($barColors)
            ->color($barColors)
            ->options([
                'responsive' => true,
                'indexAxis' => 'y', // horizontal bars
                'plugins' => [
                    'legend' => ['display' => true],
                    'tooltip' => ['enabled' => true],
                ],
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'ticks' => ['precision' => 0],
                    ],
                ],
            ]);
    }
}
