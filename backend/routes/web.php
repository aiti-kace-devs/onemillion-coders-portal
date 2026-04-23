<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\StudentOperation;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\ConstituencyCrudController;
use App\Http\Controllers\Admin\Api\FormPreviewController;
use App\Http\Controllers\Admin\Api\CourseProgrammeController;
use App\Http\Controllers\Admin\Api\CourseMatchAPIController;
use App\Http\Controllers\Admin\Api\RegistrationFormAPIController;
use App\Http\Controllers\FormResponseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Log;

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

// Student section routes
Route::prefix('student')->name('student.')->group(function () {

    Route::middleware(['auth:web', 'student.onboarding', 'student.verification.flow'])->group(function () {
        Route::get('/application-review', [StudentOperation::class, 'application_review'])->name('application-review.index');
        Route::post('/application-review/complete', [StudentOperation::class, 'complete_application_review'])->name('application-review.complete');

        Route::get('/partner-login/{partner_slug}', [StudentOperation::class, 'partner_login'])->name('partner-login');

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
    Route::middleware(['auth:web', 'is_admitted', 'student.onboarding', 'student.verification.flow'])->prefix('session')->name('session.')->group(function () {
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
        Route::get('/select-session/{user_id}', [StudentOperation::class, 'select_session_view'])->middleware(['auth', 'is_admitted', 'student.onboarding', 'student.verification.flow']);
        Route::post('/select-session/{user_id}', [StudentOperation::class, 'confirm_session'])
            ->name('select-session')
            ->middleware(['auth', 'is_admitted', 'student.onboarding', 'student.verification.flow']);
        Route::delete('/delete-student-admission/{user_id}', [StudentOperation::class, 'delete_admission'])
            ->name('delete-student-admission')
            ->middleware(['auth', 'is_admitted', 'student.onboarding', 'student.verification.flow']);
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
    } catch (Exception $e) {
        Log::error('Error fetching role permissions: ' . $e->getMessage());
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
