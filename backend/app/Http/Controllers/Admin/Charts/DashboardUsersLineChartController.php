<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Helpers\CourseVisibilityHelper;
use App\Helpers\DashboardWidgetHelper;
use App\Models\User;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Backpack\CRUD\app\Http\Controllers\ChartController;
use Illuminate\Support\Facades\Cache;


class DashboardUsersLineChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->height(250);

        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        $cacheKey = 'chart_user_count_last_8_days_' . DashboardWidgetHelper::scopeCacheKeySuffix($visibleCourseIds);

        $userStats = Cache::flexible($cacheKey, [(60 * 60), 10], function () use ($visibleCourseIds) {
            $users = [];
            $labels = [];

            for ($i = 7; $i >= 0; $i--) {
                $date = today()->subDays($i)->toDateString();

                if ($i === 0) {
                    $labels[] = 'Today';
                } elseif ($i === 1) {
                    $labels[] = 'Yesterday';
                } else {
                    $labels[] = $i . ' days ago';
                }

                $query = User::query()->whereDate('created_at', $date);
                DashboardWidgetHelper::applyCourseScope($query, $visibleCourseIds, 'registered_course');
                $users[] = $query->count();
            }

            return compact('labels', 'users');
        });

        $this->chart->labels($userStats['labels']);

        $this->chart->dataset('Users', 'line', $userStats['users'])
            ->color('rgb(77, 189, 116)')
            ->backgroundColor('rgba(77, 189, 116, 0.4)');
    }
}
