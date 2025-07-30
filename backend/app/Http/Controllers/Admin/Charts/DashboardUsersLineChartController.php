<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Models\User;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Backpack\CRUD\app\Http\Controllers\ChartController;
use App\Helpers\WidgetHelper;
use Illuminate\Support\Facades\Cache;


class DashboardUsersLineChartController extends ChartController
{
    public function setup()
    {
        $this->chart = new Chart();
        $this->chart->height(250);

        $userStats = Cache::flexible('chart_user_count_last_8_days', [(60 * 60), 10], function () {
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

                $users[] = User::whereDate('created_at', $date)->count();
            }

            return compact('labels', 'users');
        });

        $this->chart->labels($userStats['labels']);

        $this->chart->dataset('Users', 'line', $userStats['users'])
            ->color('rgb(77, 189, 116)')
            ->backgroundColor('rgba(77, 189, 116, 0.4)');
    }
}
