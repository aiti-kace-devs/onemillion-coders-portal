<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\Widget;
use App\Models\Admin;
use App\Models\AdmissionRejection;
use App\Models\Attendance;
use App\Models\Programme;
use App\Models\Course;
use App\Models\Branch;
use App\Models\District;
use App\Models\Centre;
use App\Models\User;
use App\Models\CourseSession;
use App\Models\OexExamMaster;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\StudentVerification;
use App\Models\OexQuestionMaster;
use App\Models\OexCategory;
use App\Models\CourseCertification;
use App\Models\Batch;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\CourseMatch;
use App\Models\CourseMatchOption;

class WidgetHelper
{
    /**
     * Render a widget with the given type and parameters.
     *
     * @param string $type
     * @param array $params
     * @return string
     */

    public static function adminStatisticsWidget()
    {
        // $totalUsers = Admin::whereHas('roles')->count();
        $totalUsers = Admin::count();
        $totalUsersToday = Admin::whereHas('roles')->whereDate('last_login', now())->count();

        $verifiedUsers = Admin::whereNotNull('email_verified_at')
            ->whereHas('roles')->count();

        $superAdmin = Admin::where('is_super', 1)
            ->whereHas('roles')->count();

        $getPercent = function ($count) use ($totalUsers) {
            return $totalUsers > 0 ? round(($count / $totalUsers) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalUsers),
                    'description' => 'Total Users',
                    'value' => number_format($totalUsers),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered Admins.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($verifiedUsers),
                    'description' => 'Verified Users',
                    'value' => number_format($verifiedUsers),
                    'progressClass' => 'progress-bar bg-primary',
                    'hint' => 'Admins who passed verification.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalUsersToday),
                    'description' => 'Logged In',
                    'value' => number_format($totalUsersToday),
                    'progressClass' => 'progress-bar bg-primary',
                    'hint' => 'Admins who have logged in today',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($superAdmin),
                    'description' => 'Super Admins',
                    'value' => number_format($superAdmin),
                    'progressClass' => 'bg-success',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All Super Admin',
                ],
            ],
        ]);
    }







    public static function admissionRejectionWidgets()
    {
        $topRejectedCourses = AdmissionRejection::select('course_id')
            ->selectRaw('COUNT(*) as total_rejections')
            ->groupBy('course_id')
            ->orderByDesc('total_rejections')
            ->with('course')
            ->take(4)
            ->get();

        $widgets = [];

        foreach ($topRejectedCourses as $rejection) {
            $courseName = optional($rejection->course)->course_name ?? 'Unknown';
            $total = $rejection->total_rejections;

            $widgets[] = [
                'type' => 'progress_white',
                'progress' => 100,
                'description' => $courseName,
                'value' => number_format($total),
                'progressClass' => 'bg-primary',
                'hint' => 'Rejected applications for this course',
            ];
        }

        Widget::add([
            'type' => 'div',
            'class' => 'row',
            'content' => $widgets,
        ]);
    }







    public static function attendanceWidgets()
    {
        $topCoursesToday = Attendance::whereDate('date', today())
            ->select('course_id')
            ->selectRaw('COUNT(*) as total_attendance')
            ->groupBy('course_id')
            ->orderByDesc('total_attendance')
            ->with('course')
            ->take(4)
            ->get();

        $widgets = [];

        foreach ($topCoursesToday as $attendance) {
            $courseName = optional($attendance->course)->course_name ?? 'Unknown';
            $total = $attendance->total_attendance;

            $widgets[] = [
                'type' => 'progress_white',
                'progress' => 100,
                'description' => $courseName,
                'value' => number_format($total),
                'progressClass' => 'bg-primary',
                'hint' => 'Total Attendance for today',
            ];
        }

        if (count($widgets)) {
            Widget::add([
                'type' => 'div',
                'class' => 'row',
                'content' => $widgets,
            ]);
        } else {
            Widget::add([
                'type' => 'alert',
                'class' => 'alert alert-warning',
                'content' => 'No attendance recorded for today.',
            ]);
        }
    }





    public static function categorytatisticsWidget()
    {
        $totalCategories = OexCategory::count();
        $activeCategories = OexCategory::where('status', 1)->count();
        $inactiveCategories = OexCategory::where('status', 0)->count();
        $recentCategories = OexCategory::whereDate('created_at', '>=', now()->subDays(30))->count();

        $getPercent = function ($count) use ($totalCategories) {
            return $totalCategories > 0 ? round(($count / $totalCategories) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalCategories),
                    'description' => 'Total Categories',
                    'value' => number_format($totalCategories),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered Categories.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeCategories),
                    'description' => 'Active Categories',
                    'value' => number_format($activeCategories),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Categories currently active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveCategories),
                    'description' => 'Inactive Categories',
                    'value' => number_format($inactiveCategories),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Categories currently inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($recentCategories),
                    'description' => 'New Categories (30 Days)',
                    'value' => number_format($recentCategories),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Categories added in the last 30 days.',
                ],
            ],
        ]);
    }





    public static function branchStatisticsWidget()
    {
        $totalBranches = Branch::count();
        $activeBranches = Branch::where('status', 1)->count();
        $inactiveBranches = Branch::where('status', 0)->count();
        $recentBranches = Branch::whereDate('created_at', '>=', now()->subDays(30))->count();

        $getPercent = function ($count) use ($totalBranches) {
            return $totalBranches > 0 ? round(($count / $totalBranches) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalBranches),
                    'description' => 'Total Branches',
                    'value' => number_format($totalBranches),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered branches.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeBranches),
                    'description' => 'Active Branches',
                    'value' => number_format($activeBranches),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Branches currently active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveBranches),
                    'description' => 'Inactive Branches',
                    'value' => number_format($inactiveBranches),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Branches currently inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($recentBranches),
                    'description' => 'New Branches (30 Days)',
                    'value' => number_format($recentBranches),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Branches added in the last 30 days.',
                ],
            ],
        ]);
    }






    public static function districtStatisticsWidget(array $statistics = [])
    {
        $totalDistricts = (int) ($statistics['total'] ?? District::count());
        $activeDistricts = (int) ($statistics['active'] ?? District::where('status', 1)->count());
        $inactiveDistricts = (int) ($statistics['inactive'] ?? District::where('status', 0)->count());
        $recentDistricts = (int) ($statistics['recent'] ?? District::whereDate('created_at', '>=', now()->subDays(30))->count());

        $getPercent = function ($count) use ($totalDistricts) {
            return $totalDistricts > 0 ? round(($count / $totalDistricts) * 100) : 0;
        };

        $totalPercent = (int) ($statistics['total_percent'] ?? $getPercent($totalDistricts));
        $activePercent = (int) ($statistics['active_percent'] ?? $getPercent($activeDistricts));
        $inactivePercent = (int) ($statistics['inactive_percent'] ?? $getPercent($inactiveDistricts));
        $recentPercent = (int) ($statistics['recent_percent'] ?? $getPercent($recentDistricts));

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $totalPercent,
                    'description' => 'Total Districts',
                    'value' => number_format($totalDistricts),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'class' => 'col-sm-6 col-lg-3 js-district-widget-total',
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered districts.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $activePercent,
                    'description' => 'Active Districts',
                    'value' => number_format($activeDistricts),
                    'progressClass' => 'progress-bar bg-success',
                    'wrapper' => [
                        'class' => 'col-sm-6 col-lg-3 js-district-widget-active',
                    ],
                    'hint' => 'Districts currently active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $inactivePercent,
                    'description' => 'Inactive Districts',
                    'value' => number_format($inactiveDistricts),
                    'progressClass' => 'progress-bar bg-danger',
                    'wrapper' => [
                        'class' => 'col-sm-6 col-lg-3 js-district-widget-inactive',
                    ],
                    'hint' => 'Districts currently inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $recentPercent,
                    'description' => 'New Districts (30 Days)',
                    'value' => number_format($recentDistricts),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'class' => 'col-sm-6 col-lg-3 js-district-widget-recent',
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Districts added in the last 30 days.',
                ],
            ],
        ]);
    }





    public static function centreStatisticsWidget()
    {
        $totalCenters = Centre::count();
        $activeCenters = Centre::where('status', 1)->count();
        $isReadyCenters = Centre::where('is_ready', 1)->count();
        $isNotReadyCenters = Centre::where('is_ready', null)->count();

        $getPercent = function ($count) use ($totalCenters) {
            return $totalCenters > 0 ? round(($count / $totalCenters) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalCenters),
                    'description' => 'Total Centers',
                    'value' => number_format($totalCenters),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered Centers.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeCenters),
                    'description' => 'Active Centers',
                    'value' => number_format($activeCenters),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Centers currently active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($isReadyCenters),
                    'description' => 'Ready Centers',
                    'value' => number_format($isReadyCenters),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Centers that are ready.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($isNotReadyCenters),
                    'description' => 'Centers Not Ready',
                    'value' => number_format($isNotReadyCenters),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Centers that are not ready.',
                ],
            ],
        ]);
    }





    public static function courseStatisticsWidget()
    {
        $admin = backpack_user();
        $visibleCourseIds = null;

        if ($admin instanceof Admin) {
            $visibleCourseIds = method_exists($admin, 'visibleCourseIds')
                ? $admin->visibleCourseIds()
                : ($admin->isSuper() ? null : $admin->assignedCourseIds());
        }

        $baseQuery = Course::query();

        if (is_array($visibleCourseIds)) {
            if (empty($visibleCourseIds)) {
                $baseQuery->whereRaw('1 = 0');
            } else {
                $baseQuery->whereIn('id', $visibleCourseIds);
            }
        }

        $totalCourses = (clone $baseQuery)->count();
        $activeCourses = (clone $baseQuery)->where('status', 1)->count();
        $inactiveCourses = (clone $baseQuery)->where('status', 0)->count();
        $ongoingCourses = (clone $baseQuery)->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();

        $getPercent = function ($count) use ($totalCourses) {
            return $totalCourses > 0 ? round(($count / $totalCourses) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalCourses),
                    'description' => 'Total Courses',
                    'value' => number_format($totalCourses),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered Courses.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeCourses),
                    'description' => 'Active Courses',
                    'value' => number_format($activeCourses),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Courses marked as active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveCourses),
                    'description' => 'Inactive Courses',
                    'value' => number_format($inactiveCourses),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Courses marked as inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($ongoingCourses),
                    'description' => 'Ongoing Courses',
                    'value' => number_format($ongoingCourses),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Courses currently in session.',
                ],
            ],
        ]);
    }




    public static function courseSessionStatisticsWidget()
    {
        $totalSessions = CourseSession::courseType()->count();
        $activeSessions = CourseSession::courseType()->where('status', 1)->count();
        $inactiveSessions = CourseSession::courseType()->where('status', 0)->count();

        $upcomingSessions = CourseSession::courseType()->where('course_time', '>', now())->count();

        $getPercent = function ($count) use ($totalSessions) {
            return $totalSessions > 0 ? round(($count / $totalSessions) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalSessions),
                    'description' => 'Total Sessions',
                    'value' => number_format($totalSessions),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All course sessions.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeSessions),
                    'description' => 'Active Sessions',
                    'value' => number_format($activeSessions),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Sessions currently marked active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveSessions),
                    'description' => 'Inactive Sessions',
                    'value' => number_format($inactiveSessions),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Sessions marked inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($upcomingSessions),
                    'description' => 'Upcoming Sessions',
                    'value' => number_format($upcomingSessions),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Sessions starting in the future.',
                ],
            ],
        ]);
    }







    public static function courseCategoryStatisticsWidget()
    {
        $totalCourseCategorys = CourseCategory::count();
        $activeCourseCategorys = CourseCategory::where('status', 1)->count();
        $inactiveCourseCategorys = CourseCategory::where('status', 0)->count();
        $recentCourseCategorys = CourseCategory::whereDate('created_at', '>=', now()->subDays(30))->count();

        $getPercent = function ($count) use ($totalCourseCategorys) {
            return $totalCourseCategorys > 0 ? round(($count / $totalCourseCategorys) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalCourseCategorys),
                    'description' => 'Total Course Categories',
                    'value' => number_format($totalCourseCategorys),
                    'progressClass' => 'progress-bar bg-primary',
                    // 'wrapper' => [
                    //         'style' => 'background-color:rgb(40, 127, 167);',
                    // ],
                    'hint' => 'All registered Course Categories'
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeCourseCategorys),
                    'description' => 'Active Course Categories',
                    'value' => number_format($activeCourseCategorys),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Course Categories currently active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveCourseCategorys),
                    'description' => 'Inactive Course Categories',
                    'value' => number_format($inactiveCourseCategorys),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Course Categories currently inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($recentCourseCategorys),
                    'description' => 'New Course Categories (30 Days)',
                    'value' => number_format($recentCourseCategorys),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Categories added in the last 30 days.',
                ],
            ],
        ]);
    }









    public static function courseModuleStatisticsWidget()
    {
        $totalCourseModules = CourseModule::count();
        $activeCourseModules = CourseModule::where('status', 1)->count();
        $inactiveCourseModules = CourseModule::where('status', 0)->count();
        $recentCourseModules = CourseModule::whereDate('created_at', '>=', now()->subDays(30))->count();

        $getPercent = function ($count) use ($totalCourseModules) {
            return $totalCourseModules > 0 ? round(($count / $totalCourseModules) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalCourseModules),
                    'description' => 'Total Course Modules',
                    'value' => number_format($totalCourseModules),
                    'progressClass' => 'progress-bar bg-primary',
                    // 'wrapper' => [
                    //         'style' => 'background-color:rgb(40, 127, 167);',
                    // ],
                    'hint' => 'All registered Course Modules',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeCourseModules),
                    'description' => 'Active Course Modules',
                    'value' => number_format($activeCourseModules),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Course Modules currently active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveCourseModules),
                    'description' => 'Inactive Course Modules',
                    'value' => number_format($inactiveCourseModules),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Course Modules currently inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($recentCourseModules),
                    'description' => 'New Course Modules (30 Days)',
                    'value' => number_format($recentCourseModules),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Modules added in the last 30 days.',
                ],
            ],
        ]);
    }



    public static function programmeStatisticsWidget()
    {
        $totalProgrammes = Programme::count();
        $activeProgrammes = Programme::where('status', 1)->count();
        $inactiveProgrammes = Programme::where('status', 0)->count();
        $ongoingProgrammes = Programme::whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();

        $getPercent = function ($count) use ($totalProgrammes) {
            return $totalProgrammes > 0 ? round(($count / $totalProgrammes) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalProgrammes),
                    'description' => 'Total Programmes',
                    'value' => number_format($totalProgrammes),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered programmes.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeProgrammes),
                    'description' => 'Active Programmes',
                    'value' => number_format($activeProgrammes),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Programmes marked as active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveProgrammes),
                    'description' => 'Inactive Programmes',
                    'value' => number_format($inactiveProgrammes),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Programmes marked as inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($ongoingProgrammes),
                    'description' => 'Ongoing Programmes',
                    'value' => number_format($ongoingProgrammes),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Programmes currently in session.',
                ],
            ],
        ]);
    }





    public static function manageExamStatisticsWidget()
    {
        $totalExams = OexExamMaster::count();
        $activeExams = OexExamMaster::where('status', 1)->count();
        $inactiveExams = OexExamMaster::where('status', 0)->count();
        $ongoingExams = OexExamMaster::whereDate('exam_date', '>=', now())
            ->count();

        $getPercent = function ($count) use ($totalExams) {
            return $totalExams > 0 ? round(($count / $totalExams) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalExams),
                    'description' => 'Total Exams',
                    'value' => number_format($totalExams),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered Exams.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeExams),
                    'description' => 'Active Exams',
                    'value' => number_format($activeExams),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Exams marked as active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveExams),
                    'description' => 'Inactive Exams',
                    'value' => number_format($inactiveExams),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Exams marked as inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($ongoingExams),
                    'description' => 'Ongoing Exams',
                    'value' => number_format($ongoingExams),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Exams currently in session.',
                ],
            ],
        ]);
    }








    public static function manageQuestionStatisticsWidget($examId = null)
    {
        $questionQuery = OexQuestionMaster::query();
        if ($examId) {
            $questionQuery->where('exam_id', $examId);
        }

        $totalQuestions = $questionQuery->count();

        $activeQuestion = (clone $questionQuery)->where('status', 1)->count();
        $inactiveQuestion = (clone $questionQuery)->where('status', 0)->count();
        $questionWithoutAnswers = (clone $questionQuery)->whereNull('ans')->count();



        $getPercent = function ($count) use ($totalQuestions) {
            return $totalQuestions > 0 ? round(($count / $totalQuestions) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalQuestions),
                    'description' => 'Total Questions',
                    'value' => number_format($totalQuestions),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered Questions.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeQuestion),
                    'description' => 'Active Questions',
                    'value' => number_format($activeQuestion),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Questions marked as active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveQuestion),
                    'description' => 'Inactive Questions',
                    'value' => number_format($inactiveQuestion),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Questions marked as inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($questionWithoutAnswers),
                    'description' => 'Questions without Answers',
                    'value' => number_format($questionWithoutAnswers),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Questions without Answers.',
                ],
            ],
        ]);
    }













    public static function userStatisticsWidget()
    {
        $admin = backpack_user();
        $visibleCourseIds = null;

        if ($admin instanceof Admin) {
            $visibleCourseIds = method_exists($admin, 'visibleCourseIds')
                ? $admin->visibleCourseIds()
                : ($admin->isSuper() ? null : $admin->assignedCourseIds());
        }

        $baseQuery = User::query();

        if (is_array($visibleCourseIds)) {
            if (empty($visibleCourseIds)) {
                $baseQuery->whereRaw('1 = 0');
            } else {
                $baseQuery->whereIn('registered_course', $visibleCourseIds);
            }
        }

        $totalUsers = (clone $baseQuery)->count();

        $admittedUsers = (clone $baseQuery)->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('user_admission')
                ->whereColumn('user_admission.user_id', 'users.userId')
                ->whereNotNull('user_admission.confirmed');
        })->count();

        $shortlistedUsers = (clone $baseQuery)->where('shortlist', 1)->count();

        $todaysAdmittedUsers = (clone $baseQuery)->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('user_admission')
                ->whereColumn('user_admission.user_id', 'users.userId')
                ->whereDate('user_admission.confirmed', today());
        })->count();

        $getPercent = function ($count) use ($totalUsers) {
            return $totalUsers > 0 ? round(($count / $totalUsers) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalUsers),
                    'description' => 'Total Students',
                    'value' => number_format($totalUsers),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All registered students in the system.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($admittedUsers),
                    'description' => 'Admitted Students',
                    'value' => number_format($admittedUsers),
                    'progressClass' => 'progress-bar bg-primary',
                    'hint' => 'Students with confirmed admission.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($shortlistedUsers),
                    'description' => 'Shortlisted Students',
                    'value' => number_format($shortlistedUsers),
                    'progressClass' => 'progress-bar bg-primary',
                    'hint' => 'Students marked as shortlisted.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($todaysAdmittedUsers),
                    'description' => "Today's Admitted Students",
                    'value' => number_format($todaysAdmittedUsers),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Students admitted today.',
                ],
            ]
        ]);
    }












    public static function verificationStatisticsWidget()
    {
        $admin = backpack_user();
        $visibleCourseIds = null;

        if ($admin instanceof Admin) {
            $visibleCourseIds = method_exists($admin, 'visibleCourseIds')
                ? $admin->visibleCourseIds()
                : ($admin->isSuper() ? null : $admin->assignedCourseIds());
        }

        $baseQuery = StudentVerification::query();

        if (is_array($visibleCourseIds)) {
            if (empty($visibleCourseIds)) {
                $baseQuery->whereRaw('1 = 0');
            } else {
                $baseQuery->whereIn('registered_course', $visibleCourseIds);
            }
        }

        $totalStudents = (clone $baseQuery)->count();
        $totalVerified = (clone $baseQuery)->whereNotNull('verification_date')->count();
        $unverified = (clone $baseQuery)->whereNull('verification_date')->count();
        $recentlyUpdated = (clone $baseQuery)->whereDate('details_updated_at', '>=', now()->subDays(30))->count();

        $getPercent = function ($count) use ($totalStudents) {
            return $totalStudents > 0 ? round(($count / $totalStudents) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalStudents),
                    'description' => 'Total Students',
                    'value' => number_format($totalStudents),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All Total Students',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalVerified),
                    'description' => 'Total Verified Students',
                    'value' => number_format($totalVerified),
                    'progressClass' => 'progress-bar bg-primary',
                    'hint' => 'All Total Verified Students.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($unverified),
                    'description' => 'Unverified Students',
                    'value' => number_format($unverified),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Students pending verification.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($recentlyUpdated),
                    'description' => 'Recently Updated (30 Days)',
                    'value' => number_format($recentlyUpdated),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Recently Updated (30 Days)',
                ]
            ],
        ]);
    }








    public static function courseCertificationStatisticsWidget()
    {
        $totalCourseCertifications = CourseCertification::count();
        $activeCourseCertifications = CourseCertification::where('status', 1)->count();
        $inactiveCourseCertifications = CourseCertification::where('status', 0)->count();
        $recentCourseCertifications = CourseCertification::whereDate('created_at', '>=', now()->subDays(30))->count();

        $getPercent = function ($count) use ($totalCourseCertifications) {
            return $totalCourseCertifications > 0 ? round(($count / $totalCourseCertifications) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalCourseCertifications),
                    'description' => 'Total Course Certifications',
                    'value' => number_format($totalCourseCertifications),
                    'progressClass' => 'progress-bar bg-primary',
                    // 'wrapper' => [
                    //         'style' => 'background-color:rgb(40, 127, 167);',
                    // ],
                    'hint' => 'All Course Certifications',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeCourseCertifications),
                    'description' => 'Active Course Certifications',
                    'value' => number_format($activeCourseCertifications),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Course Certifications currently active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveCourseCertifications),
                    'description' => 'Inactive Course Certifications',
                    'value' => number_format($inactiveCourseCertifications),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Course Certifications currently inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($recentCourseCertifications),
                    'description' => 'New Course Certifications (30 Days)',
                    'value' => number_format($recentCourseCertifications),
                    'progressClass' => 'progress-bar bg-primary',
                    // 'wrapper' => [
                    //         'style' => 'background-color:rgb(40, 127, 167);',
                    //     ],
                    'hint' => 'Modules added in the last 30 days.',
                ],
            ],
        ]);
    }







    public static function admissionBatchStatisticsWidget()
    {
        $totalBatchs = Batch::count();
        $activeBatchs = Batch::where('completed', 1)->count();
        $inactiveBatchs = Batch::where('completed', 0)->count();
        $ongoingBatches = Batch::whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();

        $getPercent = function ($count) use ($totalBatchs) {
            return $totalBatchs > 0 ? round(($count / $totalBatchs) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalBatchs),
                    'description' => 'Total Admission Batches',
                    'value' => number_format($totalBatchs),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All Admission Batches',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeBatchs),
                    'description' => 'Completed Admission Batches',
                    'value' => number_format($activeBatchs),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Batches currently completed.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveBatchs),
                    'description' => 'Not Completed Admission Batches',
                    'value' => number_format($inactiveBatchs),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Batches currently not completed.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($ongoingBatches),
                    'description' => 'Ongoing Admission Batches',
                    'value' => number_format($ongoingBatches),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Batches currently in session.',
                ],
            ],
        ]);
    }






    public static function CourseMatchOptionStatisticsWidget()
    {
        $totalCourseMatchOptions = CourseMatchOption::count();
        $activeCourseMatchOptions = CourseMatchOption::where('status', 1)->count();
        $inactiveCourseMatchOptions = CourseMatchOption::where('status', 0)->count();
        $recentCourseMatchOptions = CourseMatchOption::whereDate('created_at', '>=', now()->subDays(30))->count();

        $getPercent = function ($count) use ($totalCourseMatchOptions) {
            return $totalCourseMatchOptions > 0 ? round(($count / $totalCourseMatchOptions) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalCourseMatchOptions),
                    'description' => 'Total Course Matches',
                    'value' => number_format($totalCourseMatchOptions),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All Course Matches',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeCourseMatchOptions),
                    'description' => 'Active Course Matches',
                    'value' => number_format($activeCourseMatchOptions),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Course Matches currently active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveCourseMatchOptions),
                    'description' => 'Inactive Course Matches',
                    'value' => number_format($inactiveCourseMatchOptions),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Course Matches currently inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($recentCourseMatchOptions),
                    'description' => 'New Course Matches (30 Days)',
                    'value' => number_format($recentCourseMatchOptions),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Matches added in the last 30 days.',
                ],
            ],
        ]);
    }




    public static function courseMatchStatisticsWidget()
    {
        $totalCourseMatches = CourseMatch::count();
        $activeCourseMatches = CourseMatch::where('status', 1)->count();
        $inactiveCourseMatches = CourseMatch::where('status', 0)->count();
        $recentCourseMatches = CourseMatch::whereDate('created_at', '>=', now()->subDays(30))->count();

        $getPercent = function ($count) use ($totalCourseMatches) {
            return $totalCourseMatches > 0 ? round(($count / $totalCourseMatches) * 100) : 0;
        };

        Widget::add([
            'type' => 'div',
            'class' => 'row mb-4',
            'content' => [
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($totalCourseMatches),
                    'description' => 'Total Course Match Options',
                    'value' => number_format($totalCourseMatches),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'All Course Match Options',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($activeCourseMatches),
                    'description' => 'Active Course Match Options',
                    'value' => number_format($activeCourseMatches),
                    'progressClass' => 'progress-bar bg-success',
                    'hint' => 'Course Match Options currently active.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($inactiveCourseMatches),
                    'description' => 'Inactive Course Matches',
                    'value' => number_format($inactiveCourseMatches),
                    'progressClass' => 'progress-bar bg-danger',
                    'hint' => 'Course Match Options currently inactive.',
                ],
                [
                    'type' => 'progress_white',
                    'progress' => $getPercent($recentCourseMatches),
                    'description' => 'New Course Match Options (30 Days)',
                    'value' => number_format($recentCourseMatches),
                    'progressClass' => 'progress-bar bg-primary',
                    'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                    'hint' => 'Options added in the last 30 days.',
                ],
            ],
        ]);
    }
}
