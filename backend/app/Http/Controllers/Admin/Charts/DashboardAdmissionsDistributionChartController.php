<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Helpers\CourseVisibilityHelper;
use App\Helpers\DashboardWidgetHelper;
use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardAdmissionsDistributionChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->height(260);

        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        $cacheKey = 'chart_admissions_distribution_' . DashboardWidgetHelper::scopeCacheKeySuffix($visibleCourseIds);

        $payload = Cache::flexible($cacheKey, \cache_flexible_ttl(), function () use ($visibleCourseIds) {
            $query = DB::table('user_admission as ua');

            if (is_array($visibleCourseIds)) {
                if (empty($visibleCourseIds)) {
                    return [
                        'confirmed' => 0,
                        'pending' => 0,
                    ];
                }

                $query->whereIn('ua.course_id', $visibleCourseIds);
            }

            $agg = $query->selectRaw(
                '
                SUM(CASE WHEN ua.confirmed IS NOT NULL THEN 1 ELSE 0 END) as confirmed_count,
                SUM(CASE WHEN ua.confirmed IS NULL THEN 1 ELSE 0 END) as pending_count
            ',
            )->first();

            return [
                'confirmed' => (int) ($agg->confirmed_count ?? 0),
                'pending' => (int) ($agg->pending_count ?? 0),
            ];
        });

        $labels = ['Confirmed', 'Pending'];
        $data = [$payload['confirmed'], $payload['pending']];

        $this->chart->labels($labels);

        $this->chart
            ->dataset('Admissions', 'doughnut', $data)
            ->backgroundColor([
                'rgba(34,197,94,0.7)',
                'rgba(250,204,21,0.7)',
            ])
            ->color(['#ffffff', '#ffffff'])
            ->options([
                'responsive' => true,
                'plugins' => [
                    'legend' => ['display' => true],
                    'tooltip' => ['enabled' => true],
                ],
            ]);
    }
}
