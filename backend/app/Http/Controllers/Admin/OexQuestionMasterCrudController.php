<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\OexQuestionMasterRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Helpers\WidgetHelper;
use App\Helpers\FilterHelper;
use App\Models\OexCategory;
use App\Helpers\CourseFieldHelpers;
use App\Helpers\UserFieldHelpers;
use App\Models\OexQuestionMaster;

/**
 * Class OexQuestionMasterCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class OexQuestionMasterCrudController extends CrudController
{
    use UserFieldHelpers;
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
        CRUD::setModel(\App\Models\OexQuestionMaster::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/question-master');
        CRUD::setEntityNameStrings('question master', 'question masters');

        if (request()->has('exam_id')) {
            $this->crud->addClause('where', 'exam_id', request()->get('exam_id'));
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $examId = request()->get('exam_id');
        WidgetHelper::manageQuestionStatisticsWidget($examId);

        if ($examId) {
            $exam = \App\Models\OexExamMaster::find($examId);
            $this->crud->setHeading('Questions for Exam: ' . ($exam->title ?? ''));
            $this->crud->removeButton('create');

            $this->crud->addButtonFromView('top', 'add_question_with_exam', 'view', 'beginning');
            $this->crud->addButtonFromView('top', 'back_to_exam', 'view', 'end');
        }

        if ($examId) {
            $this->crud->addClause('where', 'exam_id', $examId);
        }

        if ($examId) {
            CRUD::addField([
                'name' => 'exam_id',
                'type' => 'hidden',
                'value' => $examId,
            ]);
        }
        CRUD::addColumn([
            'name' => 'questions',
            'label' => 'questions',
            'type' => 'textarea',
            'escaped' => false,
        ]);
        CRUD::addColumn([
            'name' => 'programmes',
            'label' => 'Programmes',
            'entity' => 'programmes',
            'attribute' => 'title',
            'model' => "App\Models\Programme",
        ]);
        CRUD::column('ans')->type('textarea');
        FilterHelper::addBooleanColumn('status', 'status');

        if ($examId) {
            $this->crud->addClause('where', 'exam_id', $examId);
        }

        FilterHelper::addNullableColumnFilter('filter_ans', 'ans', 'Filter Answers');
        FilterHelper::addBooleanFilter('status', 'Status');
        FilterHelper::addDateRangeFilter('created_at', 'Created At');
        CRUD::enableExportButtons();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }
    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        $examId = request()->get('exam_id');

        CRUD::setValidation(OexQuestionMasterRequest::class);

        CRUD::addField([
            'name' => 'exam_id',
            'type' => 'hidden',
            'value' => $examId ?? null
        ]);

        CRUD::addField([
            'name' => 'exam_set_id',
            'type' => 'hidden',
            'value' => 1,
        ]);

        CRUD::addField([
            'name' => 'programmes',
            'label' => 'Select Programmes',
            'type' => 'select2_multiple',
            'entity' => 'programmes',
            'attribute' => 'title',
            'model' => "App\Models\Programme",
            'pivot' => true,
            'wrapper' => ['class' => 'form-group col-12'],
            'allows_null' => true,
        ]);

        CRUD::addField([
            'name' => 'questions',
            'label' => 'Enter Question',
            'type' => 'textarea',
        ]);

        CRUD::addFields([
            [
                'name' => 'options1',
                'label' => 'Enter Option 1',
                'type' => 'text',
                'wrapper' => ['class' => 'form-group col-6'],
                'default' => ''
            ],
            [
                'name' => 'options2',
                'label' => 'Enter Option 2',
                'type' => 'text',
                'wrapper' => ['class' => 'form-group col-6'],
                'default' => ''
            ],
            [
                'name' => 'options3',
                'label' => 'Enter Option 3',
                'type' => 'text',
                'wrapper' => ['class' => 'form-group col-6'],
                'default' => ''
            ],
            [
                'name' => 'options4',
                'label' => 'Enter Option 4',
                'type' => 'text',
                'wrapper' => ['class' => 'form-group col-6'],
                'default' => ''
            ],
        ]);


        CRUD::addField([
            'name' => 'ans',
            'label' => 'Select correct option',
            'type' => 'select_from_array',
            'options' => [
                'Option1' => 'Option 1',
                'Option2' => 'Option 2',
                'Option3' => 'Option 3',
                'Option4' => 'Option 4',

            ],
            'wrapper' => ['class' => 'form-group col-6'],
            'allows_null' => false,
        ]);

        $this->addIsActiveField([true => 'True', false => 'False'], 'Status', 'status');
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

        $entry = $this->crud->getCurrentEntry();

        $this->crud->modifyField('questions', [
            'default' => $entry->questions ?? ''
        ]);

        $this->crud->modifyField('options1', [
            'default' => $entry->options['option1'] ?? ''
        ]);

        $this->crud->modifyField('options2', [
            'default' => $entry->options['option2'] ?? ''
        ]);

        $this->crud->modifyField('options3', [
            'default' => $entry->options['option3'] ?? ''
        ]);

        $this->crud->modifyField('options4', [
            'default' => $entry->options['option4'] ?? ''
        ]);

        $selectedAnswer = $this->determineSelectedAnswer($entry);
        $this->crud->modifyField('ans', [
            'default' => $selectedAnswer
        ]);

        $this->crud->modifyField('status', [
            'default' => $entry->status ?? false
        ]);
    }



    private function determineSelectedAnswer($entry)
    {
        if (!empty($entry->options)) {
            foreach ($entry->options as $key => $value) {
                if (trim($value) === trim($entry->ans)) {
                    return ucfirst($key);
                }
            }
        }
        return null;
    }





    private function transformRequestData($request)
    {
        $options = [
            'option1' => $request->input('options1', ''),
            'option2' => $request->input('options2', ''),
            'option3' => $request->input('options3', ''),
            'option4' => $request->input('options4', ''),
        ];

        $options = array_filter($options, function ($value) {
            return $value !== null && $value !== '';
        });

        $ansMap = [
            'Option1' => $options['option1'] ?? null,
            'Option2' => $options['option2'] ?? null,
            'Option3' => $options['option3'] ?? null,
            'Option4' => $options['option4'] ?? null,
        ];


        return [
            'options' => $options,
            'actualAns' => $ansMap[$request->input('ans')] ?? null,
        ];
    }

    public function store()
    {
        $request = $this->crud->getRequest();
        $this->crud->validateRequest();

        $transformed = $this->transformRequestData($request);

        $exam_id = $request->input('exam_id');
        if (!$exam_id) {
            \Alert::error('Exam ID is required')->flash();
            return back()->withInput();
        }

        $question = OexQuestionMaster::create([
            'exam_id' => $exam_id,
            'exam_set_id' => 1,
            'questions' => $request->input('questions', ''),
            'options' => $transformed['options'],
            'ans' => $transformed['actualAns'],
            'status' => $request->input('status', false),
        ]);

        $question->programmes()->sync($request->input('programmes', []));

        \Alert::success(trans('backpack::crud.insert_success'))->flash();
        return redirect(backpack_url('question-master') . '?exam_id=' . $exam_id);
    }

    public function update()
    {
        $request = $this->crud->getRequest();
        $this->crud->validateRequest();

        $transformed = $this->transformRequestData($request);
        $entry = $this->crud->getCurrentEntry();

        $exam_id = $request->input('exam_id', $entry->exam_id);

        $entry->update([
            'exam_id' => $exam_id,
            'exam_set_id' => 1,
            'questions' => $request->input('questions', ''),
            'options' => $transformed['options'],
            'ans' => $transformed['actualAns'],
            'status' => $request->input('status', false),
        ]);

        $entry->programmes()->sync($request->input('programmes', []));

        \Alert::success(trans('backpack::crud.update_success'))->flash();
        return redirect(backpack_url('question-master') . '?exam_id=' . $exam_id);
    }


    public function addQuestion($exam_id)
    {
        return redirect(backpack_url('question-master') . '?exam_id=' . $exam_id);
    }
}
