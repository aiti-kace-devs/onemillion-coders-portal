<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseBatchRequest;
use App\Events\CourseBatchCreated;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\CourseBatch;
use App\Models\Course;
use App\Models\Oex_result;
use App\Models\User;
use App\Models\UserAdmission;
use App\Helpers\FilterHelper;
use App\Services\CourseBatchService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;

/**
 * Class CourseBatchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CourseBatchCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(CourseBatch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/course-batch');
        CRUD::setEntityNameStrings('Programme Batch', 'Programme Batches');
    }

    protected function setupListOperation()
    {
        $this->setupFilters();

        CRUD::addColumn([
            'name'  => 'course',
            'label' => 'Course',
            'type'  => 'relationship',
            'attribute' => 'course_name',
        ]);

        CRUD::addColumn([
            'name'  => 'batch',
            'label' => 'Admission Batch',
            'type'  => 'relationship',
            'attribute' => 'title',
        ]);

        CRUD::addColumn([
            'name'  => 'duration',
            'label' => 'Duration (days)',
            'type'  => 'number',
        ]);

        CRUD::addColumn([
            'name'  => 'start_date',
            'label' => 'Start Date',
            'type'  => 'date',
        ]);

        CRUD::addColumn([
            'name'  => 'end_date',
            'label' => 'End Date',
            'type'  => 'date',
        ]);

        CRUD::addColumn([
            'name'  => 'available_slots',
            'label' => 'Available Slots',
            'type'  => 'number',
        ]);
    }

    protected function setupShowOperation()
    {
        CRUD::set('show.setFromDb', false);
        CRUD::set('show.view', 'vendor.backpack.crud.course_batch_show');
    }

    protected function setupFilters()
    {
        CRUD::filter('ongoing')
            ->type('simple')
            ->label('Ongoing Batches')
            ->whenActive(function () {
                $this->crud->query->whereHas('batch', function ($query) {
                    $query->whereDate('start_date', '<=', now()->toDateString())
                          ->whereDate('end_date', '>=', now()->toDateString());
                });
            });

        $batches = Batch::all()->pluck('title', 'id')->toArray();
        CRUD::addFilter([
            'name'        => 'batch_id',
            'type'        => 'select2',
            'label'       => 'Admission Batch',
            'placeholder' => 'Select a batch',
        ], function () use ($batches) {
            return $batches;
        }, function ($value) {
            if ($value) {
                $this->crud->addClause('where', 'batch_id', $value);
            }
        });

        $courses = Course::all()->pluck('course_name', 'id')->toArray();
        CRUD::addFilter([
            'name'        => 'course_id',
            'type'        => 'select2_multiple',
            'label'       => 'Course',
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

    protected function setupCreateOperation()
    {
        CRUD::setValidation(CourseBatchRequest::class);

        CRUD::addField([
            'name'        => 'course_id',
            'label'       => 'Course',
            'type'        => 'select2',
            'entity'      => 'course',
            'attribute'   => 'course_name',
            'model'       => Course::class,
            'placeholder' => 'Select a course',
        ]);

        CRUD::addField([
            'name'        => 'batch_id',
            'label'       => 'Admission Batch',
            'type'        => 'select2',
            'entity'      => 'batch',
            'attribute'   => 'title',
            'model'       => Batch::class,
            'placeholder' => 'Select a batch',
        ]);

        CRUD::addField([
            'name'  => 'start_date',
            'label' => 'Start Date',
            'type'  => 'date',
        ]);

        CRUD::addField([
            'name'  => 'end_date',
            'label' => 'End Date',
            'type'  => 'date',
        ]);

        CRUD::addField([
            'name'  => 'duration',
            'label' => 'Duration (days)',
            'type'  => 'number',
            'hint'  => 'Number of days for this programme batch',
        ]);

        CRUD::addField([
            'name'  => 'available_slots',
            'label' => 'Available Slots',
            'type'  => 'number',
            'hint'  => 'Number of available seats for this batch',
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Override store to fire CourseBatchCreated event after creation.
     */
    public function store()
    {
        $response = $this->traitStore();

        $entry = $this->crud->getCurrentEntry();
        if ($entry instanceof CourseBatch) {
            event(new CourseBatchCreated($entry));
        }

        return $response;
    }

    /**
     * Auto-generate programme batches for a course within an admission batch.
     * POST admin/course-batch/generate/{courseId}
     */
    public function generate(Request $request, int $courseId, CourseBatchService $service)
    {
        if (!backpack_user()->can('batch.update.all')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'batch_id' => 'required|integer|exists:admission_batches,id',
        ]);

        $course = Course::findOrFail($courseId);
        $batch  = Batch::findOrFail($data['batch_id']);

        try {
            $batches = $service->generateForCourse($course, $batch);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Generated {$batches->count()} programme batch(es) for course [{$course->course_name}].",
            'batches' => $batches->map(fn($b) => [
                'id'              => $b->id,
                'start_date'      => $b->start_date?->toDateString(),
                'end_date'        => $b->end_date?->toDateString(),
                'available_slots' => $b->available_slots,
            ]),
        ]);
    }

    /**
     * Toggle course status.
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
            'status'  => 'success',
            'message' => 'Course status updated successfully.',
            'value'   => $course->status ? 1 : 0,
        ]);
    }

    /**
     * DataTables-compatible admitted students for a programme batch.
     */
    public function admittedStudentsData($id, Request $request)
    {
        $programmeBatch = CourseBatch::findOrFail($id);

        $draw   = (int) $request->input('draw', 0);
        $start  = max(0, (int) $request->input('start', 0));
        $length = min(max((int) $request->input('length', 10), 1), 100);

        $searchValue = trim((string) $request->input('search.value', ''));
        $searchLike  = '%' . $searchValue . '%';

        $baseQuery = UserAdmission::query()
            ->where('programme_batch_id', $id)
            ->whereNotNull('confirmed');

        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;
        if ($searchValue !== '') {
            $filteredQuery->where(function ($q) use ($searchLike) {
                $q->where('user_id', 'like', $searchLike)
                    ->orWhereHas('user', function ($uq) use ($searchLike) {
                        $uq->where('name', 'like', $searchLike)
                            ->orWhere('email', 'like', $searchLike);
                    });
            });
        }

        $recordsFiltered = (clone $filteredQuery)->count();

        $admissions = (clone $filteredQuery)
            ->select(['id', 'user_id', 'session', 'confirmed'])
            ->with(['user:id,name,email,userId', 'courseSession:id,name,course_time'])
            ->orderByDesc('confirmed')
            ->skip($start)
            ->take($length)
            ->get();

        $fallbackUserInternalIds = $admissions
            ->filter(fn($a) => $a->user === null && $a->user_id !== null && ctype_digit((string) $a->user_id))
            ->pluck('user_id')
            ->map(fn($v) => (int) $v)
            ->unique()->values()->all();

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
                    return (int) $a->user_id;
                }
                return null;
            })
            ->filter()->unique()->values()->all();

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
            $hasExam    = $latestExam !== null;
            $userName   = $user?->name ?? ($admission->user_id ?? 'N/A');
            $userEmail  = $user?->email ?? 'N/A';

            $studentHtml = $userInternalId && $user
                ? '<a href="' . e(backpack_url('user/' . $userInternalId . '/show')) . '">' . e($userName) . '</a>'
                : e($userName);

            $sessionName = $admission->courseSession?->name
                ?? ($admission->session ? ('Session #' . $admission->session) : 'Unassigned');

            $examTitle  = '-';
            $scoreHtml  = '-';
            $resultHtml = '-';
            $actionsHtml = '-';

            if ($hasExam) {
                $examTitle  = e($latestExam?->exam?->title ?? ('Exam #' . ($latestExam->exam_id ?? '')));
                $totalAns   = (int) ($latestExam->yes_ans ?? 0) + (int) ($latestExam->no_ans ?? 0);
                $scorePct   = $totalAns > 0 ? round(((int) $latestExam->yes_ans / $totalAns) * 100, 1) : 0;
                $passed     = $scorePct >= 50;
                $scoreHtml  = '<span class="badge bg-info text-dark">' . e((string) $scorePct) . '%</span>';
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
                $resultUrl = e(url('admin/admin_view_result/' . $userInternalId));
                $actions[] = '<a href="' . $resultUrl . '" data-url="' . $resultUrl . '" class="btn btn-sm btn-outline-primary js-view-result-modal">'
                    . '<i class="la la-eye"></i> View Results</a>';
            }
            $actionsHtml = !empty($actions) ? implode(' ', $actions) : '-';

            $data[] = [
                'index'     => $start + $idx + 1,
                'student'   => $studentHtml,
                'email'     => e($userEmail),
                'session'   => e($sessionName),
                'admission' => '<span class="badge bg-success text-dark">Confirmed</span>',
                'exam'      => $examTitle,
                'score'     => $scoreHtml,
                'result'    => $resultHtml,
                'actions'   => $actionsHtml,
            ];
        }

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /**
     * DataTables-compatible attendance history for a course.
     */
    public function attendanceHistoryData($id, Request $request)
    {
        $programmeBatch = CourseBatch::findOrFail($id);
        $course = Course::findOrFail($programmeBatch->course_id);

        $draw   = (int) $request->input('draw', 0);
        $start  = max(0, (int) $request->input('start', 0));
        $length = min(max((int) $request->input('length', 10), 1), 100);

        $searchValue = trim((string) $request->input('search.value', ''));
        $searchLike  = '%' . $searchValue . '%';

        $baseQuery = Attendance::query()->where('course_id', $course->id);

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
            ->unique()->values()->all();

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

            try {
                $dateStr = $record->date ? \Carbon\Carbon::parse($record->date)->format('Y-m-d') : 'N/A';
            } catch (\Throwable $e) {
                $dateStr = (string) ($record->date ?? 'N/A');
            }

            $data[] = [
                'date'    => e($dateStr),
                'student' => e($studentName),
                'course'  => e($course->course_name ?? 'N/A'),
            ];
        }

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }
}
