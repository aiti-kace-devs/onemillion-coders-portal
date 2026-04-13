<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProgrammeBatchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\ProgrammeBatch;
use App\Models\Batch;
use App\Models\Programme;
use App\Models\Centre;
use App\Services\ProgrammeBatchGenerator;
use Illuminate\Http\Request;

/**
 * Class ProgrammeBatchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProgrammeBatchCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup()
    {
        CRUD::setModel(ProgrammeBatch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/programme-batch');
        CRUD::setEntityNameStrings('Programme Batch', 'Programme Batches');
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        CRUD::addFilter([
            'name' => 'admission_batch_id',
            'type' => 'select2',
            'label' => 'Admission Batch',
            'placeholder' => 'Select an admission batch',
        ], function () {
            return Batch::pluck('title', 'id')->toArray();
        }, function ($value) {
            if ($value) {
                $this->crud->addClause('where', 'admission_batch_id', $value);
            }
        });

        CRUD::addFilter([
            'name' => 'programme_id',
            'type' => 'select2',
            'label' => 'Programme',
            'placeholder' => 'Select a programme',
        ], function () {
            return Programme::pluck('title', 'id')->toArray();
        }, function ($value) {
            if ($value) {
                $this->crud->addClause('where', 'programme_id', $value);
            }
        });

        CRUD::addFilter([
            'name' => 'centre_id',
            'type' => 'select2',
            'label' => 'Centre',
            'placeholder' => 'Select a centre',
        ], function () {
            return Centre::pluck('title', 'id')->toArray();
        }, function ($value) {
            if ($value) {
                $this->crud->addClause('where', 'centre_id', $value);
            }
        });

        CRUD::addColumn([
            'name' => 'admissionBatch',
            'label' => 'Admission Batch',
            'type' => 'select',
            'entity' => 'admissionBatch',
            'attribute' => 'title',
            'model' => Batch::class,
        ]);

        CRUD::addColumn([
            'name' => 'programme',
            'label' => 'Programme',
            'type' => 'select',
            'entity' => 'programme',
            'attribute' => 'title',
            'model' => Programme::class,
        ]);

        CRUD::addColumn([
            'name' => 'centre',
            'label' => 'Centre',
            'type' => 'select',
            'entity' => 'centre',
            'attribute' => 'title',
            'model' => Centre::class,
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
            'name' => 'max_enrolments',
            'label' => 'Max Enrolments',
            'type' => 'number',
        ]);

        CRUD::addColumn([
            'name' => 'available_slots',
            'label' => 'Available Slots',
            'type' => 'number',
        ]);

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'boolean',
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ProgrammeBatchRequest::class);

        CRUD::addField([
            'name' => 'admission_batch_id',
            'label' => 'Admission Batch',
            'type' => 'select2',
            'entity' => 'admissionBatch',
            'attribute' => 'title',
            'model' => Batch::class,
            'placeholder' => 'Select an admission batch',
        ]);

        CRUD::addField([
            'name' => 'programme_id',
            'label' => 'Programme',
            'type' => 'select2',
            'entity' => 'programme',
            'attribute' => 'title',
            'model' => Programme::class,
            'placeholder' => 'Select a programme',
        ]);

        CRUD::addField([
            'name' => 'centre_id',
            'label' => 'Centre',
            'type' => 'select2',
            'entity' => 'centre',
            'attribute' => 'title',
            'model' => Centre::class,
            'placeholder' => 'Select a centre',
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

        CRUD::addField([
            'name' => 'max_enrolments',
            'label' => 'Max Enrolments',
            'type' => 'number',
        ]);

        CRUD::addField([
            'name' => 'available_slots',
            'label' => 'Available Slots',
            'type' => 'number',
        ]);

        CRUD::addField([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'checkbox',
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Regenerate programme batches for an admission batch.
     */
    public function regenerate(Request $request, int $batchId)
    {
        $batch = Batch::findOrFail($batchId);

        $generator = app(ProgrammeBatchGenerator::class);
        $generated = $generator->generate($batch);

        return redirect()->back()
            ->with('success', "Regenerated {$generated->count()} programme batches for '{$batch->title}'.");
    }
}
