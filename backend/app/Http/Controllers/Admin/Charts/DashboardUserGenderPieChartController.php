<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Models\User;
use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;

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

        $labels = [];
        $counts = [];

        foreach ($UserTypes as $type) {
            $labels[] = ucfirst($type);
            $counts[] = User::where('gender', $type)->count();
        }

        $this->chart->labels($labels);

        $this->chart->dataset('User Gender Distribution', 'pie', $counts)
            ->backgroundColor(array_values($colors))
            ->color(['#ffffff', '#ffffff', '#ffffff', '#ffffff']);
    }
}
