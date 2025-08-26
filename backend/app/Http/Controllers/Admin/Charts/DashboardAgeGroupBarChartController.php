<?php

namespace App\Http\Controllers\Admin\Charts;

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

        // Cache for 1 hour (fallback 10s)
        $payload = Cache::flexible('chart_age_groups_dynamic_decades', [(60 * 60), 10], function () {
            // Handle all types of dashes, hyphens, and plus signs
            $rows = DB::table('users')
                ->selectRaw("
                    CASE 
                        WHEN age IS NULL OR age = '' THEN 'Unknown'
                        WHEN age LIKE '%-%' OR age LIKE '%–%' OR age LIKE '%—%' THEN age
                        WHEN age LIKE '%+%' THEN age
                        WHEN age REGEXP '^[0-9]+$' THEN 
                            CONCAT(
                                FLOOR(CAST(age AS UNSIGNED) / 10) * 10, 
                                '-', 
                                FLOOR(CAST(age AS UNSIGNED) / 10) * 10 + 9
                            )
                        ELSE 'Unknown'
                    END AS age_range,
                    COUNT(*) AS total,
                    CASE 
                        WHEN age IS NULL OR age = '' THEN 9999
                        WHEN age LIKE '%-%' OR age LIKE '%–%' OR age LIKE '%—%' THEN 
                            CAST(SUBSTRING_INDEX(age, '-', 1) AS UNSIGNED)
                        WHEN age LIKE '%+%' THEN 
                            CAST(SUBSTRING_INDEX(age, '+', 1) AS UNSIGNED)
                        WHEN age REGEXP '^[0-9]+$' THEN 
                            FLOOR(CAST(age AS UNSIGNED) / 10)
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
