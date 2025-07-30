<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\Widget;
use App\Models\User;
use App\Models\Programme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardWidgetHelper
{
    /**
     * Render a widget with the given type and parameters.
     *
     * @param string $type
     * @param array $params
     * @return string
     */


    public static function dashboardCountStatisticsWidget()
    {
        $dasboardCountStatistics = Cache::flexible('dashboard_count_statistics', [(60 * 60), 10], function () {
            return [
                'userCount' => User::count(),
                'shortlistedUsers' => User::where('shortlist', 1)->count(),
                'admittedUsers' => User::whereExists(function ($query) {
                    $query->select(\DB::raw(1))
                        ->from('user_admission')
                        ->whereColumn('user_admission.user_id', 'users.userId')
                        ->whereNotNull('user_admission.confirmed');
                })->count(),
                'courses' => Programme::count(),
            ];
        });

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                    [
                        'type' => 'progress_white',
                        'description' => 'Total Users',
                        'value' => number_format($dasboardCountStatistics['userCount']),
                        'progressClass' => 'progress-bar bg-primary',
                        'wrapper' => [
                            'style' => 'background-color:rgb(40, 127, 167);',
                        ],
                        'hint' => 'All registered users.',
                        'permission' => 'dashboard.read.all ',
                    ],
                    [
                        'type' => 'progress_white',
                        'description' => 'Total Shortlisted Students',
                        'value' => number_format($dasboardCountStatistics['shortlistedUsers']),
                        'progressClass' => 'progress-bar bg-warning',
                        'wrapper' => [
                            'style' => 'background-color:rgb(40, 127, 167);',
                        ],
                        'hint' => 'All Shortlisted Students.',
                        'permission' => 'dashboard.read.all ',
                    ],
                    [
                        'type' => 'progress_white',
                        'description' => 'Total Admitted Students',
                        'value' => number_format($dasboardCountStatistics['admittedUsers']),
                        'progressClass' => 'progress-bar bg-dark',
                        'wrapper' => [
                            'style' => 'background-color:rgb(40, 127, 167);',
                        ],
                        'hint' => 'All Admitted Students',
                        'permission' => 'dashboard.read.all ',
                    ],
                    [
                        'type' => 'progress_white',
                        'description' => 'Total Courses',
                        'value' => number_format($dasboardCountStatistics['courses']),
                        'progressClass' => 'bg-success',
                        'wrapper' => [
                            'style' => 'background-color:rgb(40, 127, 167);',
                        ],
                        'hint' => 'All Courses in the system',
                        'permission' => 'dashboard.read.all ',
                    ],
            ]
        ]);
    }


    public static function dashboardUserGenderStatisticsWidget()
    {
        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'icon' => 'la la-home',
            'link' => backpack_url('dashboard'),
            'content' => [
                [

                    'type'       => 'chart',
                    'controller' => \App\Http\Controllers\Admin\Charts\DashboardUsersLineChartController::class,
                    'class'      => 'card mb-2',
                    'wrapper'    => ['class' => 'col-md-6'],
                    'content'    => [
                        'header' => 'Registered Students For The Past 7 Days',
                        'body'   => 'Line Chart showing Entries.',
                    ]
                ],

                [
                    'type'       => 'chart',
                    'controller' => \App\Http\Controllers\Admin\Charts\DashboardUserGenderPieChartController::class,
                    'class'      => 'card mb-2',
                    'wrapper'    => ['class' => 'col-md-6'], // was col-md-12
                    'content'    => [
                        'header' => 'Gender Distribution',
                        'body'   => 'Pie chart showing Gender Distribution',
                    ]
                ],

            ],

        ]);
    }


    public static function dashboardBatchStatisticsWidget()
    {
        $dashboardTableStatistics = Cache::flexible('dashboard_table_statistics', [(60 * 60), 10], function () {
            return [
                'topAdmissionBatch' => DB::table('admission_batches as ab')
                    ->leftJoin('user_admission as ua', function ($join) {
                        $join->on('ab.id', '=', 'ua.batch_id')
                            ->whereNotNull('ua.confirmed');
                    })
                    ->select(
                        'ab.id',
                        'ab.title',
                        'ab.year',
                        'ab.completed',
                        DB::raw('COUNT(ua.id) as admitted_students_count')
                    )
                    ->groupBy('ab.id', 'ab.title', 'ab.year', 'ab.completed')
                    ->orderByDesc('admitted_students_count')
                    ->limit(5)
                    ->get(),

                'topAdmittedRegion' => DB::table('user_admission as ua')
                    ->join('courses as c', 'ua.course_id', '=', 'c.id')
                    ->leftJoin('centres as ce', 'c.centre_id', '=', 'ce.id')
                    ->leftJoin('branches as br', 'ce.branch_id', '=', 'br.id')
                    ->whereNotNull('ua.confirmed')
                    ->select(
                        DB::raw("COALESCE(br.title, 'Unknown') as region_name"),
                        DB::raw('COUNT(ua.id) as admitted_students_count')
                    )
                    ->groupBy('region_name')
                    ->orderByDesc('admitted_students_count')
                    ->limit(20)
                    ->get(),
            ];
        });

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type'    => 'view',
                    'view'    => 'vendor.backpack.widgets.batch_table',
                    'wrapper' => ['class' => 'col-md-6'],
                    'data'    => [
                        'batches' => $dashboardTableStatistics['topAdmissionBatch'],
                    ],
                ],
                [
                    'type'    => 'view',
                    'view'    => 'vendor.backpack.widgets.admitted_student_per_region',
                    'wrapper' => ['class' => 'col-md-6'],
                    'data'    => [
                        'regions' => $dashboardTableStatistics['topAdmittedRegion'],
                    ],
                ],
            ],
        ]);
    }





    public static function dashboardStudentStatisticsWidget()
    {
        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'icon' => 'la la-home',
            'link' => backpack_url('dashboard'),
            'content' => [
                [
                    'type'       => 'chart',
                    'controller' => \App\Http\Controllers\Admin\Charts\DashboardStudentRegistrationBarChartController::class,
                    'class'      => 'card mb-2',
                    'wrapper'    => ['class' => 'col-md-6'],
                    'content'    => [
                        'header' => 'Students Registration per Region',
                        'body'   => 'Bar chart showing number of registered students per region.',
                    ]
                ],

                [
                    'type'       => 'chart',
                    'controller' => \App\Http\Controllers\Admin\Charts\DashboardAgeGroupBarChartController::class,
                    'class'      => 'card mb-2',
                    'wrapper'    => ['class' => 'col-md-6'],
                    'content'    => [
                        'header' => 'Age Group Distribution',
                        'body'   => 'Bar chart showing student counts by age group.',
                    ]
                ],
            ],
        ]);
    }




}
