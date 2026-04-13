<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseBatchRequest;
use App\Events\CourseBatchCreated;
use App\Helpers\CrudListHelper;
use App\Helpers\FilterHelper;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\CourseBatch;
use App\Models\Course;
use App\Models\Oex_result;
use App\Models\User;
use App\Models\UserAdmission;
use App\Services\CourseBatchService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
        CrudListHelper::editInDropdown();
        $this->setupFilters();

        CRUD::addColumn([
            'name'      => 'course',
            'label'     => 'Course',
            'type'      => 'relationship',
            'attribute' => 'course_name',
        ]);

        CRUD::addColumn([
            'name'      => 'batch',
            'label'     => 'Admission Batch',
            'type'      => 'relationship',
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

        FilterHelper::addSelectFilter(
            'batch_id',
            'Admission Batch',
            Batch::query()->pluck('title', 'id')->toArray(),
            'select2'
        );

        FilterHelper::addSelectFilter(
            'course_id',
            'Course',
            Course::query()->pluck('course_name', 'id')->toArray(),
            'select2_multiple'
        );
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
     * Override store to fire CourseBatchCreated after single-record CRUD creation.
     * (Bulk generation via generate() fires events from CourseBatchService.)
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

        $course = Course::with(['programme', 'centre'])->findOrFail($courseId);
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
     * DataTables-compatible admitted students for a programme batch.
     */
    public function admittedStudentsData($id, Request $request)
    {
        CourseBatch::findOrFail($id);

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

        [$fallbackUsersById, $internalIds] = $this->resolveFallbackUsers($admissions);

        $latestExamByUserId = collect();
        if (!empty($internalIds)) {
            $latestExamByUserId = Oex_result::query()
                ->whereIn('user_id', $internalIds)
                ->with('exam:id,title')
                ->orderByDesc('created_at')
                ->get(['id', 'user_id', 'exam_id', 'yes_ans', 'no_ans', 'created_at'])
                ->unique('user_id')
                ->keyBy('user_id');
        }

        $data = [];
        foreach ($admissions as $idx => $admission) {
            [$user, $userInternalId] = $this->resolveUser($admission, $fallbackUsersById);

            $latestExam  = $userInternalId ? ($latestExamByUserId[$userInternalId] ?? null) : null;
            $hasExam     = $latestExam !== null;
            $userName    = $user?->name ?? ($admission->user_id ?? 'N/A');
            $userEmail   = $user?->email ?? 'N/A';

            $studentHtml = $userInternalId && $user
                ? '<a href="' . e(backpack_url('user/' . $userInternalId . '/show')) . '">' . e($userName) . '</a>'
                : e($userName);

            $sessionName = $admission->courseSession?->name
                ?? ($admission->session ? ('Session #' . $admission->session) : 'Unassigned');

            [$examTitle, $scoreHtml, $resultHtml] = $this->buildExamColumns($latestExam);

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

            $data[] = [
                'index'     => $start + $idx + 1,
                'student'   => $studentHtml,
                'email'     => e($userEmail),
                'session'   => e($sessionName),
                'admission' => '<span class="badge bg-success text-dark">Confirmed</span>',
                'exam'      => $examTitle,
                'score'     => $scoreHtml,
                'result'    => $resultHtml,
                'actions'   => !empty($actions) ? implode(' ', $actions) : '-',
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
     * DataTables-compatible attendance history for a programme batch (scoped to batch dates).
     */
    public function attendanceHistoryData($id, Request $request)
    {
        $programmeBatch = CourseBatch::findOrFail($id);
        $course         = Course::findOrFail($programmeBatch->course_id);

        $draw   = (int) $request->input('draw', 0);
        $start  = max(0, (int) $request->input('start', 0));
        $length = min(max((int) $request->input('length', 10), 1), 100);

        $searchValue = trim((string) $request->input('search.value', ''));
        $searchLike  = '%' . $searchValue . '%';

        $baseQuery = Attendance::query()
            ->where('course_id', $course->id)
            ->whereBetween('date', [
                $programmeBatch->start_date->toDateString(),
                $programmeBatch->end_date->toDateString(),
            ]);

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

        [$fallbackUsersById] = $this->resolveFallbackUsers($records);

        $data = [];
        foreach ($records as $record) {
            $user = $record->user;
            if (!$user && $record->user_id !== null && ctype_digit((string) $record->user_id)) {
                $user = $fallbackUsersById[(int) $record->user_id] ?? null;
            }

            try {
                $dateStr = $record->date ? \Carbon\Carbon::parse($record->date)->format('Y-m-d') : 'N/A';
            } catch (\Throwable $e) {
                $dateStr = (string) ($record->date ?? 'N/A');
            }

            $data[] = [
                'date'    => e($dateStr),
                'student' => e($user?->name ?? ($record->user_id ?? 'N/A')),
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

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * For records where the Eloquent `user` relation is null but `user_id` looks like
     * an internal integer PK, do a single batch lookup and return a keyed collection.
     * Also returns the flat array of internal IDs for downstream exam lookups.
     *
     * @return array{Collection, array<int>}
     */
    private function resolveFallbackUsers(Collection $records): array
    {
        $integerIds = $records
            ->filter(fn($r) => $r->user === null && $r->user_id !== null && ctype_digit((string) $r->user_id))
            ->pluck('user_id')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $fallbackById = collect();
        if (!empty($integerIds)) {
            $fallbackById = User::query()
                ->whereIn('id', $integerIds)
                ->get(['id', 'name', 'email', 'userId'])
                ->keyBy('id');
        }

        // Collect all internal IDs (from loaded relation or fallback) for exam lookups
        $allInternalIds = $records
            ->map(function ($r) use ($fallbackById) {
                if ($r->user?->id) {
                    return (int) $r->user->id;
                }
                if ($r->user_id !== null && ctype_digit((string) $r->user_id)) {
                    return (int) $r->user_id;
                }
                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [$fallbackById, $allInternalIds];
    }

    /** @return array{string, string, string} [examTitle, scoreHtml, resultHtml] */
    private function buildExamColumns(?object $latestExam): array
    {
        if (!$latestExam) {
            return ['<span class="badge bg-secondary text-dark">Not taken</span>', '-', '-'];
        }

        $examTitle = e($latestExam->exam?->title ?? ('Exam #' . ($latestExam->exam_id ?? '')));
        $totalAns  = (int) ($latestExam->yes_ans ?? 0) + (int) ($latestExam->no_ans ?? 0);
        $scorePct  = $totalAns > 0 ? round(((int) $latestExam->yes_ans / $totalAns) * 100, 1) : 0;
        $passed    = $scorePct >= 50;

        return [
            $examTitle,
            '<span class="badge bg-info text-dark">' . e((string) $scorePct) . '%</span>',
            '<span class="badge ' . ($passed ? 'bg-success' : 'bg-danger') . '">' . ($passed ? 'Pass' : 'Fail') . '</span>',
        ];
    }

    /** @return array{?User, ?int} */
    private function resolveUser(object $record, Collection $fallbackById): array
    {
        $user = $record->user;
        if (!$user && $record->user_id !== null && ctype_digit((string) $record->user_id)) {
            $user = $fallbackById[(int) $record->user_id] ?? null;
        }

        $internalId = $user?->id;
        if (!$internalId && $record->user_id !== null && ctype_digit((string) $record->user_id)) {
            $internalId = (int) $record->user_id;
        }

        return [$user, $internalId];
    }
}
