<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProgrammeRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\GeneralFieldsAndColumns;
use App\Helpers\ProgrammeFieldHelpers;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Helpers\MediaHelper;
use App\Models\CourseModule;
use App\Models\CourseCertification;
use App\Helpers\CrudListHelper;
use App\Models\Programme;
use App\Events\OnlineProgrammeSaved;

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
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation {
        destroy as traitDestroy;
    }
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
        CrudListHelper::editInDropdown();
        WidgetHelper::programmeStatisticsWidget();

        CRUD::column('title')->type('textarea');
        CRUD::column('mode_of_delivery')->type('text');
        CRUD::column('level')->type('text');
        CRUD::column('provider')->type('text');
        FilterHelper::addGenericRelationshipColumn('category', 'Course Category', 'course-category', 'title');
        CRUD::column('duration');
        CRUD::column('duration_in_days');
        CRUD::column('time_allocation');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        FilterHelper::addDateRangeFilter('start_date', 'Start Date');
        $this->addOngoingCoursesFilter('Ongoing Programmes');
        $deliveryModes = Programme::query()
            ->whereNotNull('mode_of_delivery')
            ->select('mode_of_delivery')
            ->distinct()
            ->orderBy('mode_of_delivery')
            ->pluck('mode_of_delivery', 'mode_of_delivery')
            ->toArray();

        $programmeLevel = Programme::query()
            ->whereNotNull('level')
            ->select('level')
            ->distinct()
            ->orderBy('level')
            ->pluck('level', 'level')
            ->toArray();
        FilterHelper::addSelectFilter('mode_of_delivery', 'Mode of Delivery', $deliveryModes, 'select2');
        FilterHelper::addSelectFilter('level', 'Level', $programmeLevel, 'select2');
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addTagsFilter('programmeTags', 'Tags');
        FilterHelper::addDateRangeFilter('end_date', 'End Date');
        // FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }


    protected function setupShowOperation()
    {
        // $this->setupShowCommonFields();
        CRUD::column('title')->type('textarea');
        FilterHelper::addGenericRelationshipColumn('category', 'Course Category', 'course-category', 'title');
        CRUD::column('duration');
        CRUD::column('start_date');
        CRUD::column('end_date');
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'view',
            'view' => 'admin.status_toggle.status_column',
        ]);
        MediaHelper::previewMediaImagesFile('image', 'Image');
        FilterHelper::addDateRangeFilter('start_date', 'Start Date');
        $this->addOngoingCoursesFilter('Ongoing Programmes');
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addTagsFilter('programmeTags', 'Tags');
        FilterHelper::addDateRangeFilter('end_date', 'End Date');
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

    public function toggleStatus(\Illuminate\Http\Request $request, $id)
    {
        $this->crud->hasAccessOrFail('update');

        $data = $request->validate([
            'value' => 'required|boolean',
        ]);

        $programme = \App\Models\Programme::findOrFail($id);
        $programme->status = (bool) $data['value'];
        $programme->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Programme status updated successfully.',
            'value' => $programme->status ? 1 : 0,
        ]);
    }



    public function store()
    {
        $this->crud->setRequest($this->handleOverviewData());
        $this->normalizeImagePath();
        $response = $this->traitStore();
        $this->handleCourseModules($this->crud->entry, request()->input('course_modules', []));
        $this->handleCourseCertification($this->crud->entry, request()->input('course_certification', []));

        // Check if mode_of_delivery is online
        // if ($this->crud->entry->mode_of_delivery === 'Online') {
        //     event(new OnlineProgrammeSaved($this->crud->entry));
        // }

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
        $this->normalizeImagePath();
        $response = $this->traitUpdate();
        $this->handleCourseModules($this->crud->entry, request()->input('course_modules', []));
        $this->handleCourseCertification($this->crud->entry, request()->input('course_certification', []));

        // Check if mode_of_delivery is online
        // if ($this->crud->entry->mode_of_delivery === 'Online') {
        //     event(new OnlineProgrammeSaved($this->crud->entry));
        // }

        // Check if mode_of_delivery is not online
        if ($this->crud->entry->mode_of_delivery === 'In Person') {
            // If there are existing courses, delete them
            if ($programme->courses()->count() > 0) {
                $programme->courses->each->delete();
            }
        }

        return $response;
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        $programme = Programme::findOrFail($id);

        // Detach tags
        $programme->tags()->detach();

        // Delete related course modules and certifications
        CourseModule::where('programme_id', $programme->id)->delete();
        CourseCertification::where('programme_id', $programme->id)->delete();

        // Delete related courses
       $programme->courses->each->delete();

        return $this->traitDestroy($id);
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

    protected function normalizeImagePath()
    {
        $request = $this->crud->getRequest();
        $imagePath = $request->input('image');

        if (empty($imagePath)) {
            return;
        }

        // If it already has https://, don't process further
        if (strpos($imagePath, 'https://') === 0 || strpos($imagePath, 'http://') === 0) {
            return;
        }

        // Strip "Google Cloud Storage/" or similar disk aliases
        if (strpos($imagePath, CLOUD_STORAGE_ALIAS . '/') === 0) {
            $imagePath = substr($imagePath, strlen(CLOUD_STORAGE_ALIAS . '/'));
        }

        // Build the full CDN URL
        $cdnUrl = rtrim(config('filesystems.cdn_url'), '/');
        $fullUrl = $cdnUrl . '/' . ltrim($imagePath, '/');

        $request->merge(['image' => $fullUrl]);
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
