<?php

use Illuminate\Support\Facades\Route;

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
    Route::crud('admission-rejection', 'AdmissionRejectionCrudController');
    Route::crud('app-config', 'AppConfigCrudController');
    Route::crud('attendance', 'AttendanceCrudController');
    Route::crud('branch', 'BranchCrudController');
    Route::crud('centre', 'CentreCrudController');
    Route::crud('course', 'CourseCrudController');
    Route::crud('course-session', 'CourseSessionCrudController');
    Route::crud('email-template', 'EmailTemplateCrudController');
    Route::crud('form', 'FormCrudController');
    Route::crud('form-response', 'FormResponseCrudController');
    Route::crud('oex-category', 'OexCategoryCrudController');
    Route::crud('oex-exam-master', 'OexExamMasterCrudController');
    Route::crud('oex-question-master', 'OexQuestionMasterCrudController');
    Route::crud('oex-result', 'OexResultCrudController');
    Route::crud('period', 'PeriodCrudController');
    Route::crud('programme', 'ProgrammeCrudController');
    Route::crud('sms-template', 'SmsTemplateCrudController');
    Route::crud('user', 'UserCrudController');
    Route::crud('user-admission', 'UserAdmissionCrudController');
    Route::crud('user-exam', 'UserExamCrudController');

    // Custom AJAX routes for bulk admit modal
    Route::get('course/ajax-list', 'CourseCrudController@ajaxList');
    Route::get('course-session/ajax-list', 'CourseSessionCrudController@ajaxList');
    Route::post('user/bulk-admit', 'UserCrudController@bulkAdmit');
    Route::get('qr-scanner', 'AttendanceCrudController@setupScanQrCodePage')->name('qr-scanner');

    // Custom routes for AttendanceCrudController non-CRUD methods
    Route::get('attendance/qr-scanner', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupScanQrCodePage'])->name('attendance.qr-scanner');
    Route::post('attendance/generate_qrcode', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupGenerateQrCodeData'])->name('attendance.generate_qrcode');
    Route::post('attendance/confirm_attendance', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupConfirmAttendance'])->name('attendance.confirm_attendance');
    Route::post('attendance/record_attendance', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupRecordAttendance'])->name('attendance.record_attendance');
    Route::get('attendance/view_attendance', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupViewAttendance'])->name('attendance.view_attendance');
    Route::delete('attendance/remove_attendance/{id}', [\App\Http\Controllers\Admin\AttendanceCrudController::class, 'setupRemoveAttendance'])->name('attendance.remove_attendance');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
