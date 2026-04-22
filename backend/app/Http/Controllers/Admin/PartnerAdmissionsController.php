<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Charts\PartnerEnrollmentByPartnerChartController;
use App\Http\Controllers\Admin\Charts\PartnerEnrollmentByProgrammeChartController;
use App\Http\Controllers\Controller;
use App\Services\PartnerAdmissionService;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\Request;
use Prologue\Alerts\Facades\Alert;

class PartnerAdmissionsController extends Controller
{
    protected PartnerAdmissionService $admissionService;

    public function __construct(PartnerAdmissionService $admissionService)
    {
        $this->admissionService = $admissionService;
    }

    /**
     * Display the Partner Admissions Dashboard.
     */
    public function index()
    {
        $stats = $this->admissionService->getEnrollmentStats();
        $totalAwaiting = $stats->sum('awaiting_count');

        // Add Chart Widgets
        Widget::add([
            'type' => 'div',
            'class' => 'row',
            'content' => [
                [
                    'type'       => 'chart',
                    'controller' => PartnerEnrollmentByProgrammeChartController::class,
                    'wrapper'    => ['class' => 'col-md-6'],
                    'content'    => [
                        'header' => 'Total Enrolled per Programme',
                    ]
                ],
                [
                    'type'       => 'chart',
                    'controller' => PartnerEnrollmentByPartnerChartController::class,
                    'wrapper'    => ['class' => 'col-md-6'],
                    'content'    => [
                        'header' => 'Total Enrolled per Partner',
                    ]
                ],
            ]
        ])->to('before_content');

        // Add Global Actions Widget
        Widget::add([
            'type'    => 'view',
            'view'    => 'admin.partner_admissions.widgets.actions',
            'data'    => [
                'totalAwaiting' => $totalAwaiting,
            ],
        ])->to('before_content');

        // Add Table Widget
        Widget::add([
            'type'    => 'view',
            'view'    => 'admin.partner_admissions.widgets.table',
            'data'    => [
                'stats' => $stats,
            ],
        ])->to('before_content');

        return view('admin.partner_admissions.index', [
            'title' => 'Partner Admissions Dashboard',
        ]);
    }

    /**
     * Enrol ALL awaiting students across all programmes.
     */
    public function enrolAll()
    {
        $count = $this->admissionService->enrolAwaitingStudents();

        if ($count > 0) {
            Alert::success("Enrolment started for {$count} awaiting students across all programmes.")->flash();
        } else {
            Alert::info("No awaiting students found.")->flash();
        }

        return redirect()->back();
    }

    /**
     * Enrol awaiting students for a specific programme.
     */
    public function enrolProgramme($id)
    {
        $count = $this->admissionService->enrolAwaitingStudents((int) $id);

        if ($count > 0) {
            Alert::success("Enrolment started for {$count} awaiting students.")->flash();
        } else {
            Alert::info("No awaiting students found for this programme.")->flash();
        }

        return redirect()->back();
    }

    /**
     * Simple color generator for charts.
     */
    protected function generateColors(int $count): array
    {
        $colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
            '#FF9F40', '#8e5ea2', '#3e95cd', '#3cba9f', '#c45850',
            '#e8c3b9', '#7e7e7e', '#4169E1', '#32CD32', '#FFD700'
        ];

        // If we need more colors than available, just wrap around
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $colors[$i % count($colors)];
        }

        return $result;
    }
}
