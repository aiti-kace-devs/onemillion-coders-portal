<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentOperation;
use App\Http\Controllers\FormResponseController;
use App\Http\Controllers\StatamicEntryApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Api\CourseMatchAPIController;
use App\Http\Controllers\Admin\BatchCrudController;
use App\Http\Controllers\Admin\Api\CreateStudentAPIController;
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
// Route::post('/course-match/recommend', [CourseMatchAPIController::class, 'recommend']);
// Route::post('/course-match/full-recommend', [CourseMatchAPIController::class, 'fullRecommendation']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// Route::middleware('apikey.check')->group(function () {
//     Route::post('/student/register', [AdminController::class, 'add_new_students']);
//     // Route::post('/student/admit', [StudentOperation::class, 'admit_student'])->name('admit_student');
//     Route::get('/generate_qrcode', [AdminController::class, 'generate_qrcode_page']);
// });

// Route::post('/api/add-student', [FormResponseController::class, 'store']);
// Route::post('/api/add-student', [CreateStudentAPIController::class, 'store'])->middleware('api'); // This applies the api middleware group


// Tiered Assessment endpoints (External API) — user resolved from JWT (Bearer, token, or user_id param)
Route::prefix('tiered-assessment')->name('api.tiered-assessment.')->middleware('user.token')->group(function () {
    Route::get('/fetch', [StudentOperation::class, 'fetch_assessment_question'])->name('fetch');
    Route::post('/submit', [StudentOperation::class, 'submit_assessment_answer'])->name('submit');
    Route::post('/record-violation', [StudentOperation::class, 'record_assessment_violation'])->name('record-violation');
});
/*
|--------------------------------------------------------------------------
| Statamic Custom API Routes
|--------------------------------------------------------------------------
|
| Custom API endpoints for Statamic entries with eager loading
|
*/
Route::get('/pages/footer', [StatamicEntryApiController::class, 'footer']);
Route::get('/pages/privacy', [StatamicEntryApiController::class, 'privacy']);
Route::get('/pages/terms', [StatamicEntryApiController::class, 'terms']);
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
