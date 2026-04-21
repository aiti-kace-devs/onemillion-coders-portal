<?php

namespace App\Http\Controllers\Admin\Charts;

use App\Services\PartnerAdmissionService;
use Backpack\CRUD\app\Http\Controllers\ChartController;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class PartnerEnrollmentByProgrammeChartController extends ChartController
{
    protected PartnerAdmissionService $admissionService;

    public function setup()
    {
        $this->admissionService = app(PartnerAdmissionService::class);
        
        $this->chart = new Chart();
        $this->chart->height(300);

        $stats = $this->admissionService->getEnrollmentStats();

        $labels = $stats->pluck('title')->toArray();
        $data = $stats->pluck('enrolled_count')->toArray();

        $colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
            '#FF9F40', '#8e5ea2', '#3e95cd', '#3cba9f', '#c45850',
            '#e8c3b9', '#7e7e7e', '#4169E1', '#32CD32', '#FFD700'
        ];
        
        $backgroundColors = [];
        for ($i = 0; $i < count($labels); $i++) {
            $backgroundColors[] = $colors[$i % count($colors)];
        }

        if (count($data) === 0 || array_sum($data) == 0) {
            $labels = ['No Enrollments Yet'];
            $data = [1];
            $backgroundColors = ['#e0e0e0'];
        }

        $this->chart->labels($labels);

        $this->chart->dataset('Enrolled Students', 'pie', $data)
            ->backgroundColor($backgroundColors)
            ->color(['#ffffff']);

        $this->chart->options([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'legend' => [
                'display' => true,
                'position' => 'bottom',
            ],
        ]);
    }
}
