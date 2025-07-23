<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Api\CentreController;
use App\Http\Controllers\Admin\OexQuestionMasterCrudController;
use App\Http\Controllers\Admin\StudentVerificationCrudController;
use App\Http\Controllers\Admin\UserCrudController;
// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('admin', 'AdminCrudController');
    Route::get('/filemanager', function () {
        return view('admin.filemanager.index');
    });
    Route::post('/user/assign-batch', [UserCrudController::class, 'assignBatch']);
    Route::get('api/centre-by-branch', [CentreController::class, 'filterByBranch']);
    Route::get('admin/exam/{exam_id}/add-question', [OexQuestionMasterCrudController::class, 'addQuestion'])
    ->name('admin.exam.add-question');
    Route::crud('role', 'RoleCrudController');
    Route::crud('admission-rejection', 'AdmissionRejectionCrudController');
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
    Route::post('user/send-bulk-email', 'UserCrudController@sendBulkEmail');
    Route::post('user/send-bulk-sms', 'UserCrudController@sendBulkSMS');
    Route::post('user/shortlist-students', 'UserCrudController@saveShortlistedStudents');

    // AJAX endpoint to get count of all shortlisted students
    Route::post('user/shortlisted-count', 'UserCrudController@shortlistedCount')->name('user.shortlisted-count');

    // Custom routes for AttendanceCrudController non-CRUD methods
    Route::get('attendance/qr-scanner', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupScanQrCodePage'])->name('attendance.qr-scanner');
    Route::post('attendance/generate_qrcode', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupGenerateQrCodeData'])->name('attendance.generate_qrcode');
    Route::post('attendance/confirm_attendance', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupConfirmAttendance'])->name('attendance.confirm_attendance');
    Route::post('attendance/record_attendance', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupRecordAttendance'])->name('attendance.record_attendance');
    Route::get('attendance/view_attendance', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupViewAttendance'])->name('attendance.view_attendance');
    Route::delete('attendance/remove_attendance/{id}', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupRemoveAttendance'])->name('attendance.remove_attendance');

    // Shortlist Actions (Bulk/Group)
    Route::get('user/choose-shortlist-modal', 'UserCrudController@showChooseShortlistModal')->name('user.choose-shortlist-modal');
    Route::post('user/admit-shortlisted', 'UserCrudController@admitShortlistedStudents')->name('user.admit-shortlisted');

    // Shortlist Row Actions (Per Student)
    Route::post('user/{user}/change-admission', 'UserCrudController@changeAdmission')->name('user.change-admission');
    Route::post('user/{user}/choose-session', 'UserCrudController@chooseSession')->name('user.choose-session');
    Route::delete('user/{user}/delete-admission', 'UserCrudController@deleteAdmission')->name('user.delete-admission');

    // View Results for a student (admin panel, Backpack)
    Route::get('admin_view_result/{id}', 'UserCrudController@viewResult');
    // Reset Result for a student (admin panel, Backpack)
    Route::get('reset-exam/{exam_id}/student/{user_id}', 'UserCrudController@resetResult')->name('results.reset');
    Route::crud('student-verification', 'StudentVerificationCrudController');
    Route::crud('course-certification', 'CourseCertificationCrudController');
    Route::crud('course-match', 'CourseMatchCrudController');
    Route::crud('course-match-option', 'CourseMatchOptionCrudController');
    // Route::crud('media', 'MediaCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
