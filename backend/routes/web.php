<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentOperation;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AppConfigController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Traits\AttendanceQRCodeTrait;
use App\Http\Controllers\Traits\AttendanceConfirmTrait;
use App\Http\Controllers\Traits\AttendanceViewRemoveTrait;
// use App\Http\Controllers\Admin\RegisteredUserController;
// use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CentreController;
use App\Http\Controllers\Admin\ConstituencyCrudController;
use App\Http\Controllers\Admin\Api\FormPreviewController;
use App\Http\Controllers\Admin\Api\CourseProgrammeController;
use App\Http\Controllers\Admin\Api\CourseMatchAPIController;
use App\Http\Controllers\Admin\Api\RegistrationFormAPIController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormResponseController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\SmsTemplateController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\Api\OtpController;
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

Route::redirect('/', 'login');


Route::get('/api/form', [RegistrationFormAPIController::class, 'index']);
Route::post('/api/add-student', [FormResponseController::class, 'store']);
Route::get('/api/constituencies/{constituency}/metrics', [ConstituencyCrudController::class, 'metrics']);
Route::get('/api/check-user/{userID}', [RegistrationFormAPIController::class, 'check_user_by_userID'])->middleware('user.token');
Route::post('/api/confirm-course-for-self-paced', [RegistrationFormAPIController::class, 'enrollSelfPacedStudent'])->middleware('user.token');
Route::post('/api/confirm-course', [RegistrationFormAPIController::class, 'confirmCourse'])->middleware('user.token');
Route::post('/api/switch-to-self-paced-or-with-support', [RegistrationFormAPIController::class, 'switchToSelfPacedOrWithSupport'])->middleware('user.token');
Route::get('/api/check-user-recommended-courses/{userID}', [CourseMatchAPIController::class, 'checkUserRecommendedCourses'])->middleware('user.token');

// OTP verification routes for registration
Route::get('/api/otp/check-email', [OtpController::class, 'checkEmail'])->middleware('throttle:30,1');
Route::post('/api/otp/send', [OtpController::class, 'send'])->middleware('throttle:10,1');
Route::post('/api/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:20,1');
Route::get('/api/otp/status', [OtpController::class, 'status'])->middleware('throttle:30,1');

// Redirect Statamic login to Backpack login
Route::get(config('statamic.cp.route', 'cp') . '/auth/login', function () {
    return redirect()->to(backpack_url('login'));
});

Route::get('/api/course-match', [CourseMatchAPIController::class, 'index']);
Route::post('/api/course-match/recommend', action: [CourseMatchAPIController::class, 'recommend']);
Route::get('/api/programmes', [CourseProgrammeController::class, 'programmeWithBatch']);
Route::get('/api/programmes-with-batches', [CourseProgrammeController::class, 'programmeWithBatch']);
Route::get('/api/programme/{id}', [CourseProgrammeController::class, 'show']);
Route::get('/api/programmes/category/{categoryId}', [CourseProgrammeController::class, 'programmesByCategory']);

Route::get('/api/programmes/{programme}/locations', [CourseProgrammeController::class, 'programmeLocations']);
Route::get('/api/centre/{centre}/programmes', [CourseProgrammeController::class, 'programmesByCentre']);
Route::get('/api/centres-by-id/{centre}', [CourseProgrammeController::class, 'centreById']);
Route::get('/api/categories', [CourseProgrammeController::class, 'getCourseCategory']);
Route::get('/api/branches', [CourseProgrammeController::class, 'getBranch']);
Route::get('/api/branches/summary', [CourseProgrammeController::class, 'getBranchSummary']);
Route::get('admin/forms/preview/{form}', [FormPreviewController::class, 'preview'])->name('forms.preview');
Route::get('/api/branch/{branch}/centres', [CourseProgrammeController::class, 'centresByBranch']);
Route::get('/api/districts-by-branch', [CourseProgrammeController::class, 'districtsByBranch']);
Route::get('/api/centres-by-district', [CourseProgrammeController::class, 'centresByDistrict']);
Route::get('/api/constituencies-by-branch', [CourseProgrammeController::class, 'constituencyByRegion']);
Route::get('/api/centres/count/total', [CourseProgrammeController::class, 'getTotalCentresCount']);

Route::post('admin/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');



