<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\Widget;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardWidgetHelper
{
    
    private static function isCentreManager(): bool
    {
        $admin = backpack_user();

        return $admin && method_exists($admin, 'hasRole') && $admin->hasRole('centre-manager');
    }

    /**
     * Render a widget with the given type and parameters.
     *
     * @param string $type
     * @param array $params
     * @return string
     */

    /**
     * Build a stable cache suffix for course-scoped dashboard data.
     */
    public static function scopeCacheKeySuffix(?array $visibleCourseIds): string
    {
        if ($visibleCourseIds === null) {
            return 'all';
        }

        if (empty($visibleCourseIds)) {
            return 'none';
        }

        $ids = array_map('intval', $visibleCourseIds);
        sort($ids);

        return md5(implode(',', $ids));
    }

    /**
     * Apply course visibility scope to a query builder.
     */
    public static function applyCourseScope($query, ?array $visibleCourseIds, string $column): void
    {
        if (! is_array($visibleCourseIds)) {
            return;
        }

        if (empty($visibleCourseIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn($column, $visibleCourseIds);
    }


    public static function dashboardCountStatisticsWidget()
    {
        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        $cacheKey = 'dashboard_count_statistics_' . self::scopeCacheKeySuffix($visibleCourseIds);

        $dasboardCountStatistics = Cache::flexible($cacheKey, \cache_flexible_ttl(), function () use ($visibleCourseIds) {
            $baseUserQuery = User::query();
            self::applyCourseScope($baseUserQuery, $visibleCourseIds, 'registered_course');

            $courseQuery = Course::query();
            self::applyCourseScope($courseQuery, $visibleCourseIds, 'id');

            $scopeSuffix = self::scopeCacheKeySuffix($visibleCourseIds);
            $shortlistedCacheKey = 'shortlistedUsers_' . $scopeSuffix;
            $admittedCacheKey = 'admittedUsers_' . $scopeSuffix;

            $totalCourses = $courseQuery->count();
            if (self::isCentreManager()) {
                if (is_array($visibleCourseIds) && empty($visibleCourseIds)) {
                    $totalCourses = 0;
                } else {
                    $batchCoursesQuery = DB::table('admission_batches as ab')
                        ->join('courses as c2', 'c2.batch_id', '=', 'ab.id')
                        ->where('ab.completed', 0)
                        ->where('ab.status', 1);

                    if (is_array($visibleCourseIds)) {
                        $batchCoursesQuery->whereIn('c2.id', $visibleCourseIds);
                    }

                    $totalCourses = $batchCoursesQuery
                        ->select('ab.id')
                        ->selectRaw('COUNT(DISTINCT c2.programme_id) as courses_count')
                        ->groupBy('ab.id')
                        ->get()
                        ->sum('courses_count');
                }
            }

            return [
                'userCount' => (clone $baseUserQuery)->count(),
                // cache shortlistedUsers
                'shortlistedUsers' => Cache::remember($shortlistedCacheKey, 60 * 60, function () use ($baseUserQuery) {
                    return (clone $baseUserQuery)->where('shortlist', 1)->count();
                }),
                'admittedUsers' => Cache::remember($admittedCacheKey, 60 * 60, function () use ($baseUserQuery) {
                    return (clone $baseUserQuery)->whereExists(function ($query) {
                        $query->select(\DB::raw(1))
                            ->from('user_admission')
                            ->whereColumn('user_admission.user_id', 'users.userId')
                            ->whereNotNull('user_admission.confirmed');
                    })->count();
                }),
                'courses' => $totalCourses,
            ];
        });

        $coursesHint = self::isCentreManager()
            ? 'Courses in active or ongoing batches.'
            : 'All Courses in the system';

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
                    'hint' => $coursesHint,
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
        if (self::isCentreManager()) {
            return;
        }

        $visibleCourseIds = CourseVisibilityHelper::currentAdminVisibleCourseIds();
        $cacheKey = 'dashboard_table_statistics_' . self::scopeCacheKeySuffix($visibleCourseIds);

        $dashboardTableStatistics = Cache::flexible($cacheKey, \cache_flexible_ttl(), function () use ($visibleCourseIds) {
            $topBatches = DB::table('courses as c')
                ->join('admission_batches as ab', 'c.batch_id', '=', 'ab.id')
                ->leftJoin('user_admission as ua', function ($join) {
                    $join->on('c.id', '=', 'ua.course_id')
                        ->whereNotNull('ua.confirmed');
                })
                ->when(is_array($visibleCourseIds), function ($query) use ($visibleCourseIds) {
                    if (empty($visibleCourseIds)) {
                        $query->whereRaw('1 = 0');
                        return;
                    }

                    $query->whereIn('c.id', $visibleCourseIds);
                })
                ->select(
                    'ab.id as batch_id',
                    'ab.title',
                    'ab.year',
                    'ab.completed',
                    DB::raw('COUNT(DISTINCT ua.id) as admitted_students_count'),
                    DB::raw('COUNT(DISTINCT c.programme_id) as courses_count')
                )
                ->groupBy('ab.id', 'ab.title', 'ab.year', 'ab.completed')
                ->orderByDesc('admitted_students_count')
                ->limit(5)
                ->get()
                ->map(function ($batch) {
                    $batch->admitted_students_count = (int) $batch->admitted_students_count;
                    $batch->courses_count = (int) $batch->courses_count;
                    return $batch;
                });

            return [
                'topAdmissionBatch' => $topBatches,
                'topAdmittedRegion' => DB::table('user_admission as ua')
                    ->join('courses as c', 'ua.course_id', '=', 'c.id')
                    ->leftJoin('centres as ce', 'c.centre_id', '=', 'ce.id')
                    ->leftJoin('branches as br', 'ce.branch_id', '=', 'br.id')
                    ->when(is_array($visibleCourseIds), function ($query) use ($visibleCourseIds) {
                        if (empty($visibleCourseIds)) {
                            $query->whereRaw('1 = 0');
                            return;
                        }

                        $query->whereIn('c.id', $visibleCourseIds);
                    })
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
        if (self::isCentreManager()) {
            Widget::add([
                'type' => 'div',
                'class' => 'row mb-4',
                'icon' => 'la la-home',
                'link' => backpack_url('dashboard'),
                'content' => [
                    [
                        'type'       => 'chart',
                        'controller' => \App\Http\Controllers\Admin\Charts\DashboardAdmissionsDistributionChartController::class,
                        'class'      => 'card mb-2',
                        'wrapper'    => ['class' => 'col-md-6'],
                        'content'    => [
                            'header' => 'Admissions Distribution',
                            'body'   => 'Doughnut chart showing confirmed vs pending admissions.',
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
            return;
        }

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
