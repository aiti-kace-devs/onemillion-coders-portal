<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CampaignRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Campaign;
use App\Models\Branch;
use App\Models\District;
use App\Models\Centre;
use App\Models\ProgrammeBatch;
use App\Models\MasterSession;
use App\Models\CourseSession;
use Request;

/**
 * Class CampaignCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CampaignCrudController extends CrudController
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
        CRUD::setModel(Campaign::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/campaign');
        CRUD::setEntityNameStrings('campaign', 'campaigns');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('title');
        CRUD::column('priority');
        CRUD::column('sent_at')->label('Sent At');
        CRUD::column('created_at');

        // Add a button to send the campaign
        CRUD::addButtonFromModelFunction('line', 'send_campaign', 'sendCampaignButton', 'beginning');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->crud->setOperation('create');

        return view('admin.campaign.create', [
            'branches' => Branch::all(),
            'districts' => District::all(),
            'centres' => Centre::all(),
            'programmeBatches' => ProgrammeBatch::all(),
            'masterSessions' => MasterSession::all(),
            'courseSessions' => CourseSession::all(),
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(CampaignRequest::class);

        CRUD::field('title')->type('text')->label('Campaign Title');
        CRUD::field('message')->type('textarea')->label('Message');
        CRUD::field('priority')->type('select_from_array')->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'])->default('normal');
        CRUD::field('type')->type('text')->default('campaign')->attributes(['readonly' => true]);

        // Add a custom HTML section for targeting
        CRUD::addField([
            'name' => 'targeting_section',
            'type' => 'custom_html',
            'label' => 'Targeting Criteria',
            'value' => view('admin.campaign.targeting_form', [
                'branches' => Branch::all(),
                'districts' => District::all(),
                'centres' => Centre::all(),
                'programmeBatches' => ProgrammeBatch::all(),
                'masterSessions' => MasterSession::all(),
                'courseSessions' => CourseSession::all(),
                'campaign' => $this->crud->getCurrentEntry() ?? null,
            ])->render(),
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::field('created_by')->type('hidden')->value(backpack_auth()->id());
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $campaign = Campaign::findOrFail($id);

        return view('admin.campaign.edit', [
            'campaign' => $campaign,
            'branches' => Branch::all(),
            'districts' => District::all(),
            'centres' => Centre::all(),
            'programmeBatches' => ProgrammeBatch::all(),
            'masterSessions' => MasterSession::all(),
            'courseSessions' => CourseSession::all(),
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
        
        // Prevent editing sent campaigns
        if ($this->crud->getModel()->sent_at !== null) {
            CRUD::denyAccess(['update', 'delete']);
        }
    }

    /**
     * Handle storing campaign with JSON array data
     */
    public function store()
    {
        // Validate the request
        $validated = request()->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1200',
            'priority' => 'required|string|in:low,normal,high',
            'type' => 'required|string',
            'target_type' => 'nullable|string|in:all,branch,district,centre,batch,session',
            'target_selection' => 'nullable|array',
            'created_by' => 'required|integer',
            'target_branches' => 'nullable|array',
            'target_districts' => 'nullable|array',
            'target_centres' => 'nullable|array',
            'target_courses' => 'nullable|array',
            'target_programme_batches' => 'nullable|array',
            'target_master_sessions' => 'nullable|array',
            'target_course_sessions' => 'nullable|array',
        ]);

        // Set default target_type if not provided
        if (!isset($validated['target_type'])) {
            $validated['target_type'] = 'all';
        }

        // Ensure JSON fields are arrays (not null)
        $jsonFields = ['target_branches', 'target_districts', 'target_centres', 'target_courses',
                       'target_programme_batches', 'target_master_sessions', 'target_course_sessions'];
        
        foreach ($jsonFields as $field) {
            if (!isset($validated[$field]) || empty($validated[$field])) {
                $validated[$field] = [];
            } elseif (is_string($validated[$field])) {
                $validated[$field] = [$validated[$field]];
            }
        }
        
        // Store the campaign
        Campaign::create($validated);
        
        \Alert::success('Campaign created successfully! Now you can send it from the list.')->flash();
        return redirect(backpack_url('campaign'));
    }

    /**
     * Handle updating campaign
     */
    public function update($id)
    {
        $campaign = Campaign::findOrFail($id);
        
        // Prevent updating sent campaigns
        if ($campaign->sent_at !== null) {
            \Alert::error('Sent campaigns cannot be modified.')->flash();
            return redirect()->back();
        }

        // Validate the request
        $validated = request()->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1200',
            'priority' => 'required|string|in:low,normal,high',
            'target_type' => 'nullable|string|in:all,branch,district,centre,batch,session',
            'target_selection' => 'nullable|array',
            'target_branches' => 'nullable|array',
            'target_districts' => 'nullable|array',
            'target_centres' => 'nullable|array',
            'target_courses' => 'nullable|array',
            'target_programme_batches' => 'nullable|array',
            'target_master_sessions' => 'nullable|array',
            'target_course_sessions' => 'nullable|array',
        ]);

        // Set default target_type if not provided
        if (!isset($validated['target_type'])) {
            $validated['target_type'] = 'all';
        }

        // Ensure JSON fields are arrays
        $jsonFields = ['target_branches', 'target_districts', 'target_centres', 'target_courses',
                       'target_programme_batches', 'target_master_sessions', 'target_course_sessions'];
        
        foreach ($jsonFields as $field) {
            if (!isset($validated[$field]) || empty($validated[$field])) {
                $validated[$field] = [];
            } elseif (is_string($validated[$field])) {
                $validated[$field] = [$validated[$field]];
            }
        }
        
        $campaign->update($validated);
        
        \Alert::success('Campaign updated successfully!')->flash();
        return redirect(backpack_url('campaign'));
    }

    public function sendCampaign($id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->sent_at) {
            \Alert::error('Campaign has already been sent.')->flash();
            return redirect()->back();
        }

        \App\Http\Controllers\CampaignController::sendCampaign($campaign);

        \Alert::success('Campaign sent successfully!')->flash();
        return redirect()->back();
    }








    public function getBranches()
    {
        $branches = Branch::select('id', 'name as title')->get();
        return response()->json($branches);
    }

    public function getDistricts(Request $request)
    {
        $branchIds = $request->input('branch_ids', []);
        
        $districts = District::whereIn('branch_id', $branchIds)
            ->select('id', 'name as title')
            ->get();
        
        return response()->json($districts);
    }

    public function getCentres(Request $request)
    {
        $districtIds = $request->input('district_ids', []);
        
        $centres = Centre::whereIn('district_id', $districtIds)
            ->select('id', 'name as title')
            ->get();
        
        return response()->json($centres);
    }

    public function getCourses(Request $request)
    {
        $centreIds = $request->input('centre_ids', []);
        
        $courses = Course::whereIn('centre_id', $centreIds)
            ->select('id', 'name as title')
            ->get();
        
        return response()->json($courses);
    }

    public function getSessions(Request $request)
    {
        $courseIds = $request->input('course_ids', []);
        
        $masterSessions = MasterSession::whereIn('course_id', $courseIds)
            ->select('id', \DB::raw("CONCAT('Master - ', name) as title"))
            ->get();
        
        $courseSessions = CourseSession::whereIn('course_id', $courseIds)
            ->select('id', \DB::raw("CONCAT('Course - ', name) as title"))
            ->get();
        
        $sessions = $masterSessions->merge($courseSessions);
        
        return response()->json($sessions);
    }



}