Route::prefix('admins')
    ->middleware(['auth:admin'])
    ->name('admin.')
    ->group(function () {
        // forms route
        Route::prefix('/forms')
            ->middleware('permission:form.read')
            ->name('form.')
            ->group(function () {
            Route::get('/', [FormController::class, 'index'])->name('index');
            Route::get('/fetch', [FormController::class, 'fetch'])->name('fetch');
            Route::get('/create', [FormController::class, 'create'])
                ->name('create')
                ->middleware('permission:form.create');
            Route::post('/', [FormController::class, 'store'])
                ->name('store')
                ->middleware('permission:form.create');
            Route::get('/{form}/edit', [FormController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:form.update');
            Route::put('/{form}/update', [FormController::class, 'update'])
                ->name('update')
                ->middleware('permission:form.update');
            Route::get('/{form}/preview', [FormController::class, 'preview'])->name('preview');
            Route::get('/{form}/responses', [FormController::class, 'show'])->name('show');
            Route::get('/{form}/export', [FormController::class, 'export'])
                ->name('export')
                ->middleware('permission:form.create');
            Route::post('/{form}/destroy', [FormController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:form.delete');
        });

        Route::prefix('form-responses')
            ->middleware('permission:form-response.read')
            ->name('form_responses.')
            ->group(function () {
                // form responses route
                Route::get('/', [FormController::class, 'index'])->name('index');
                Route::get('/fetch', [FormResponseController::class, 'fetch'])->name('fetch');
                Route::get('/create', [FormController::class, 'create'])
                    ->name('create')
                    ->middleware('permission:form-response.create');
                Route::get('/{response}/edit', [FormResponseController::class, 'edit'])
                    ->name('edit')
                    ->middleware('permission:form-response.update');
                Route::put('/{response}/update', [FormResponseController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:form-response.update');
                Route::get('/{response}/view', [FormResponseController::class, 'show'])->name('show');
                Route::post('/{response}/destroy', [FormResponseController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:form-response.delete');
            });

        // questionnaires route
        Route::prefix('/questionnaires')
            ->middleware('permission:form.read')
            ->name('questionnaire.')
            ->group(function () {
            Route::get('/', [QuestionnaireController::class, 'index'])->name('index');
            Route::get('/fetch', [QuestionnaireController::class, 'fetch'])->name('fetch');
            Route::get('/create', [QuestionnaireController::class, 'create'])
                ->name('create')
                ->middleware('permission:form.create');
            Route::post('/', [QuestionnaireController::class, 'store'])
                ->name('store')
                ->middleware('permission:form.create');
            Route::get('/{questionnaire}/edit', [QuestionnaireController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:form.update');
            Route::put('/{questionnaire}/update', [QuestionnaireController::class, 'update'])
                ->name('update')
                ->middleware('permission:form.update');
            Route::get('/{questionnaire}/preview', [QuestionnaireController::class, 'preview'])->name('preview');
            Route::get('/{questionnaire}/responses', [QuestionnaireController::class, 'show'])->name('show');
            Route::get('/{questionnaire}/export', [QuestionnaireController::class, 'export'])
                ->name('export')
                ->middleware('permission:form.create');
            Route::post('/{questionnaire}/destroy', [QuestionnaireController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:form.delete');
        });
    });

Route::prefix('admins')
    ->middleware('theme:dashboard')
    ->name('admin.')
    ->group(function () {
        Route::middleware(['auth:admin'])->group(function () {
            // Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
            // Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    
            Route::middleware('permission:category.read')
                ->name('category.')
                ->group(function () {
                    Route::get('/exam_category', [AdminController::class, 'exam_category'])->name('index');
                    Route::get('/category_status/{id}', [AdminController::class, 'category_status'])
                        ->name('status')
                        ->middleware('permission:category.status');
                    Route::post('/add_new_category', [AdminController::class, 'add_new_category'])
                        ->name('store')
                        ->middleware('permission:category.create');
                    Route::get('/edit_category/{id}', [AdminController::class, 'edit_category'])
                        ->name('edit')
                        ->middleware('permission:category.update');
                    Route::post('/edit_new_category', [AdminController::class, 'edit_new_category'])
                        ->name('update')
                        ->middleware('permission:category.update');
                    Route::get('/delete_category/{id}', [AdminController::class, 'delete_category'])
                        ->name('destroy')
                        ->middleware('permission:category.delete');
                });

            Route::middleware('permission:exam.read')->group(function () {
                Route::get('/manage_exam', [AdminController::class, 'manage_exam'])->name('exam.index');
                Route::get('/exam_status/{id}', [AdminController::class, 'exam_status'])
                    ->name('exam.status')
                    ->middleware('permission:exam.status');
                Route::post('/add_new_exam', [AdminController::class, 'add_new_exam'])
                    ->name('exam.store')
                    ->middleware('permission:exam.create');
                Route::get('/edit_exam/{id}', [AdminController::class, 'edit_exam'])
                    ->name('exam.edit')
                    ->middleware('permission:exam.update');
                Route::post('/edit_exam_sub', [AdminController::class, 'edit_exam_sub'])
                    ->name('exam.update')
                    ->middleware('permission:exam.update');
                Route::get('/delete_exam/{id}', [AdminController::class, 'delete_exam'])
                    ->name('exam.destroy')
                    ->middleware('permission:exam.delete');
                Route::get('/add_questions/{id}', [AdminController::class, 'add_questions'])
                    ->name('exam.questions')
                    ->middleware('permission:exam.create');
                Route::get('/question_status/{id}', [AdminController::class, 'question_status'])
                    ->name('exam.questions.status')
                    ->middleware('permission:exam.status');
                Route::get('/delete_question/{id}', [AdminController::class, 'delete_question'])
                    ->name('exam.questions.destroy')
                    ->middleware('permission:exam.delete');
                Route::get('/update_question/{id}', [AdminController::class, 'update_question'])
                    ->name('exam.questions.edit')
                    ->middleware('permission:exam.update');
                Route::post('/edit_question_inner', [AdminController::class, 'edit_question_inner'])
                    ->name('exam.questions.update')
                    ->middleware('permission:exam.update');
                Route::post('/add_new_question', [AdminController::class, 'add_new_question'])
                    ->name('exam.questions.store')
                    ->middleware('permission:exam.create');
                Route::get('/reset-exam/{exam_id}/student/{user_id}', [StudentOperation::class, 'reset_exam'])->name('reset-exam');
            });

            Route::middleware('permission:student.read')->group(function () {
                Route::get('/registered_students', [AdminController::class, 'registered_students'])
                    ->name('student.index')
                    ->middleware('permission:student.admit');
                Route::post('/student/admit', [StudentOperation::class, 'admit_student'])
                    ->name('admit_student')
                    ->middleware('permission:student.admit');
                Route::post('/admit', [AdminController::class, 'admit_student'])
                    ->name('admit_user_ui')
                    ->middleware('permission:student.admit');
                Route::get('/shortlisted_students', [AdminController::class, 'shortlisted_students'])
                    ->middleware('permission:student.admit')
                    ->name('shortlisted_students');
                Route::post('/save_shortlisted_students', [AdminController::class, 'saveShortlistedStudents'])
                    ->name('save_shortlisted_students')
                    ->middleware('permission:student.admit');
            });

            Route::get('/manage_students', [AdminController::class, 'manage_students'])->name('manage_students')->middleware('permission:student.read|student.bulk-sms|student.admit|student.email|student.shortlist');
            Route::get('/login_as_student/{id}', [AdminController::class, 'login_as_student'])->name('login_as_student')->middleware('permission:user.update');

            Route::middleware('permission:student.read')->group(function () {
                Route::get('/student_status/{id}', [AdminController::class, 'student_status'])
                    ->name('student.status')
                    ->middleware('permission:student.status');
                Route::get('/delete_students/{id}', [AdminController::class, 'delete_students'])
                    ->name('student.destroy')
                    ->middleware('permission:student.delete');
                Route::get('/delete_registered_students/{id}', [AdminController::class, 'delete_registered_students'])
                    ->name('student.destroy_registered')
                    ->middleware('permission:student.delete');
                Route::post('/edit_students_final', [AdminController::class, 'edit_students_final'])
                    ->name('student.update')
                    ->middleware('permission:student.update');
                Route::post('/add_new_students', [AdminController::class, 'add_new_students'])
                    ->name('student.store')
                    ->middleware('permission:student.create');
                Route::get('/view_answer/{id}', [StudentOperation::class, 'view_answer']);
            });

            Route::middleware('permission:attendance.read')->group(function () {
                Route::post('/confirm_attendance', [AttendanceConfirmTrait::class, 'confirmAttendance'])->middleware('permission:attendance.create');
                Route::get('/view_attendance', [AdminController::class, 'viewAttendanceByDate'])->name('viewAttendanceByDate');
                Route::get('/remove-attendance/{id}', [AttendanceViewRemoveTrait::class, 'removeAttendance'])
                    ->name('remove-attendance')
                    ->middleware('permission:attendance.delete');
                Route::get('/generate_qrcode', [AdminController::class, 'generate_qrcode_page'])->middleware('permission:attendance.create');
                // Route::post('/generate_qrcode', [AttendanceQRCodeTrait::class, 'generateQRCodeData'])->middleware('permission:attendance.create');
                // Route::post('/confirm_attendance', [AttendanceController::class, 'confirmAttendance'])->middleware('permission:attendance.create');
                Route::get('/view_attendance', [AdminController::class, 'viewAttendanceByDate'])->name('viewAttendanceByDate');
                // Route::get('/remove-attendance/{id}', [AttendanceController::class, 'removeAttendance'])
                //     ->name('remove-attendance')
                // ->middleware('permission:attendance.delete');
                Route::get('/generate_qrcode', [AdminController::class, 'generate_qrcode_page'])->middleware('permission:attendance.create');
                // Route::post('/generate_qrcode', [AttendanceController::class, 'generateQRCodeData'])->middleware('permission:attendance.create');
                Route::post('/generate_qrcode', [AttendanceQRCodeTrait::class, 'generateQRCodeData'])->middleware('permission:attendance.create');
                // Route::post('/confirm_attendance', [AttendanceController::class, 'confirmAttendance'])->middleware('permission:attendance.create');
                Route::get('/view_attendance', [AdminController::class, 'viewAttendanceByDate'])->name('viewAttendanceByDate');
                // Route::get('/remove-attendance/{id}', [AttendanceController::class, 'removeAttendance'])
                //     ->name('remove-attendance')
                // ->middleware('permission:attendance.delete');
                Route::get('/generate_qrcode', [AdminController::class, 'generate_qrcode_page'])->middleware('permission:attendance.create');
                // Route::post('/generate_qrcode', [AttendanceController::class, 'generateQRCodeData'])->middleware('permission:attendance.create');
                Route::get('/scan_qrcode', [AdminController::class, 'scan_qrcode_page'])->middleware('permission:attendance.create');
                Route::get('/verification', [AdminController::class, 'verification_page'])->name('verification');
                Route::get('/verify_details', [AdminController::class, 'verifyDetails'])->name('verify-details');
                Route::get('/reset-verify/{id}', [AdminController::class, 'reset_verify'])->name('reset-verify')->middleware('permission:student.update');
                Route::post('/verify-student/{id}', [AdminController::class, 'verifyStudent'])->name('verify-student');
            });

            Route::middleware('permission:report.read')->group(function () {
                Route::get('/reports', [AdminController::class, 'getReportView'])->name('getReportView');
                Route::post('/reports', [AdminController::class, 'generateReport'])
                    ->name('generateReport')
                    ->middleware('permission:report.create');
            });

            Route::get('/apply_exam/{id}', [AdminController::class, 'apply_exam']);

            Route::middleware('permission:result.read')->group(function () {
                Route::get('/admin_view_result/{id}', [AdminController::class, 'admin_view_result'])->middleware('admin.super');
            });

            Route::middleware('permission:admin.read')->group(function () {
                // Route::get('/get-admin-courses/{admin}', [RegisteredUserController::class, 'getAdminCourses'])->name('admin.get-admin-courses');
                // Route::post('/update-admin-courses', [RegisteredUserController::class, 'updateAdminCourses'])->name('admin.update-admin-courses');
                // Route::get('/manage_admins', [RegisteredUserController::class, 'index'])->name('manage_admins');
                // Route::get('/create', [RegisteredUserController::class, 'create'])
                //     ->name('admins.create')
                //     ->middleware('permission:admin.create');
                // Route::post('/add_new_admin', [RegisteredUserController::class, 'store'])->middleware('permission:admin.create');
                // Route::get('/edit_admin/{id}/edit', [RegisteredUserController::class, 'edit'])
                //     ->name('admins.edit')
                //     ->middleware('permission:admin.update');
                // Route::put('/{id}/update', [RegisteredUserController::class, 'update'])
                //     ->name('admins.update')
                //     ->middleware('permission:admin.update');
                // Route::delete('/{id}/delete', [RegisteredUserController::class, 'destroy'])
                //     ->name('admins.delete')
                //     ->middleware('permission:admin.delete');
                // Route::get('/is_super_admin_status/{id}', [RegisteredUserController::class, 'is_super_admin_status'])->middleware('permission:admin.status');
                // Route::get('/get-admin-courses/{admin}', [RegisteredUserController::class, 'getAdminCourses'])->name('admin.get-admin-courses');
                // Route::post('/update-admin-courses', [RegisteredUserController::class, 'updateAdminCourses'])->name('admin.update-admin-courses');
                // Route::get('/manage_admins', [RegisteredUserController::class, 'index'])->name('manage_admins');
                // Route::get('/create', [RegisteredUserController::class, 'create'])
                //     ->name('admins.create')
                //     ->middleware('permission:admin.create');
                // Route::post('/add_new_admin', [RegisteredUserController::class, 'store'])->middleware('permission:admin.create');
                // Route::get('/edit_admin/{id}/edit', [RegisteredUserController::class, 'edit'])
                //     ->name('admins.edit')
                //     ->middleware('permission:admin.update');
                // Route::put('/{id}/update', [RegisteredUserController::class, 'update'])
                //     ->name('admins.update')
                //     ->middleware('permission:admin.update');
                // Route::delete('/{id}/delete', [RegisteredUserController::class, 'destroy'])
                //     ->name('admins.delete')
                //     ->middleware('permission:admin.delete');
                // Route::get('/is_super_admin_status/{id}', [RegisteredUserController::class, 'is_super_admin_status'])->middleware('permission:admin.status');
            });

            // manage branch routes
            Route::prefix('manage-branch')
                ->middleware('permission:branch.read')
                ->name('branch.')
                ->group(function () {
                Route::get('/', [BranchController::class, 'index'])->name('index');
                Route::post('/', [BranchController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:branch.create');
                Route::get('/{id}/edit', [BranchController::class, 'edit'])
                    ->name('edit')
                    ->middleware('permission:branch.update');
                Route::put('/{branch}/update', [BranchController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:branch.update');
                Route::get('/{branch}/delete', [BranchController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:branch.delete');
                Route::get('/branch_status/{id}', [AdminController::class, 'branch_status'])
                    ->name('status')
                    ->middleware('permission:branch.status');
            });
            // end of manage branch routes
    
            // manage centre routes
            Route::prefix('manage-centre')
                ->middleware('permission:centre.read')
                ->name('centre.')
                ->group(function () {
                Route::get('/', [CentreController::class, 'index'])->name('index');
                Route::get('/centre_status/{id}', [AdminController::class, 'centre_status'])->middleware('permission:centre.status');
                Route::post('/', [CentreController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:centre.create');
                Route::get('/{id}/edit', [CentreController::class, 'edit'])
                    ->name('edit')
                    ->middleware('permission:centre.update');
                Route::put('/{centre}/update', [CentreController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:centre.update');
                Route::get('/{centre}/delete', [CentreController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:centre.delete');
            });
            // end of manage centre routes
    
            // manage programme routes
            Route::prefix('manage-programme')
                ->middleware('permission:programme.read')
                ->name('programme.')
                ->group(function () {
                Route::get('/', [ProgrammeController::class, 'index'])->name('index');
                Route::get('/programme_status/{id}', [AdminController::class, 'programme_status'])
                    ->name('status')
                    ->middleware('permission:programme.status');
                Route::post('/', [ProgrammeController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:programme.create');
                Route::get('/{id}/edit', [ProgrammeController::class, 'edit'])
                    ->name('edit')
                    ->middleware('permission:programme.update');
                Route::put('/{programme}/update', [ProgrammeController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:programme.update');
                Route::get('/{programme}/delete', [ProgrammeController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:programme.delete');
            });
            // end of manage programme routes
    
            // manage course routes
            Route::prefix('manage-course')
                ->middleware('permission:course.read')
                ->name('course.')
                ->group(function () {
                Route::get('/', [CourseController::class, 'index'])->name('index');
                Route::get('/course_status/{id}', [AdminController::class, 'course_status'])->middleware('permission:course.status');
                Route::post('/', [CourseController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:course.create');
                Route::get('/{id}/edit', [CourseController::class, 'edit'])
                    ->name('edit')
                    ->middleware('permission:course.update');
                Route::put('/{course}/update', [CourseController::class, 'update'])->name('update');
                Route::get('/{course}/delete', [CourseController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:course.delete');
                Route::get('/fetch-programme', [CourseController::class, 'fetchProgrammeDetails'])->name('fetch.programme');
                Route::get('/fetch/centre', [CourseController::class, 'fetchCentre'])->name('fetch.centre');
            });
            // end of manage course routes
    
            // manage session routes
            Route::prefix('sessions')
                ->middleware('permission:session.read')
                ->name('session.')
                ->group(function () {
                Route::get('/', [SessionController::class, 'index'])->name('index');
                Route::get('/fetch', [SessionController::class, 'fetch'])->name('fetch');
                Route::get('/create', [SessionController::class, 'create'])
                    ->name('create')
                    ->middleware('permission:session.create');
                Route::post('/', [SessionController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:session.create');
                Route::get('/{id}/edit', [SessionController::class, 'edit'])
                    ->name('edit')
                    ->middleware('permission:session.update');
                Route::put('/{session}/update', [SessionController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:session.update');
                Route::get('/{session}/delete', [SessionController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:session.delete');
            });
            // end of manage period routes
    
            // manage class schedule routes
            Route::prefix('manage-class-schedule')
                ->name('class.schedule.')
                ->group(function () {
                Route::get('/', [ClassScheduleController::class, 'index'])->name('index');
                Route::post('/', [ClassScheduleController::class, 'store'])->name('store');
                Route::post('/{id}/edit', [ClassScheduleController::class, 'edit'])->name('edit');
                Route::put('/{course}/update', [ClassScheduleController::class, 'update'])->name('update');
                Route::get('/{course}/delete', [ClassScheduleController::class, 'destroy'])->name('destroy');
            });
            // end of manage class schedule routes
    
            // manage sms_template routes
            Route::prefix('manage-sms-template')
                ->middleware('permission:sms-template.read')
                ->group(function () {
                Route::get('/', [SmsTemplateController::class, 'index'])->name('sms.template.index');
                Route::post('/', [SmsTemplateController::class, 'store'])
                    ->name('sms.template.store')
                    ->middleware('permission:sms-template.create');
                Route::get('/{id}/edit', [SmsTemplateController::class, 'edit'])
                    ->name('sms.template.edit')
                    ->middleware('permission:sms-template.update');
                Route::put('/{template}/update', [SmsTemplateController::class, 'update'])
                    ->name('sms.template.update')
                    ->middleware('permission:sms-template.update');
                Route::get('/{template}/delete', [SmsTemplateController::class, 'destroy'])
                    ->name('sms.template.destroy')
                    ->middleware('permission:sms-template.delete');
                Route::get('/fetch_sms_template', [AdminController::class, 'fetch_sms_template'])->name('fetch.sms.template');
                Route::post('/send-bulk-email', [AdminController::class, 'sendBulkEmail'])
                    ->name('send_bulk_email')
                    ->middleware('permission:student.bulk-email');
                Route::post('/send_bulk_sms', [AdminController::class, 'sendBulkSMS'])
                    ->name('send_bulk_sms')
                    ->middleware('permission:student.bulk-sms');
            });
            // end of manage sms_template routes
    
            // manage email_template routes
            Route::prefix('manage-email-template')
                ->middleware('permission:email-template.read')
                ->group(function () {
                Route::get('/', [EmailTemplateController::class, 'index'])->name('email.template.index');
                Route::post('/', [EmailTemplateController::class, 'store'])
                    ->name('email.template.store')
                    ->middleware('permission:email-template.create');
                Route::get('/{id}/edit', [EmailTemplateController::class, 'edit'])
                    ->name('email.template.edit')
                    ->middleware('permission:email-template.update');
                Route::put('/{template}/update', [EmailTemplateController::class, 'update'])
                    ->name('email.template.update')
                    ->middleware('permission:email-template.update');
                Route::get('/{template}/delete', [EmailTemplateController::class, 'destroy'])
                    ->name('email.template.destroy')
                    ->middleware('permission:email-template.delete');
                // Route::get('/fetch_email_template', [AdminController::class, 'fetch_email_template'])->name('fetch.email.template');
            });
            // end of manage emai_template routes
            Route::middleware('permission:manage.monitor')->group(function () {
                // Route::get('app-logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->middleware('permission:manage.config');
                Route::get('/app-config', [AppConfigController::class, 'index'])
                    ->name('config.index')
                    ->middleware('admin.super');
                Route::put('/app-config', [AppConfigController::class, 'update'])
                    ->name('config.update')
                    ->middleware('admin.super');
            });
        });

        Route::middleware(['auth:admin', 'permission:student.bulk-email|sudent.bulk-sms'])->group(function () {
            // Route::get('/manage-lists', [ListController::class, 'index'])->name('lists.index')->middleware('admin.super');
            Route::get('/lists/fetch', [ListController::class, 'fetch'])->name('lists.fetch');
            Route::get('/lists/view-data', [ListController::class, 'viewData'])->name('lists.view-data');
            Route::get('/lists/get-table-columns', [ListController::class, 'getTableColumns'])->name('lists.get-table-columns');
            Route::resource('/lists', ListController::class);
        });
    });

// Student section routes
Route::prefix('student')->name('student.')->group(function () {

    Route::middleware(['auth:web', 'student.verification.flow'])->group(function () {
        // Dashboard route
        Route::get('/dashboard', [StudentOperation::class, 'dashboard'])->name('dashboard');

        // Profile route
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('edit');
            Route::patch('/', [ProfileController::class, 'update'])->name('update');
        });

        // Exam route
        Route::get('/exam', [StudentOperation::class, 'exam'])->name('exam.index');
        Route::get('/join-exam/{id}', [StudentOperation::class, 'join_exam'])->name('join-exam');
        Route::post('/start-exam', [StudentOperation::class, 'start_exam'])->name('start-exam');
        Route::post('/submit-exam', [StudentOperation::class, 'submit_questions'])->name('submit-exam');

        // Application status route
        Route::get('/application-status', [StudentOperation::class, 'application_status'])->name('application-status');
        Route::get('/verification/image', [StudentOperation::class, 'verification_image'])->name('verification.image');

        // Course history route
        Route::get('/course-history', [\App\Http\Controllers\Student\CourseHistoryController::class, 'index'])->name('course-history');
        Route::get('/level-assessment', [StudentOperation::class, 'level_assessment'])->name('level-assessment');
        Route::get('/verification', [StudentOperation::class, 'verification'])->name('verification.index');
        Route::get('/verification/status', [StudentOperation::class, 'verification_status'])->name('verification.status');

        // Results route
        Route::get('/results', [StudentOperation::class, 'results'])->name('results');

        // Change course route
        Route::get('/course', [StudentOperation::class, 'change_course'])->name('course.index');
        Route::get('/course/select-center/{branch_id}', [StudentOperation::class, 'select_center'])->name('course.select-center');
        Route::get('/course/select-course', [StudentOperation::class, 'select_course'])->name('course.select-course');
        Route::get('/choose-course', [StudentOperation::class, 'change_course'])->name('change-course');
        Route::post('/update-course', [StudentOperation::class, 'update_course'])->name('update-course');

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [StudentOperation::class, 'notifications'])->name('index');
            Route::patch('/{id}/read', [StudentOperation::class, 'markNotificationAsRead'])->name('mark-read');
            Route::post('/mark-all-read', [StudentOperation::class, 'markAllNotificationsAsRead'])->name('mark-all-read');
        });

        // Course assessment route
        Route::prefix('assessment')->name('assessment.')->group(function () {
            Route::get('/', [StudentOperation::class, 'questionnaire'])->name('index');
            Route::get('/{code}', [StudentOperation::class, 'take_questionnaire'])->name('take-questionnaire');
            Route::post('/{code}', [StudentOperation::class, 'store_questionnaire'])->name('store');
        });
    });


    // Session route
    Route::middleware(['auth:web', 'is_admitted', 'student.verification.flow'])->prefix('session')->name('session.')->group(function () {
        Route::get('/', [StudentOperation::class, 'select_session_view'])->name('index');
        Route::post('/', [StudentOperation::class, 'confirm_session'])->name('store');
        Route::delete('/{user}', [StudentOperation::class, 'delete_admission'])->name('destroy');
    });

    Route::middleware('is_admitted:true')->group(function () {
        // Profile route
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [AttendanceController::class, 'viewAttendance'])->name('show');
            Route::post('/', [AttendanceController::class, 'recordAttendance'])->name('record');
        });
    });
});

/* Student section routes */
Route::prefix('student')
    ->middleware('theme:dashboard')
    ->name('student.')
    ->group(function () {
        Route::get('/select-session/{user_id}', [StudentOperation::class, 'select_session_view'])->middleware(['auth', 'is_admitted', 'student.verification.flow']);
        Route::post('/select-session/{user_id}', [StudentOperation::class, 'confirm_session'])
            ->name('select-session')
            ->middleware(['auth', 'is_admitted', 'student.verification.flow']);
        Route::delete('/delete-student-admission/{user_id}', [StudentOperation::class, 'delete_admission'])
            ->name('delete-student-admission')
            ->middleware(['auth', 'is_admitted', 'student.verification.flow']);
    });

Route::middleware(['auth:web'])->group(function () {
    // Route::get('/dashboard', [StudentOperation::class, 'dashboard'])->name('dashboard');
    // Route::get('/application-status', [StudentOperation::class, 'application_status'])->name('application-status');
    // Route::get('/profile', [StudentOperation::class, 'profile'])->name('profile')->middleware('is_not_admitted');
    // Route::get('/change-course', [StudentOperation::class, 'change_course'])->name('change-course');
    // Route::post('/update-course', [StudentOperation::class, 'update_course'])->name('update-course')->middleware('is_not_admitted');

    // Route::get('/exam', [StudentOperation::class, 'exam']);
    // Route::get('/join_exam/{id}', [StudentOperation::class, 'join_exam']);
    // Route::post('/submit_questions', [StudentOperation::class, 'submit_questions']);
    Route::get('/show_result/{id}', [StudentOperation::class, 'show_result']);
    Route::get('/apply_exam/{id}', [StudentOperation::class, 'apply_exam']);
    Route::get('/view_result/{id}', [StudentOperation::class, 'view_result']);
    Route::post('/attendance/record', [AttendanceController::class, 'recordAttendance'])->name('attendance.record')->middleware('is_admitted:true');
    // Route::get('/attendance', [AttendanceController::class, 'viewAttendance'])->name('attendance.show')->middleware('is_admitted:true');
    Route::get('/id-qrcode', [StudentOperation::class, 'get_details_page'])->middleware('is_admitted:true');
    Route::get('/scan-qrcode', [StudentOperation::class, 'get_scanner_page']);
    Route::get('/meeting-link', [StudentOperation::class, 'get_meeting_link_page']);
    Route::post('/update-details', [StudentOperation::class, 'updateDetails'])->name('updateDetails')->middleware('is_admitted');
});





Route::get('admin/roles/permissions', function (Request $request) {
    $roleIds = $request->input('role_ids', []);

    // Ensure $roleIds is an array
    if (!is_array($roleIds)) {
        $roleIds = [$roleIds];
    }

    // Filter out empty values and convert to integers
    $roleIds = array_filter(array_map('intval', $roleIds));

    if (empty($roleIds)) {
        return response()->json([]);
    }

    try {
        // Get unique permission IDs for the selected roles
        $permissionIds = \Spatie\Permission\Models\Permission::whereHas('roles', function ($q) use ($roleIds) {
            $q->whereIn('roles.id', $roleIds);
        })->pluck('id')->unique()->values()->toArray();

        // Convert to integers to ensure consistency
        $permissionIds = array_map('intval', $permissionIds);

        return response()->json($permissionIds);
    } catch (\Exception $e) {
        \Log::error('Error fetching role permissions: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to fetch permissions'], 500);
    }
})->middleware(['web']);

// Test route for debugging
Route::get('admin/test-roles', function () {
    $roles = \Spatie\Permission\Models\Role::with('permissions')->get();
    $permissions = \Spatie\Permission\Models\Permission::all();

    return response()->json([
        'roles' => $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('id')->toArray()
            ];
        }),
        'permissions' => $permissions->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name
            ];
        })
    ]);
})->middleware(['web']);


require __DIR__ . '/auth.php';

// Custom Backpack auth routes with role-based redirect
Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'namespace' => 'App\Http\Controllers\Admin\Auth',
], function () {
    // Login routes
    Route::get('login', 'LoginController@showLoginForm')->name('backpack.auth.login')->middleware('guest:admin');
    Route::post('login', 'LoginController@login')->middleware('guest:admin');
    Route::get('logout', 'LoginController@logout')->name('backpack.auth.logout');
    Route::post('logout', 'LoginController@logout');

    // Password Reset Routes - using Backpack's default controllers
    Route::get('password/reset', '\Backpack\CRUD\app\Http\Controllers\Auth\ForgotPasswordController@showLinkRequestForm')->name('backpack.auth.password.reset');
    Route::post('password/email', '\Backpack\CRUD\app\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail')->name('backpack.auth.password.email')->middleware('backpack.throttle.password.recovery:' . config('backpack.base.password_recovery_throttle_access'));
    Route::get('password/reset/{token}', '\Backpack\CRUD\app\Http\Controllers\Auth\ResetPasswordController@showResetForm')->name('backpack.auth.password.reset.token');
    Route::post('password/reset', '\Backpack\CRUD\app\Http\Controllers\Auth\ResetPasswordController@reset');
});

require __DIR__ . '/admin.php';
