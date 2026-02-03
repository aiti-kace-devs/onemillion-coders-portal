<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProgrammeRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\GeneralFieldsAndColumns;
use App\Helpers\ProgrammeFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\CourseModule;
use App\Models\CourseCertification;

/**
 * Class ProgrammeCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProgrammeCrudController extends CrudController
{
    use GeneralFieldsAndColumns;
    use ProgrammeFieldHelpers;
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
        CRUD::setModel(\App\Models\Programme::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/programme');
        CRUD::setEntityNameStrings('programme', 'programmes');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        WidgetHelper::programmeStatisticsWidget();

        CRUD::column('title')->type('textarea');
        FilterHelper::addGenericRelationshipColumn('category', 'Course Category', 'course-category', 'title');
        CRUD::column('duration');
        CRUD::column('start_date');
        CRUD::column('end_date');
        FilterHelper::addBooleanColumn('status', 'status');
        FilterHelper::addDateRangeFilter('start_date', 'Start Date');
        $this->addOngoingCoursesFilter('Ongoing Programmes');
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('end_date', 'End Date');
        // FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }


    protected function setupShowOperation()
    {
        $this->setupShowCommonFields();
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ProgrammeRequest::class);

        $this->setupCreateFields();
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



    public function store()
    {
        $this->crud->setRequest($this->handleOverviewData());
        $response = $this->traitStore();
        $this->handleCourseModules($this->crud->entry, request()->input('course_modules', []));
        $this->handleCourseCertification($this->crud->entry, request()->input('course_certification', []));
        return $response;
    }




    public function update()
    {
        $this->crud->setRequest($this->handleOverviewData());
        // Combine all course_match_* values into one array called tags
        $tags = [];
        $keysToRemove = [];
        $data = request()->all();

        $programme = $this->crud->getCurrentEntry();
        $this->handleProgrammeTags($programme, $data['tags']);
        $response = $this->traitUpdate();
        $this->handleCourseModules($this->crud->entry, request()->input('course_modules', []));
        $this->handleCourseCertification($this->crud->entry, request()->input('course_certification', []));
        return $response;
    }



    protected function handleCourseModules($programme, $modules = [])
    {
        $modules = is_array($modules) ? $modules : [];

        CourseModule::where('programme_id', $programme->id)->delete();

        foreach ($modules as $module) {
            if (!empty($module['title'])) {
                CourseModule::create([
                    'programme_id' => $programme->id,
                    'title' => $module['title'],
                    'description' => $module['description'] ?? null,
                    'status' => $module['status'] ?? true,
                ]);
            }
        }
    }



    protected function handleCourseCertification($programme, $certificates = [])
    {
        $certificates = is_array($certificates) ? $certificates : [];

        CourseCertification::where('programme_id', $programme->id)->delete();
        foreach ($certificates as $certificate) {
            if (!empty($certificate['title'])) {
                CourseCertification::create([
                    'programme_id' => $programme->id,
                    'title' => $certificate['title'],
                    'type' => $certificate['type'],
                    'description' => $certificate['description'] ?? null,
                    'status' => isset($certificate['status']) ? $certificate['status'] : true,
                ]);
            }
        }
    }

    protected function handleProgrammeTags($programme, $tags = [])
    {
        $programme->tags()->sync($tags);
    }



    protected function handleOverviewData()
    {
        $request = $this->crud->getRequest();

        $overview = $request->input('overview', []);

        $processedOverview = [
            'what_you_will_learn' => array_values(array_filter($overview['what_you_will_learn'] ?? [])),
            'why_choose_this_course' => array_values(array_filter($overview['why_choose_this_course'] ?? []))
        ];

        $request->merge(['overview' => $processedOverview]);

        // Combine all course_match_* values into one array called tags
        $tags = [];
        $keysToRemove = [];

        foreach (request()->all() as $key => $value) {
            if (strpos($key, 'course_match_') === 0 && is_array($value)) {
                $tags = array_merge($tags, $value);
                $keysToRemove[] = $key;
            }
        }
        $request->merge(['tags' => $tags]);

        // Remove the course_match_* keys from the request
        foreach ($keysToRemove as $key) {
            $request->request->remove($key);
        }

        return $request;
    }
}
