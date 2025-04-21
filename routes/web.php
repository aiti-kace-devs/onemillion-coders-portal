<?php

use HansSchouten\LaravelPageBuilder\LaravelPageBuilder;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentOperation;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\RegisteredUserController;
use App\Http\Controllers\AppConfigController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CentreController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormResponseController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\SmsTemplateController;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::redirect('/', '/login');


Route::get('/', [LandingPageController::class, 'index']);

Route::get('/available-courses', [LandingPageController::class, 'availableCourses'])->name('available-courses');

Route::get('/application', [LandingPageController::class, 'application'])->name('application');

Route::get('/{course}', [LandingPageController::class, 'courseView'])->where('course', 'cybersecurity-course|ai-course|data-protection-course|protection-expert-course|protection-sup-course|certified-dpf-course|cnst-course')
    ->name('dynamic-course');


Route::get('/forms/{formCode}', [FormController::class, 'submitForm'])->name('register');
Route::post('form-responses/', [FormResponseController::class, 'store'])->name('admin.form_responses.store');


Route::prefix('admin')->middleware(['auth:admin'])->name('admin.')->group(function () {

    // forms route
    Route::prefix('/forms')->name('form.')->group(function () {
        Route::get('/', [FormController::class, 'index'])->name('index');
        Route::get('/fetch', [FormController::class, 'fetch'])->name('fetch');
        Route::get('/create', [FormController::class, 'create'])->name('create');
        Route::post('/', [FormController::class, 'store'])->name('store');
        Route::get('/{form}/edit', [FormController::class, 'edit'])->name('edit');
        Route::put('/{form}/update', [FormController::class, 'update'])->name('update');
        Route::get('/{form}/preview', [FormController::class, 'preview'])->name('preview');
        Route::get('/{form}/responses', [FormController::class, 'show'])->name('show');
        Route::get('/{form}/export', [FormController::class, 'export'])->name('export');
        Route::post('/{form}/destroy', [FormController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('form-responses')->name('form_responses.')->group(function () {
        // form responses route
        Route::get('/', [FormController::class, 'index'])->name('index');
        Route::get('/fetch', [FormResponseController::class, 'fetch'])->name('fetch');
        Route::get('/create', [FormController::class, 'create'])->name('create');
        Route::get('/{response}/edit', [FormResponseController::class, 'edit'])->name('edit');
        Route::put('/{response}/update', [FormResponseController::class, 'update'])->name('update');
        Route::get('/{response}/view', [FormResponseController::class, 'show'])->name('show');
        Route::post('/{response}/destroy', [FormResponseController::class, 'destroy'])->name('destroy');
    });
});










Route::prefix('admin')->middleware('theme:dashboard')->name('admin.')->group(function () {

    Route::middleware(['auth:admin'])->group(function () {

        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/delete_category/{id}', [AdminController::class, 'delete_category'])->middleware('admin.super');
        Route::get('/exam_category', [AdminController::class, 'exam_category'])->middleware('admin.super');
        Route::get('/edit_category/{id}', [AdminController::class, 'edit_category'])->middleware('admin.super');
        Route::get('/category_status/{id}', [AdminController::class, 'category_status'])->middleware('admin.super');
        Route::get('/branch_status/{id}', [AdminController::class, 'branch_status'])->middleware('admin.super');
        Route::get('/centre_status/{id}', [AdminController::class, 'centre_status'])->middleware('admin.super');
        Route::get('/programme_status/{id}', [AdminController::class, 'programme_status'])->middleware('admin.super');
        Route::get('/course_status/{id}', [AdminController::class, 'course_status'])->middleware('admin.super');
        Route::get('/manage_exam', [AdminController::class, 'manage_exam'])->middleware('admin.super');
        Route::get('/exam_status/{id}', [AdminController::class, 'exam_status'])->middleware('admin.super');
        Route::get('/delete_exam/{id}', [AdminController::class, 'delete_exam'])->middleware('admin.super');
        Route::get('/edit_exam/{id}', [AdminController::class, 'edit_exam'])->middleware('admin.super');
        Route::get('/manage_students', [AdminController::class, 'manage_students'])->middleware('admin.super')->name('manage_students');
        Route::get('/student_status/{id}', [AdminController::class, 'student_status'])->middleware('admin.super');
        Route::get('/delete_students/{id}', [AdminController::class, 'delete_students'])->middleware('admin.super');
        Route::get('/add_questions/{id}', [AdminController::class, 'add_questions'])->middleware('admin.super');
        Route::get('/question_status/{id}', [AdminController::class, 'question_status'])->middleware('admin.super');
        Route::get('/delete_question/{id}', [AdminController::class, 'delete_question'])->middleware('admin.super');
        Route::get('/update_question/{id}', [AdminController::class, 'update_question'])->middleware('admin.super');
        Route::get('/shortlisted_students', [AdminController::class, 'shortlisted_students'])->middleware('admin.super')->name('shortlisted_students');
        Route::post('/save_shortlisted_students', [AdminController::class, 'saveShortlistedStudents'])->name('save_shortlisted_students');
        Route::get('/registered_students', [AdminController::class, 'registered_students'])->middleware('admin.super');
        Route::get('/delete_registered_students/{id}', [AdminController::class, 'delete_registered_students'])->middleware('admin.super');
        Route::get('/apply_exam/{id}', [AdminController::class, 'apply_exam']);
        Route::get('/admin_view_result/{id}', [AdminController::class, 'admin_view_result'])->middleware('admin.super');
        Route::get('/view_answer/{id}', [StudentOperation::class, 'view_answer'])->middleware('admin.super');
        Route::post('/edit_question_inner', [AdminController::class, 'edit_question_inner'])->middleware('admin.super');
        Route::post('/add_new_question', [AdminController::class, 'add_new_question'])->middleware('admin.super');
        Route::post('/student/admit', [StudentOperation::class, 'admit_student'])->name('admit_student')->middleware('admin.super');
        Route::post('/edit_students_final', [AdminController::class, 'edit_students_final'])->middleware('admin.super');
        Route::post('/add_new_exam', [AdminController::class, 'add_new_exam'])->middleware('admin.super');
        Route::post('/add_new_category', [AdminController::class, 'add_new_category'])->middleware('admin.super');
        Route::post('/edit_new_category', [AdminController::class, 'edit_new_category'])->middleware('admin.super');
        Route::post('/edit_exam_sub', [AdminController::class, 'edit_exam_sub'])->middleware('admin.super');
        Route::post('/add_new_students', [AdminController::class, 'add_new_students']);
        Route::get('/reset-exam/{exam_id}/student/{user_id}', [StudentOperation::class, 'reset_exam'])->name('reset-exam');
        Route::get('/generate_qrcode', [AdminController::class, 'generate_qrcode_page']);
        Route::post('/generate_qrcode', [AttendanceController::class, 'generateQRCodeData']);
        Route::get('/scan_qrcode', [AdminController::class, 'scan_qrcode_page']);
        Route::post('/confirm_attendance', [AttendanceController::class, 'confirmAttendance']);
        Route::get('/verification', [AdminController::class, 'verification_page'])->name('verification');
        Route::get('/verify_details', [AdminController::class, 'verifyDetails'])->name('verify-details');
        Route::post('/verify-student/{id}', [AdminController::class, 'verifyStudent'])->name('verify-student');
        Route::get('/view_attendance', [AdminController::class, 'viewAttendanceByDate'])->name('viewAttendanceByDate');
        Route::get('/reports', [AdminController::class, 'getReportView'])->name('getReportView');
        Route::post('/reports', [AdminController::class, 'generateReport'])->name('generateReport');
        Route::post('/send-bulk-email', [AdminController::class, 'sendBulkEmail'])->name('send_bulk_email');
        Route::get('/fetch_sms_template', [AdminController::class, 'fetch_sms_template'])->name('fetch.sms.template');
        Route::post('/send_bulk_sms', [AdminController::class, 'sendBulkSMS'])->name('send_bulk_sms');
        Route::delete('/delete-student-admission/{user_id}', [AdminController::class, 'delete_admission'])->name('delete-student-admission')->middleware('admin.super');


        Route::get('/manage_admins', [RegisteredUserController::class, 'index'])->name('manage_admins')->middleware('admin.super');
        Route::get('/create', [RegisteredUserController::class, 'create'])->name('admins.create')->middleware('admin.super');
        Route::post('/add_new_admin', [RegisteredUserController::class, 'store'])->middleware('admin.super');
        Route::get('/edit_admin/{id}/edit', [RegisteredUserController::class, 'edit'])->name('admins.edit')->middleware('admin.super');
        Route::put('/{id}/update', [RegisteredUserController::class, 'update'])->name('admins.update')->middleware('admin.super');
        Route::delete('/{id}/delete', [RegisteredUserController::class, 'destroy'])->name('admins.delete')->middleware('admin.super');
        Route::get('/is_super_admin_status/{id}', [RegisteredUserController::class, 'is_super_admin_status'])->middleware('admin.super');


        Route::get('/remove-attendance/{id}', [AttendanceController::class, 'removeAttendance'])->name('remove-attendance');


        Route::get('/reset-verify/{id}', [AdminController::class, 'reset_verify'])->name('reset-verify');
        Route::post('/admit', [AdminController::class, 'admit_student'])->name('admit_user_ui')->middleware('admin.super');

        // manage branch routes
        Route::prefix('manage-branch')->group(function () {
            Route::get('/', [BranchController::class, 'index'])->name('branch.index');
            Route::post('/', [BranchController::class, 'store'])->name('branch.store');
            Route::get('/{id}/edit', [BranchController::class, 'edit'])->name('branch.edit');
            Route::put('/{branch}/update', [BranchController::class, 'update'])->name('branch.update');
            Route::get('/{branch}/delete', [BranchController::class, 'destroy'])->name('branch.destroy');
        });
        // end of manage branch routes

        // manage centre routes
        Route::prefix('manage-centre')->group(function () {
            Route::get('/', [CentreController::class, 'index'])->name('centre.index');
            Route::post('/', [CentreController::class, 'store'])->name('centre.store');
            Route::get('/{id}/edit', [CentreController::class, 'edit'])->name('centre.edit');
            Route::put('/{centre}/update', [CentreController::class, 'update'])->name('centre.update');
            Route::get('/{centre}/delete', [CentreController::class, 'destroy'])->name('centre.destroy');
        });
        // end of manage centre routes

        // manage programme routes
        Route::prefix('manage-programme')->group(function () {
            Route::get('/', [ProgrammeController::class, 'index'])->name('programme.index');
            Route::post('/', [ProgrammeController::class, 'store'])->name('programme.store');
            Route::get('/{id}/edit', [ProgrammeController::class, 'edit'])->name('programme.edit');
            Route::put('/{programme}/update', [ProgrammeController::class, 'update'])->name('programme.update');
            Route::get('/{programme}/delete', [ProgrammeController::class, 'destroy'])->name('programme.destroy');
        });
        // end of manage programme routes

        // manage course routes
        Route::prefix('manage-course')->group(function () {
            Route::get('/', [CourseController::class, 'index'])->name('course.index');
            Route::post('/', [CourseController::class, 'store'])->name('course.store');
            Route::get('/{id}/edit', [CourseController::class, 'edit'])->name('course.edit');
            Route::get('/fetch/centre', [CourseController::class, 'fetchCentre'])->name('course.fetch.centre');
            Route::put('/{course}/update', [CourseController::class, 'update'])->name('course.update');
            Route::get('/{course}/delete', [CourseController::class, 'destroy'])->name('course.destroy');
            Route::get('/fetch-programme', [CourseController::class, 'fetchProgrammeDetails'])->name('course.fetch.programme');
        });
        // end of manage course routes

        // manage session routes
        Route::prefix('sessions')->name('session.')->group(function () {
            Route::get('/', [SessionController::class, 'index'])->name('index');
            Route::get('/fetch', [SessionController::class, 'fetch'])->name('fetch');
            Route::get('/create', [SessionController::class, 'create'])->name('create');
            Route::post('/', [SessionController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [SessionController::class, 'edit'])->name('edit');
            Route::put('/{session}/update', [SessionController::class, 'update'])->name('update');
            Route::get('/{session}/delete', [SessionController::class, 'destroy'])->name('destroy');
        });
        // end of manage period routes

        // manage class schedule routes
        Route::prefix('manage-class-schedule')->group(function () {
            Route::get('/', [ClassScheduleController::class, 'index'])->name('class.schedule.index');
            Route::post('/', [ClassScheduleController::class, 'store'])->name('class.schedule.store');
            Route::post('/{id}/edit', [ClassScheduleController::class, 'edit'])->name('class.schedule.edit');
            Route::put('/{course}/update', [ClassScheduleController::class, 'update'])->name('class.schedule.update');
            Route::get('/{course}/delete', [ClassScheduleController::class, 'destroy'])->name('class.schedule.destroy');
        });
        // end of manage class schedule routes

        // manage sms_template routes
        Route::prefix('manage-sms-template')->group(function () {
            Route::get('/', [SmsTemplateController::class, 'index'])->name('sms.template.index');
            Route::post('/', [SmsTemplateController::class, 'store'])->name('sms.template.store');
            Route::get('/{id}/edit', [SmsTemplateController::class, 'edit'])->name('sms.template.edit');
            Route::put('/{template}/update', [SmsTemplateController::class, 'update'])->name('sms.template.update');
            Route::get('/{template}/delete', [SmsTemplateController::class, 'destroy'])->name('sms.template.destroy');
        });
        // end of manage sms_template routes

        Route::get('app-logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->middleware(['admin.super']);
        Route::get('/app-config', [AppConfigController::class, 'index'])->name('config.index')->middleware('admin.super');
        Route::put('/app-config', [AppConfigController::class, 'update'])->name('config.update')->middleware('admin.super');

        // Route::get('/manage-lists', [ListController::class, 'index'])->name('lists.index')->middleware('admin.super');
        Route::get('/lists/fetch', [ListController::class, 'fetch'])->name('lists.fetch')->middleware('admin.super');
        Route::get('/lists/get-table-columns', [ListController::class, 'getTableColumns'])->middleware('admin.super')->name('lists.get-table-columns');
        Route::get('/lists/view-data', [ListController::class, 'viewData'])->name('lists.view-data')->middleware('admin.super');
        Route::resource('/lists', ListController::class)->middleware('admin.super');
    });
});




/* Student section routes */
Route::prefix('student')
    ->middleware('theme:dashboard')
    ->name('student.')->group(function () {

        Route::get('/select-session/{user_id}', [StudentOperation::class, 'select_session_view'])->middleware('auth');
        Route::post('/select-session/{user_id}', [StudentOperation::class, 'confirm_session'])->name('select-session')->middleware('auth');
        Route::delete('/delete-student-admission/{user_id}', [StudentOperation::class, 'delete_admission'])->name('delete-student-admission')->middleware('auth');

        Route::middleware(['auth:web'])->group(function () {
            Route::get('/dashboard', [StudentOperation::class, 'dashboard'])->name('dashboard');
            Route::get('/application-status', [StudentOperation::class, 'application_status'])->name('application-status');
            Route::get('/profile', [StudentOperation::class, 'profile'])->name('profile');
            Route::get('/change-course', [StudentOperation::class, 'change_course'])->name('change-course');
            Route::post('/update-course', [StudentOperation::class, 'update_course'])->name('update-course');

            Route::get('/exam', [StudentOperation::class, 'exam']);
            Route::get('/join_exam/{id}', [StudentOperation::class, 'join_exam']);
            Route::post('/submit_questions', [StudentOperation::class, 'submit_questions']);
            Route::get('/show_result/{id}', [StudentOperation::class, 'show_result']);
            Route::get('/apply_exam/{id}', [StudentOperation::class, 'apply_exam']);
            // Route::get('/view_result/{id}', [StudentOperation::class, 'view_result']);
            Route::post('/attendance/record', [AttendanceController::class, 'recordAttendance'])->name('attendance.record');
            Route::get('/attendance', [AttendanceController::class, 'viewAttendance'])->name('attendance.show');
            Route::get('/id-qrcode', [StudentOperation::class, 'get_details_page']);
            Route::get('/scan-qrcode', [StudentOperation::class, 'get_scanner_page']);
            Route::get('/meeting-link', [StudentOperation::class, 'get_meeting_link_page']);
            Route::post('/update-details', [StudentOperation::class, 'updateDetails'])->name('updateDetails');



            // Route::get('/ateendance', [StudentOperation::class, 'view_result']);



            // Route::get('/view_answer/{id}', [StudentOperation::class, 'view_answer']);

            Route::post('/start-exam/{id}', [StudentOperation::class, 'start_exam']);
            Route::get('/mark_attendance', [AttendanceController::class, 'recordAttendance'])->name('mark-attendance');
            Route::get('/logout', [AuthenticatedSessionController::class, 'destroy']);
        });
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__ . '/auth.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/builder.php';
