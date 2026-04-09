<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Api\CentreController;
use App\Http\Controllers\Admin\Api\ConstituencyController as ConstituencyApiController;
use App\Http\Controllers\Admin\Api\DistrictController as DistrictApiController;
use App\Http\Controllers\Admin\OexQuestionMasterCrudController;
use App\Http\Controllers\Admin\StudentVerificationCrudController;
use App\Http\Controllers\Admin\UserCrudController;
use App\Http\Controllers\Admin\BatchCrudController;
use App\Http\Controllers\Admin\CourseBatchCrudController;
use App\Http\Controllers\Admin\DistrictCrudController;
use App\Http\Controllers\Admin\ConstituencyCrudController;
use App\Http\Controllers\Admin\ManageStudentCrudController;
use App\Http\Controllers\Admin\CentreCrudController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UtilitiesController;
// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' =>
    config('backpack.base.middleware_key', 'admin'),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('admin', 'AdminCrudController');
    Route::get('/filemanager', function () {
        return view('admin.filemanager.index');
    });
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('backpack.dashboard');
    Route::post('/user/assign-batch', [UserCrudController::class, 'assignBatch']);
    Route::get('api/centre-by-branch', [CentreController::class, 'filterByBranch']);
    Route::get('api/constituency-by-branch', [ConstituencyApiController::class, 'filterByBranch']);
    Route::get('api/district-by-branch', [DistrictApiController::class, 'filterByBranch']);
    Route::get('admin/exam/{exam_id}/add-question', [OexQuestionMasterCrudController::class, 'addQuestion'])
        ->name('admin.exam.add-question');
    Route::crud('role', 'RoleCrudController');
    Route::crud('admission-rejection', 'AdmissionRejectionCrudController');
    Route::post('app-config/{id}/toggle', 'AppConfigCrudController@toggleValue');
    Route::crud('app-config', 'AppConfigCrudController');
    Route::crud('attendance', 'AttendanceCrudController');
    Route::crud('branch', 'BranchCrudController');
    Route::post('branch/{id}/toggle', 'BranchCrudController@toggleStatus');
    Route::crud('centre', 'CentreCrudController');
    Route::post('centre/{id}/toggle', 'CentreCrudController@toggleStatus');
    Route::post('centre/{id}/toggle-is-pwd-friendly', 'CentreCrudController@toggleIsPwdFriendly');
    Route::post('centre/{id}/toggle-is-ready', 'CentreCrudController@toggleIsReady');
    Route::get('centre/{centreId}/sessions', [CentreCrudController::class, 'getCentreSessions']);
    Route::post('centre/{centreId}/sessions', [CentreCrudController::class, 'saveCentreSessions']);
    Route::crud('course', 'CourseCrudController');
    Route::crud('batch', 'BatchCrudController');
    Route::post('batch/add-courses/{batchId}', [BatchCrudController::class, 'addCourses']);
    Route::post('batch/update-course/{courseId}', [BatchCrudController::class, 'updateCourse']);
    Route::get('batch/course/{courseId}/sessions', [BatchCrudController::class, 'getCourseSessions']);
    Route::post('batch/course/{courseId}/sessions', [BatchCrudController::class, 'saveCourseSessions']);
    Route::post('batch/{id}/toggle', [BatchCrudController::class, 'toggleStatus']);
    Route::post('batch/{id}/toggle-completed', [BatchCrudController::class, 'toggleCompleted']);
    Route::crud('tag', 'TagCrudController');
    Route::crud('tag-type', 'TagTypeCrudController');
    Route::crud('course-session', 'CourseSessionCrudController');
    Route::crud('email-template', 'EmailTemplateCrudController');
    Route::crud('form', 'FormCrudController');
    Route::post('form/{id}/toggle', 'FormCrudController@toggleStatus');
    Route::crud('form-response', 'FormResponseCrudController');
    Route::crud('category', 'OexCategoryCrudController');
    Route::post('category/{id}/toggle', 'OexCategoryCrudController@toggleStatus');
    Route::crud('manage-exam', 'OexExamMasterCrudController');
    Route::post('manage-exam/{id}/toggle', 'OexExamMasterCrudController@toggleStatus');
    Route::crud('question-master', 'OexQuestionMasterCrudController');
    Route::post('question-master/{id}/toggle', 'OexQuestionMasterCrudController@toggleStatus');
    Route::crud('oex-result', 'OexResultCrudController');
    Route::crud('period', 'PeriodCrudController');
    Route::crud('programme', 'ProgrammeCrudController');
    Route::post('programme/{id}/toggle', 'ProgrammeCrudController@toggleStatus');
    Route::crud('sms-template', 'SmsTemplateCrudController');
    Route::crud('user', 'UserCrudController');
    Route::crud('manage-student', 'ManageStudentCrudController');

    // Manage Student actions (Per Student)
    Route::post('manage-student/{user}/change-admission', 'ManageStudentCrudController@changeAdmission')->name('manage-student.change-admission');
    Route::post('manage-student/{user}/choose-session', 'ManageStudentCrudController@chooseSession')->name('manage-student.choose-session');
    Route::delete('manage-student/delete-admission/{user_id}', 'ManageStudentCrudController@deleteAdmission')->name('manage-student.delete-admission');

    // AJAX routes for student metrics and dropdowns
    Route::get('manage-student/{user}/metrics', 'ManageStudentCrudController@getStudentMetrics')->name('manage-student.metrics');
    Route::get('manage-student/courses-ajax', 'ManageStudentCrudController@getCoursesAjax')->name('manage-student.courses-ajax');
    Route::get('manage-student/sessions-ajax', 'ManageStudentCrudController@getSessionsAjax')->name('manage-student.sessions-ajax');

    // Use UserCrudController's bulkAdmit for single student admission
    Route::post('manage-student/bulk-admit', 'UserCrudController@bulkAdmit')->name('manage-student.bulk-admit');

    // Separate CRUD controllers for different student views
    Route::crud('students-with-admission', 'StudentsWithAdmissionCrudController');
    Route::crud('students-without-exam-results', 'StudentsWithoutExamResultsCrudController');
    Route::crud('students-yet-to-accept-admission', 'StudentsYetToAcceptAdmissionCrudController');
    Route::crud('students-with-exam-results', 'StudentsWithExamResultsCrudController');
    Route::crud('shortlisted-students', 'ShortlistedStudentsCrudController');

    Route::crud('user-admission', 'UserAdmissionCrudController');
    Route::crud('user-exam', 'UserExamCrudController');
    Route::post('/admin/student-verification/{id}/reset', [StudentVerificationCrudController::class, 'resetVerification'])->name('student-verification.reset');

    // Custom AJAX routes for bulk admit modal
    Route::get('course/ajax-list', 'CourseCrudController@ajaxList');
    Route::get('course-session/ajax-list', 'CourseSessionCrudController@ajaxList');
    Route::post('user/bulk-admit', 'UserCrudController@bulkAdmit');
    Route::crud('course-category', 'CourseCategoryCrudController');
    Route::post('course-category/{id}/toggle', 'CourseCategoryCrudController@toggleStatus');
    Route::crud('course-module', 'CourseModuleCrudController');
    Route::get('qr-scanner', 'AttendanceCrudController@setupScanQrCodePage')->name('qr-scanner');

    // Bulk actions for UserCrudController
    Route::get('user/fetch_sms_template', 'UserCrudController@fetchSmsTemplate')->name('sms-template.fetch');
    Route::post('user/send-bulk-email', 'UserCrudController@sendBulkEmail')->name('bulk-email.send');
    Route::post('user/send-bulk-sms', 'UserCrudController@sendBulkSMS')->name('bulk-sms.send');
    Route::post('user/shortlist-students', 'UserCrudController@saveShortlistedStudents')->name('bulk-students.shortlist');

    // AJAX endpoint to get count of all shortlisted students
    Route::post('user/shortlisted-count', 'UserCrudController@shortlistedCount')->name('user.shortlisted-count');
    Route::get('user/filtered-count', 'UserCrudController@getFilteredCount')->name('user.filtered-count');

    // Custom routes for AttendanceCrudController non-CRUD methods
    Route::get('attendance/qr-scanner', 'AttendanceCrudController@setupScanQrCodePage')->name('attendance.qr-scanner');
    Route::post('attendance/generate_qrcode', 'AttendanceCrudController@setupGenerateQrCodeData')->name('attendance.generate_qrcode');
    Route::post('attendance/confirm_attendance', 'AttendanceCrudController@setupConfirmAttendance')->name('attendance.confirm_attendance');
    Route::post('attendance/record_attendance', 'AttendanceCrudController@setupRecordAttendance')->name('attendance.record_attendance');
    Route::get('attendance/view_attendance', 'AttendanceCrudController@setupViewAttendance')->name('attendance.view_attendance');
    Route::delete('attendance/remove_attendance/{id}', 'AttendanceCrudController@setupRemoveAttendance')->name('attendance.remove_attendance');

    // Shortlist Actions (Bulk/Group)
    Route::get('user/choose-shortlist-modal', 'UserCrudController@showChooseShortlistModal')->name('user.choose-shortlist-modal');
    Route::post('user/admit-shortlisted', 'UserCrudController@admitShortlistedStudents')->name('user.admit-shortlisted');
    Route::post('user/admit-student', 'UserCrudController@bulkAdmit')->name('user.admit-student');

    // Shortlist Row Actions (Per Student)
    Route::post('user/{user}/change-admission', 'UserCrudController@changeAdmission')->name('user.change-admission');
    Route::post('user/{user}/choose-session', 'UserCrudController@chooseSession')->name('user.choose-session');
    Route::delete('user/delete-admission/{user_id}', 'UserCrudController@deleteAdmission')->name('user.delete-admission');

    // View Results for a student (admin panel, Backpack)
    Route::get('admin_view_result/{id}', 'UserCrudController@viewResult');
    // Reset Result for a student (admin panel, Backpack)
    Route::get('reset-exam/{exam_id}/student/{user_id}', 'UserCrudController@resetResult')->name('results.reset');
    Route::crud('student-verification', 'StudentVerificationCrudController');
    Route::crud('course-certification', 'CourseCertificationCrudController');
    Route::crud('course-match', 'CourseMatchCrudController');
    Route::post('course-match/{id}/toggle', 'CourseMatchCrudController@toggleStatus');
    Route::crud('course-match-option', 'CourseMatchOptionCrudController');
    Route::post('course-match-option/{id}/toggle', 'CourseMatchOptionCrudController@toggleStatus');


    // Utilities dashboard (super-admin only)
    Route::get('utilities', [UtilitiesController::class, 'index'])->name('admin.utilities.index');
    Route::post('utilities/run', [UtilitiesController::class, 'run'])->name('admin.utilities.run');

    Route::get('user/activities/{user_id}', 'UserCrudController@getActivities')->name('user.activities');

    Route::crud('activity', 'ActivityCrudController');
    // Route::crud('media', 'MediaCrudController');
    Route::post('course-batch/{id}/toggle', [CourseBatchCrudController::class, 'toggleStatus']);
    Route::get('course-batch/{id}/admitted-students-data', [CourseBatchCrudController::class, 'admittedStudentsData'])->name('course-batch.admitted-students-data');
    Route::get('course-batch/{id}/attendance-history-data', [CourseBatchCrudController::class, 'attendanceHistoryData'])->name('course-batch.attendance-history-data');
    Route::crud('course-batch', 'CourseBatchCrudController');
    Route::post('district/{id}/toggle', [DistrictCrudController::class, 'toggleStatus']);
    Route::post('constituency/{id}/toggle', [ConstituencyCrudController::class, 'toggleStatus']);
    Route::post('district/{districtId}/add-centres', [DistrictCrudController::class, 'addCentres'])->name('district.add-centres');
    Route::delete('district/{districtId}/remove-centre/{centreId}', [DistrictCrudController::class, 'removeCentre'])->name('district.remove-centre');
    Route::crud('district', 'DistrictCrudController');
    Route::crud('constituency', 'ConstituencyCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
