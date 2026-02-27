<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Api\CentreController;
use App\Http\Controllers\Admin\OexQuestionMasterCrudController;
use App\Http\Controllers\Admin\StudentVerificationCrudController;
use App\Http\Controllers\Admin\UserCrudController;
use App\Http\Controllers\Admin\ManageStudentCrudController;
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
    Route::get('admin/exam/{exam_id}/add-question', [OexQuestionMasterCrudController::class, 'addQuestion'])
        ->name('admin.exam.add-question');
    Route::crud('role', 'RoleCrudController');
    Route::crud('admission-rejection', 'AdmissionRejectionCrudController');
    Route::post('app-config/{id}/toggle', 'AppConfigCrudController@toggleValue');
    Route::crud('app-config', 'AppConfigCrudController');
    Route::crud('attendance', 'AttendanceCrudController');
    Route::crud('branch', 'BranchCrudController');
    Route::crud('centre', 'CentreCrudController');
    Route::crud('course', 'CourseCrudController');
    Route::crud('batch', 'BatchCrudController');
    Route::crud('course-session', 'CourseSessionCrudController');
    Route::crud('email-template', 'EmailTemplateCrudController');
    Route::crud('form', 'FormCrudController');
    Route::crud('form-response', 'FormResponseCrudController');
    Route::crud('category', 'OexCategoryCrudController');
    Route::crud('manage-exam', 'OexExamMasterCrudController');
    Route::crud('question-master', 'OexQuestionMasterCrudController');
    Route::crud('oex-result', 'OexResultCrudController');
    Route::crud('period', 'PeriodCrudController');
    Route::crud('programme', 'ProgrammeCrudController');
    Route::crud('sms-template', 'SmsTemplateCrudController');
    Route::crud('user', 'UserCrudController');
    Route::crud('manage-student', 'ManageStudentCrudController');

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
    Route::crud('course-match-option', 'CourseMatchOptionCrudController');

    // Utilities dashboard (super-admin only)
    Route::get('utilities', [UtilitiesController::class, 'index'])->name('admin.utilities.index');
    Route::post('utilities/run', [UtilitiesController::class, 'run'])->name('admin.utilities.run');

    Route::get('user/activities/{user_id}', 'UserCrudController@getActivities')->name('user.activities');

    Route::crud('activity', 'ActivityCrudController');
    // Route::crud('media', 'MediaCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
