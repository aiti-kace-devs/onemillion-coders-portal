<?php

namespace App\Helpers;

use Backpack\CRUD\app\Library\Widget;
use App\Models\Admin;
use App\Models\AdmissionRejection;
use App\Models\Attendance;
use App\Models\Programme;
use App\Models\Course;
use App\Models\Branch;
use App\Models\Centre;
use App\Models\User;
use App\Models\CourseSession;
use App\Models\OexExamMaster;
use App\Models\OexQuestionMaster;
use App\Models\OexCategory;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
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
        $totalUsers = Admin::whereHas('roles')->count();
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
        $topRejectedCourses = Attendance::select('course_id')
            ->selectRaw('COUNT(*) as total_attendance')
            ->groupBy('course_id')
            ->orderByDesc('total_attendance')
            ->with('course')
            ->take(4)
            ->get();

        $widgets = [];

        foreach ($topRejectedCourses as $rejection) {
            $courseName = optional($rejection->course)->course_name ?? 'Unknown';
            $total = $rejection->total_attendance;

            $widgets[] = [
                'type' => 'progress_white',
                'progress' => 100,
                'description' => $courseName,
                'value' => number_format($total),
                'progressClass' => 'bg-primary',
                'hint' => 'Total Attendance for this course',
            ];
        }

        Widget::add([
            'type' => 'div',
            'class' => 'row',
            'content' => $widgets,
        ]);
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






public static function centreStatisticsWidget()
{
    $totalCenters = Centre::count();
    $activeCenters = Centre::where('status', 1)->count();
    $inactiveCenters = Centre::where('status', 0)->count();
    $recentCenters = Centre::whereDate('created_at', '>=', now()->subDays(30))->count();

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
                'progress' => $getPercent($inactiveCenters),
                'description' => 'Inactive Centers',
                'value' => number_format($inactiveCenters),
                'progressClass' => 'progress-bar bg-danger',
                'hint' => 'Centers currently inactive.',
            ],
            [
                'type' => 'progress_white',
                'progress' => $getPercent($recentCenters),
                'description' => 'New Centers (30 Days)',
                'value' => number_format($recentCenters),
                'progressClass' => 'progress-bar bg-primary',
                'wrapper' => [
                        'style' => 'background-color:rgb(40, 127, 167);',
                    ],
                'hint' => 'Centers added in the last 30 days.',
            ],
        ],
    ]);
}





    public static function courseStatisticsWidget()
{
    $totalCourses = Course::count();
    $activeCourses = Course::where('status', 1)->count();
    $inactiveCourses = Course::where('status', 0)->count();
    $ongoingCourses = Course::whereDate('start_date', '<=', now())
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
    $totalSessions = CourseSession::count();
    $activeSessions = CourseSession::where('status', 1)->count();
    $inactiveSessions = CourseSession::where('status', 0)->count();

    $upcomingSessions = CourseSession::where('course_time', '>', now())->count();

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
    $totalUsers = User::count();

    $admittedUsers = User::whereExists(function ($query) {
        $query->select(\DB::raw(1))
            ->from('user_admission')
            ->whereColumn('user_admission.user_id', 'users.userId')
            ->whereNotNull('user_admission.confirmed');
    })->count();

    $shortlistedUsers = User::where('shortlist', 1)->count();

    $todaysAdmittedUsers = User::whereExists(function ($query) {
        $query->select(\DB::raw(1))
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











}