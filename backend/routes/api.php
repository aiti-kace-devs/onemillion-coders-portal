<?php

use App\Http\Controllers\Admin\Api\CourseMatchAPIController;
use App\Http\Controllers\Admin\BatchCrudController;
use App\Http\Controllers\StatamicEntryApiController;
use App\Http\Controllers\StudentOperation;
use App\Http\Controllers\CampaignTargetingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('batch/add-courses/{batch}', [BatchCrudController::class, 'addCourses'])
    ->name('batch.add-courses');
Route::post('/recommend/courses', [CourseMatchAPIController::class, 'recommendCourses']);

// Campaign targeting endpoints
Route::prefix('campaign-targeting')->name('api.campaign-targeting.')->group(function () {
    Route::get('/branches', [CampaignTargetingController::class, 'getBranches'])->name('branches');
    Route::post('/districts', [CampaignTargetingController::class, 'getDistrictsByBranches'])->name('districts');
    Route::post('/centres', [CampaignTargetingController::class, 'getCentresByDistricts'])->name('centres');
    Route::post('/courses', [CampaignTargetingController::class, 'getCoursesByCentres'])->name('courses');
    Route::post('/sessions', [CampaignTargetingController::class, 'getSessionsByCourses'])->name('sessions');
});

// Availability endpoint
Route::get('/availability', [\App\Http\Controllers\AvailabilityController::class, 'index'])->name('api.availability');

// Availability endpoints — authenticated (iframed into student portal)
Route::prefix('availability')->name('api.availability.')->middleware(['user.token', 'student.onboarding', 'student.verification.flow'])->group(function () {
    Route::get('/batches', [\App\Http\Controllers\AvailabilityController::class, 'batches'])->name('batches');
    Route::get('/in-person/batches', [\App\Http\Controllers\InPersonAvailabilityController::class, 'batches'])->name('in-person-batches');
    Route::get('/sibling-centres', [\App\Http\Controllers\AvailabilityController::class, 'siblingCentres'])->name('sibling-centres');
    Route::get('/sibling-courses', [App\Http\Controllers\Admin\Api\CourseMatchAPIController::class, 'siblingCourses'])->name('sibling-courses');
});

// Programme availability per centre — authenticated
Route::get('/programmes/{programmeId}/availability-per-centre', [
    App\Http\Controllers\Admin\Api\CourseProgrammeController::class, 'availabilityPerCentre'
])->name('api.programmes.availability-per-centre');


// Booking endpoints — student reserves/cancels a programme_batch slot
Route::prefix('bookings')->name('api.bookings.')->middleware(['user.token', 'student.onboarding', 'student.verification.flow'])->group(function () {
    Route::get('/mine', [\App\Http\Controllers\BookingController::class, 'mine'])->name('mine');
    Route::post('/', [\App\Http\Controllers\BookingController::class, 'store'])->name('store');
    Route::delete('/{booking}', [\App\Http\Controllers\BookingController::class, 'destroy'])->name('destroy');
});

// Legacy in-person enrollment endpoint; kept as a compatibility alias to the shared booking flow.
Route::post('/in-person-enrollment', [\App\Http\Controllers\InPersonEnrollmentController::class, 'store'])
    ->middleware(['user.token', 'student.onboarding', 'student.verification.flow'])
    ->name('api.in-person-enrollment.store');

// Waitlist endpoints — authenticated
Route::prefix('waitlist')->name('api.waitlist.')->middleware(['user.token', 'student.onboarding', 'student.verification.flow'])->group(function () {
    Route::get('/mine', [\App\Http\Controllers\AdmissionWaitlistController::class, 'index'])->name('mine');
    Route::post('/add', [\App\Http\Controllers\AdmissionWaitlistController::class, 'store'])->name('add');
    Route::post('/convert/{waitlistId}', [\App\Http\Controllers\AdmissionWaitlistController::class, 'convert'])->name('convert');
    Route::get('/check/{courseId}', [\App\Http\Controllers\AdmissionWaitlistController::class, 'check'])->name('check');
    Route::get('/count/{courseId}', [\App\Http\Controllers\AdmissionWaitlistController::class, 'count'])->name('count');
    Route::delete('/{courseId}', [\App\Http\Controllers\AdmissionWaitlistController::class, 'destroy'])->name('destroy');
});

Route::get('/courses/slot-left', [CourseMatchAPIController::class, 'courseSlotLeft']);
Route::get('/courses/{courseId}/slot-left', [CourseMatchAPIController::class, 'courseSlotLeft']);
// Route::post('/course-match/recommend', [CourseMatchAPIController::class, 'recommend']);
// Route::post('/course-match/full-recommend', [CourseMatchAPIController::class, 'fullRecommendation']);

// Route::middleware('auth:sanctum')->group(function () {
//         Route::group(function () {

//             Route::get('/user', function (Request $request) {
//         return $request->user();
//     });
// });
Route::prefix('ghana-card')->middleware(['user.token', 'student.onboarding'])->group(function () {
    Route::post('/verify', [\App\Http\Controllers\Api\GhanaCardController::class, 'verify']);
    Route::get('/status', [\App\Http\Controllers\Api\GhanaCardController::class, 'status']);
});

// Route::middleware('apikey.check')->group(function () {
//     Route::post('/student/register', [AdminController::class, 'add_new_students']);
//     // Route::post('/student/admit', [StudentOperation::class, 'admit_student'])->name('admit_student');
//     Route::get('/generate_qrcode', [AdminController::class, 'generate_qrcode_page']);
// });

// Route::post('/api/add-student', [FormResponseController::class, 'store']);
// Route::post('/api/add-student', [CreateStudentAPIController::class, 'store'])->middleware('api'); // This applies the api middleware group

// Tiered Assessment endpoints (External API) — user resolved from JWT (Bearer, token, or user_id param)
Route::prefix('tiered-assessment')->name('api.tiered-assessment.')->middleware(['user.token', 'student.onboarding'])->group(function () {
    Route::get('/fetch', [StudentOperation::class, 'fetch_assessment_question'])->name('fetch');
    Route::post('/submit', [StudentOperation::class, 'submit_assessment_answer'])->name('submit');
    Route::post('/record-violation', [StudentOperation::class, 'record_assessment_violation'])->name('record-violation');
});

// Route::get('/recommended-courses', [StudentOperation::class, 'recommendCourses'])->name('recommended-courses');
/*
|--------------------------------------------------------------------------
| Statamic Custom API Routes
|--------------------------------------------------------------------------
|
| Custom API endpoints for Statamic entries with eager loading
|
*/
Route::get('/pages/footer', [StatamicEntryApiController::class, 'footer']);
Route::get('/pages/{slug}', [StatamicEntryApiController::class, 'showPageBySlug']);

Route::get('/forms/{handle}', [StatamicEntryApiController::class, 'showFormByHandle']);
Route::post('/forms/{handle}', [StatamicEntryApiController::class, 'saveFormByHandle']);

Route::name('custom.')->group(function () {
    // Get all entries with optional filtering and related entries
    Route::get('/entries', [StatamicEntryApiController::class, 'index']);

    // Get a single entry by ID
    Route::get('/entries/{id}', [StatamicEntryApiController::class, 'show']);

    // Get entries by collection
    Route::get('/collections/{collection}/entries', [StatamicEntryApiController::class, 'byCollection']);

    // Get available collections
    Route::get('/collections', [StatamicEntryApiController::class, 'collections']);

    // Search entries
    Route::get('/search', [StatamicEntryApiController::class, 'search']);
});
