<?php

namespace App;

use Illuminate\Http\Request;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\CrudPanel\Hooks\Facades\LifecycleHook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;


trait SearchableCRUD
{
    /**
     * Columns that will be searched for the 'q' attribute.
     * These should be actual database columns or accessors on your model.
     * Set this in your CrudController's setup() method.
     * e.g., $this->setSearchableColumns(['name', 'email', 'description']);
     *
     * @var array
     */
    protected $traitSearchableColumns = [];

    /**
     * Attributes to be returned in the JSON response for each matched model.
     * Default to ['id', 'title', 'name'].
     * Set this in your CrudController's setup() method if different.
     * e.g., $this->setSearchResultAttributes(['id', 'product_code', 'product_name']);
     *
     * @var array
     */
    protected $traitSearchResultAttributes = ['id', 'title', 'name'];

    /**
     * Set the columns to be searched when the /search endpoint is hit.
     *
     * @param array $columns An array of column names (strings).
     * @return self
     */
    public function setSearchableColumns(array $columns)
    {
        $this->traitSearchableColumns = $columns;
        return $this;
    }



    /**
     * Set the attributes to be returned in the JSON response for each matched model.
     *
     * @param array $attributes An array of attribute names (strings).
     * @return self
     */
    public function setSearchResultAttributes(array $attributes)
    {
        $this->traitSearchResultAttributes = $attributes;
        return $this;
    }

    /**
     * Add the custom '/search' route to the CRUD controller.
     * Call this from your CrudController's setup() method.
     *
     * @return void
     */

    protected function setupPerformSearchRoutes($segment, $routeName, $controller)
    {
        Route::match(['get', 'post'], $segment . '/performSearch', [
            'as' => $routeName . '.performSearch',
            'uses' => $controller . '@performSearch',
            'operation' => 'performSearch',
        ]);
    }

    protected function setupPerformSearchDefaults()
    {
        $this->crud->allowAccess('performSearch');

        LifecycleHook::hookInto('performSearch:before_setup', function () {
            $this->crud->loadDefaultOperationSettingsFromConfig();
        });
    }

    /**
     * Handles the search request for the /{entity}/search endpoint.
     * Searches the model based on 'q' attribute and returns id, title, name.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function performSearch(Request $request)
    {
        $query = $request->input('q');
        $model = CRUD::getModel(); // Get the model instance associated with the current CRUD controller

        if (empty($query)) {
            return response()->json([]); // Return empty if no search query
        }

        if (empty($this->traitSearchableColumns)) {
            // Log a warning or throw an exception if searchable columns are not set.
            // For production, ensure these are configured.
            // Log::warning('SearchableCrud Trait: $traitSearchableColumns not set for ' . get_class($this));
            return response()->json([]);
        }

        $results = $model->newQuery();

        foreach ($this->traitSearchableColumns as $index => $column) {
            if ($index === 0) {
                $results->where($column, 'LIKE', '%' . $query . '%');
            } else {
                $results->orWhere($column, 'LIKE', '%' . $query . '%');
            }
        }

        // Select only the requested attributes. Ensure these exist as columns or accessors.
        // If a requested attribute doesn't exist, it will be null in the response.
        $results = $results
            ->select($this->traitSearchResultAttributes)
            ->limit(10) // Limit results for performance in AJAX scenarios
            ->get();

        return response()->json($results);
    }
}
