<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Course;
use App\Models\Batch;
use App\Models\Programme;
use App\Models\Centre;
use App\Models\UserAdmission;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Oex_result;
use App\Helpers\FilterHelper;
use App\Helpers\CourseFieldHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * Class CourseBatchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CourseBatchCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Course::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/course-batch');
        CRUD::setEntityNameStrings('Manage Course Batches', 'Manage Course Batches');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->setupFilters();

        CRUD::addColumn([
            'name' => 'course_name',
            'label' => 'Course Name',
            'type' => 'text',
        ]);

        
        FilterHelper::addGenericRelationshipColumn('batch', 'Batch', 'batch', 'title');
        FilterHelper::addGenericRelationshipColumn('centre', 'Centre', 'centre', 'title');   

        CRUD::addColumn([
            'name' => 'duration',
            'label' => 'Duration',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type' => 'date',
        ]);

        CRUD::addColumn([
            'name' => 'end_date',
            'label' => 'End Date',
            'type' => 'date',
        ]);

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
            'toggle_url' => 'course-batch/{id}/toggle',
        ]);
    }

    /**
     * Define what happens when the Show operation is loaded.
     * 
     * @return void
     */
    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false);
        
        // Use custom show view
        CRUD::set('show.view', 'vendor.backpack.crud.course_batch_show');
        
        // // Basic info columns
        // CRUD::addColumn([
        //     'name' => 'course_name',
        //     'label' => 'Course Name',
        //     'type' => 'text',
        // ]);

        // CRUD::addColumn([
        //     'name' => 'batch_title',
        //     'label' => 'Batch',
        //     'type' => 'closure',
        //     'function_count' => 1,
        //     'function' => function($entry) {
        //         $url = url('admin/batch/'.$entry->batch_id.'/show');
        //         return '<a href="'.$url.'" class="text-primary font-bold">'.$entry->batch->title.'</a>';
        //     },
        //     'escaped' => false,
        // ]);

        // CRUD::addColumn([
        //     'name' => 'centre_title',
        //     'label' => 'Centre',
        //     'type' => 'closure',
        //     'function_count' => 1,
        //     'function' => function($entry) {
        //         $url = url('admin/centre/'.$entry->centre_id.'/show');
        //         return '<a href="'.$url.'" class="text-primary font-bold">'.$entry->centre->name.'</a>';
        //     },
        //     'escaped' => false,
        // ]);

        // CRUD::addColumn([
        //     'name' => 'duration',
        //     'label' => 'Duration',
        //     'label' => 'Duration',
        //     'type' => 'text',
        // ]);

        // CRUD::addColumn([
        //     'name' => 'start_date',
        //     'label' => 'Start Date',
        //     'type' => 'date',
        // ]);

        // CRUD::addColumn([
        //     'name' => 'end_date',
        //     'label' => 'End Date',
        //     'type' => 'date',
        // ]);
    }

    /**
     * Add filters to the list operation.
     */
    protected function setupFilters()
    {

        CRUD::filter('ongoing')
        ->type('simple')
        ->label('Ongoing Batch Courses')
        ->whenActive(function () {
            $this->crud->query->whereHas('batch', function ($query) {
                $query->whereDate('start_date', '<=', now()->toDateString())
                      ->whereDate('end_date', '>=', now()->toDateString());
            });
        });
        
        // Batch filter
        $batches = Batch::all()->pluck('title', 'id')->toArray();
        CRUD::addFilter([
            'name' => 'batch_id',
            'type' => 'select2',
            'label' => 'Batch',
            'placeholder' => 'Select a batch',
        ], function () use ($batches) {
            return $batches;
        }, function ($value) {
            if ($value) {
                $this->crud->addClause('where', 'batch_id', $value);
            }
        });

        // Course filter - handle array of course_ids from URL
        $courses = Course::all()->pluck('course_name', 'id')->toArray();
        CRUD::addFilter([
            'name' => 'course_id',
            'type' => 'select2_multiple',
            'label' => 'Course',
            'placeholder' => 'Select courses',
        ], function () use ($courses) {
            return $courses;
        }, function ($value) {
            if ($value) {
                $decoded = is_array($value) ? $value : json_decode($value, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $this->crud->addClause('whereIn', 'course_id', $decoded);
                } elseif ($decoded) {
                    $this->crud->addClause('where', 'course_id', $decoded);
                }
            }
        });
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CourseRequest::class);

        CRUD::addField([
            'name' => 'batch_id',
            'label' => 'Batch',
            'type' => 'select2',
            'entity' => 'batch',
            'attribute' => 'title',
            'model' => \App\Models\Batch::class,
            'placeholder' => 'Select a batch',
        ]);

        CRUD::addField([
            'name' => 'programme_id',
            'label' => 'Programme',
            'type' => 'select2',
            'entity' => 'programme',
            'attribute' => 'title',
            'model' => \App\Models\Programme::class,
            'placeholder' => 'Select a programme',
        ]);

        CRUD::addField([
            'name' => 'centre_id',
            'label' => 'Centre',
            'type' => 'select2',
            'entity' => 'centre',
            'attribute' => 'name',
            'model' => \App\Models\Centre::class,
            'placeholder' => 'Select a centre',
        ]);

        CRUD::addField([
            'name' => 'duration',
            'label' => 'Duration',
            'type' => 'text',
            'hint' => 'e.g., 4 Weeks, 2 Months',
        ]);

        CRUD::addField([
            'name' => 'start_date',
            'label' => 'Start Date',
            'type' => 'date',
        ]);

        CRUD::addField([
            'name' => 'end_date',
            'label' => 'End Date',
            'type' => 'date',
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Toggle course status from the List/Show view.
     */
    public function toggleStatus(Request $request, $id)
    {
        if (!backpack_user()->can('course.update.all') && !backpack_user()->can('batch.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $course = Course::findOrFail($id);
        $course->status = (bool) $data['value'];
        $course->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Course status updated successfully.',
            'value' => $course->status ? 1 : 0,
        ]);
    }

    public function admittedStudentsData($id, Request $request)
    {
        Course::findOrFail($id);

        $draw = (int) $request->input('draw', 0);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length <= 0) $length = 10;
        $length = min($length, 100);

        $searchValue = trim((string) $request->input('search.value', ''));
        $searchLike = '%' . $searchValue . '%';

        $baseQuery = UserAdmission::query()
            ->where('course_id', $id)
            ->whereNotNull('confirmed');

        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;
        if ($searchValue !== '') {
            $filteredQuery->where(function ($q) use ($searchLike) {
                $q->where('user_id', 'like', $searchLike)
                    ->orWhereHas('user', function ($uq) use ($searchLike) {
                        $uq->where('name', 'like', $searchLike)
                            ->orWhere('email', 'like', $searchLike);
                    })
                    ->orWhereHas('courseSession', function ($sq) use ($searchLike) {
                        $sq->where('name', 'like', $searchLike)
                            ->orWhere('course_time', 'like', $searchLike);
                    });
            });
        }

        $recordsFiltered = (clone $filteredQuery)->count();

        $admissions = (clone $filteredQuery)
            ->select(['id', 'user_id', 'session', 'confirmed'])
            ->with([
                'user:id,name,email,userId',
                'courseSession:id,name,course_time',
            ])
            ->orderByDesc('confirmed')
            ->skip($start)
            ->take($length)
            ->get();

        $fallbackUserInternalIds = $admissions
            ->filter(fn($a) => $a->user === null && $a->user_id !== null && ctype_digit((string) $a->user_id))
            ->pluck('user_id')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $fallbackUsersById = collect();
        if (!empty($fallbackUserInternalIds)) {
            $fallbackUsersById = User::query()
                ->whereIn('id', $fallbackUserInternalIds)
                ->get(['id', 'name', 'email', 'userId'])
                ->keyBy('id');
        }

        $internalIdsForExamLookup = $admissions
            ->map(function ($a) use ($fallbackUsersById) {
                if ($a->user?->id) return (int) $a->user->id;
                if ($a->user_id !== null && ctype_digit((string) $a->user_id)) {
                    $candidate = (int) $a->user_id;
                    return $fallbackUsersById->has($candidate) ? $candidate : $candidate;
                }
                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $latestExamByUserId = collect();
        if (!empty($internalIdsForExamLookup)) {
            $latestExamByUserId = Oex_result::query()
                ->whereIn('user_id', $internalIdsForExamLookup)
                ->with('exam:id,title')
                ->orderByDesc('created_at')
                ->get(['id', 'user_id', 'exam_id', 'yes_ans', 'no_ans', 'created_at'])
                ->unique('user_id')
                ->keyBy('user_id');
        }

        $data = [];
        foreach ($admissions as $idx => $admission) {
            $user = $admission->user;
            if (!$user && $admission->user_id !== null && ctype_digit((string) $admission->user_id)) {
                $user = $fallbackUsersById[(int) $admission->user_id] ?? null;
            }

            $userInternalId = $user?->id;
            if (!$userInternalId && $admission->user_id !== null && ctype_digit((string) $admission->user_id)) {
                $userInternalId = (int) $admission->user_id;
            }

            $latestExam = $userInternalId ? ($latestExamByUserId[$userInternalId] ?? null) : null;
            $hasExam = $latestExam !== null;

            $userName = $user?->name ?? ($admission->user_id ?? 'N/A');
            $userEmail = $user?->email ?? 'N/A';

            $studentHtml = $userInternalId && $user
                ? '<a href="' . e(backpack_url('user/' . $userInternalId . '/show')) . '">' . e($userName) . '</a>'
                : e($userName);

            $sessionName = $admission->courseSession?->name
                ?? ($admission->session ? ('Session #' . $admission->session) : 'Unassigned');

            $examTitle = '-';
            $scoreHtml = '-';
            $resultHtml = '-';
            $actionsHtml = '-';

            if ($hasExam) {
                $examTitle = e($latestExam?->exam?->title ?? ('Exam #' . ($latestExam->exam_id ?? '')));

                $totalAns = (int) ($latestExam->yes_ans ?? 0) + (int) ($latestExam->no_ans ?? 0);
                $scorePct = $totalAns > 0 ? round(((int) $latestExam->yes_ans / $totalAns) * 100, 1) : 0;
                $passed = $scorePct >= 50;

                $scoreHtml = '<span class="badge bg-info text-dark">' . e((string) $scorePct) . '%</span>';
                $resultHtml = '<span class="badge ' . ($passed ? 'bg-success' : 'bg-danger') . '">' . ($passed ? 'Pass' : 'Fail') . '</span>';
            } else {
                $examTitle = '<span class="badge bg-secondary text-dark">Not taken</span>';
            }

            $actions = [];
            if ($userInternalId) {
                $actions[] = '<a href="' . e(backpack_url('manage-student/' . $userInternalId . '/show')) . '" class="btn btn-sm btn-outline-secondary">'
                    . '<i class="la la-chart-bar"></i> View Metrics</a>';
            }
            if ($hasExam && $userInternalId) {
                $actions[] = '<a href="' . e(url('admin/admin_view_result/' . $userInternalId)) . '" class="btn btn-sm btn-outline-primary" target="_blank">'
                    . '<i class="la la-eye"></i> View Results</a>';
            }
            $actionsHtml = !empty($actions) ? implode(' ', $actions) : '-';

            $data[] = [
                'index' => $start + $idx + 1,
                'student' => $studentHtml,
                'email' => e($userEmail),
                'session' => e($sessionName),
                'admission' => '<span class="badge bg-success text-dark">Confirmed</span>',
                'exam' => $examTitle,
                'score' => $scoreHtml,
                'result' => $resultHtml,
                'actions' => $actionsHtml,
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function attendanceHistoryData($id, Request $request)
    {
        $course = Course::findOrFail($id);

        $draw = (int) $request->input('draw', 0);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length <= 0) $length = 10;
        $length = min($length, 100);

        $searchValue = trim((string) $request->input('search.value', ''));
        $searchLike = '%' . $searchValue . '%';

        $baseQuery = Attendance::query()
            ->where('course_id', $id);

        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;
        if ($searchValue !== '') {
            $filteredQuery->where(function ($q) use ($searchLike) {
                $q->where('user_id', 'like', $searchLike)
                    ->orWhere('date', 'like', $searchLike)
                    ->orWhereHas('user', function ($uq) use ($searchLike) {
                        $uq->where('name', 'like', $searchLike)
                            ->orWhere('email', 'like', $searchLike);
                    });
            });
        }

        $recordsFiltered = (clone $filteredQuery)->count();

        $records = (clone $filteredQuery)
            ->select(['id', 'user_id', 'date'])
            ->with(['user:id,name,userId'])
            ->orderByDesc('date')
            ->skip($start)
            ->take($length)
            ->get();

        $fallbackUserInternalIds = $records
            ->filter(fn($r) => $r->user === null && $r->user_id !== null && ctype_digit((string) $r->user_id))
            ->pluck('user_id')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $fallbackUsersById = collect();
        if (!empty($fallbackUserInternalIds)) {
            $fallbackUsersById = User::query()
                ->whereIn('id', $fallbackUserInternalIds)
                ->get(['id', 'name', 'userId'])
                ->keyBy('id');
        }

        $data = [];
        foreach ($records as $record) {
            $user = $record->user;
            if (!$user && $record->user_id !== null && ctype_digit((string) $record->user_id)) {
                $user = $fallbackUsersById[(int) $record->user_id] ?? null;
            }

            $studentName = $user?->name ?? ($record->user_id ?? 'N/A');

            $dateStr = 'N/A';
            try {
                $dateStr = $record->date ? \Carbon\Carbon::parse($record->date)->format('Y-m-d') : 'N/A';
            } catch (\Throwable $e) {
                $dateStr = (string) ($record->date ?? 'N/A');
            }

            $data[] = [
                'date' => e($dateStr),
                'student' => e($studentName),
                'course' => e($course->course_name ?? 'N/A'),
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}
