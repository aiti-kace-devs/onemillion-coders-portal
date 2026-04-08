<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Helpers\CourseVisibilityHelper;
use App\Helpers\DashboardWidgetHelper;
use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Facades\Cache;

class DashboardUserGenderPieChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->height(250);

        // User types to chart
        $UserTypes = ['male', 'female'];

        // Optional: Custom colors for each User type
        $colors = [
            'male'    => 'rgba(54, 162, 235, 0.6)',
            'female'    => 'rgba(255, 99, 132, 0.6)',
        ];

        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        $cacheKey = 'chart_gender_distribution_' . DashboardWidgetHelper::scopeCacheKeySuffix($visibleCourseIds);

        $payload = Cache::flexible($cacheKey, \cache_flexible_ttl(), function () use ($UserTypes, $visibleCourseIds) {
            $labels = [];
            $counts = [];

            foreach ($UserTypes as $type) {
                $labels[] = ucfirst($type);
                $query = User::query()->where('gender', $type);
                DashboardWidgetHelper::applyCourseScope($query, $visibleCourseIds, 'registered_course');
                $counts[] = $query->count();
            }

            return compact('labels', 'counts');
        });

        $labels = $payload['labels'];
        $counts = $payload['counts'];

        $this->chart->labels($labels);

        $this->chart->dataset('User Gender Distribution', 'pie', $counts)
            ->backgroundColor(array_values($colors))
            ->color(['#ffffff', '#ffffff', '#ffffff', '#ffffff']);
    }
}
