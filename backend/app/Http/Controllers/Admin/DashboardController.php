<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\WidgetHelper;
use App\Helpers\DashboardWidgetHelper;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Backpack\CRUD\app\Http\Controllers\AdminController;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DashboardController extends AdminController
{


    public function dashboard()
    {

        $this->middleware('permission:dashboard.read.all', ['only' => ['index', 'show']]);

        DashboardWidgetHelper::dashboardCountStatisticsWidget();

        DashboardWidgetHelper::dashboardUserGenderStatisticsWidget();

        DashboardWidgetHelper::dashboardBatchStatisticsWidget();

        DashboardWidgetHelper::dashboardStudentStatisticsWidget();

        //  DashboardWidgetHelper::dashboardStudentAgeGroupStatisticsWidget();

        // DashboardWidgetHelper::dashboardArticleAndCatgoryChartStatisticsWidget();

        // DashboardWidgetHelper::dashboardMediaChartStatisticsWidget();

        return view(backpack_view('dashboard'));
    }




    // public function submitText(Request $request)
    // {
    //     $command = $request->input('command');

    //     try {
    //         Artisan::call($command);
    //         $output = Artisan::output();

    //         // Log::info("Artisan command '$command' executed successfully.");
    //         // Log::info("Output:\n" . $output);
    //     } catch (\Exception $e) {
    //         // Log::error("Failed to run Artisan command '$command': " . $e->getMessage());
    //     }

    //     return redirect()->back();
    // }
}
