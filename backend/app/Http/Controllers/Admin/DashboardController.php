<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\DashboardWidgetHelper;
use Backpack\CRUD\app\Http\Controllers\AdminController;


class DashboardController extends AdminController
{


    public function dashboard()
    {

        $this->middleware('permission:dashboard.read.all', ['only' => ['index', 'show']]);

        DashboardWidgetHelper::maintenanceAlertWidget();

        DashboardWidgetHelper::dashboardCountStatisticsWidget();

        DashboardWidgetHelper::dashboardUserGenderStatisticsWidget();

        DashboardWidgetHelper::dashboardBatchStatisticsWidget();

        DashboardWidgetHelper::dashboardStudentStatisticsWidget();

        return view(backpack_view('dashboard'));
    }
}
