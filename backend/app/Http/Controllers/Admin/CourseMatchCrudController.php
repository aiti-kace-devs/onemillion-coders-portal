<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CourseMatchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\CourseFieldHelpers;
use Illuminate\Support\Facades\DB;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Services\CourseMatchReferenceService;

/**
 * Class CourseMatchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CourseMatchCrudController extends CrudController
{
    use CourseFieldHelpers;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\CourseMatch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/course-match');
        CRUD::setEntityNameStrings('course match', 'course matches');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        WidgetHelper::courseMatchStatisticsWidget();

        CRUD::column('tag');
        CRUD::column('question');
        CRUD::column('description');
        CRUD::column('description');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created Date');
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CourseMatchRequest::class);
        $this->courseMatchFields();
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */ protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();

        $courseMatch = $this->crud->getCurrentEntry();

        $options = $courseMatch->courseMatchOptions()->get()->map(function ($option) {
            return [
                'answer' => $option->answer,
                'value' => $option->value,
                'icon' => $option->icon,
                'description' => $option->description,
                'status' => $option->status,
            ];
        })->toArray();

        $this->crud->modifyField('course_match_options', ['value' => $options]);
    }




    public function store()
    {
        $response = $this->traitStore();
        $this->handleCourseMatchOptions($this->crud->entry, request()->input('course_match_options', []));
        return $response;
    }

    public function update()
    {
        $response = $this->traitUpdate();
        $this->handleCourseMatchOptions($this->crud->entry, request()->input('course_match_options', []));
        return $response;
    }

    protected function handleCourseMatchOptions($courseMatch, $options)
    {
        $referenceSource = request()->input('reference_source') ?? $courseMatch->reference_source;
        $referenceService = app(CourseMatchReferenceService::class);
        $referenceOptions = $referenceService->getReferenceOptions($referenceSource);

        if ($referenceSource && $referenceOptions !== null) {
            $referenceService->syncCourseMatchOptions($courseMatch, $referenceOptions);
            return;
        }

        $existingOptionIds = $courseMatch->courseMatchOptions()->pluck('id')->toArray();
        $incomingOptionIds = collect($options)->pluck('id')->filter()->toArray();

        $toDelete = array_diff($existingOptionIds, $incomingOptionIds);
        if (!empty($toDelete)) {
            DB::table('programme_course_match_options')
                ->whereIn('course_match_option_id', $toDelete)
                ->delete();

            $courseMatch->courseMatchOptions()->whereIn('id', $toDelete)->delete();
        }
        foreach ($options as $option) {
            if (!empty($option['answer'])) {
                if (!empty($option['id'])) {
                    $courseMatch->courseMatchOptions()->where('id', $option['id'])->update([
                        'answer' => $option['answer'],
                        'value' => $option['value'] ?? \Str::slug($option['answer']),
                        'icon' => $option['icon'] ?? null,
                        'description' => $option['description'] ?? null,
                        'status' => $option['status'] ?? 1,
                    ]);
                } else {
                    $courseMatch->courseMatchOptions()->create([
                        'answer' => $option['answer'],
                        'value' => $option['value'] ?? \Str::slug($option['answer']),
                        'icon' => $option['icon'] ?? null,
                        'description' => $option['description'] ?? null,
                        'status' => $option['status'] ?? 1,
                    ]);
                }
            }
        }
    }

    public function toggleStatus(\Illuminate\Http\Request $request, $id)
    {
        $this->crud->hasAccessOrFail('update');

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $courseMatch = \App\Models\CourseMatch::findOrFail($id);
        $courseMatch->status = (bool) $data['value'];
        $courseMatch->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Course match status updated successfully.',
            'value' => $courseMatch->status ? 1 : 0,
        ]);
    }
}
