<?php

namespace App\Http\Controllers;

use App\Models\ListModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Query\Builder; // Import the Query Builder
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $views = DB::select('SHOW FULL TABLES WHERE Table_type = "VIEW"');
        return Inertia::render('List/Index')->with(compact('views'));
        // return view('lists.index', compact('views'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        return Inertia::render('List/Create', compact('tables'));
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request): RedirectResponse
    {
        $viewExists = DB::table('information_schema.views') // Corrected table name.
            ->where('TABLE_NAME', $request->input('view_name'))
            ->exists();

        if ($viewExists) {
            redirect()->back()->withErrors(['view_name' => ['The view name is already taken.']])->withInput();
            // return response()->json(['errors' => ], 422);
        }

        $tableNames = implode(',', DB::connection()->getDoctrineSchemaManager()->listTableNames());
        $validator = Validator::make($request->all(), [
            'view_name' => 'required|string', // Ensure unique view name
            'table_name' => 'required|string|in:' . $tableNames,
            'columns' => 'nullable|array',
            'columns.*' => 'string',
            'where_conditions' => 'nullable|array',
            'where_conditions.*.column' => 'required|string',
            'where_conditions.*.operator' => 'required|string|in:==,!=,<,>,<=,>=,LIKE,NOT LIKE,IN,NOT IN',
            'where_conditions.*.value' => 'required',
            'order_by_column' => 'nullable|string',
            'order_by_direction' => 'nullable|string|in:asc,desc',
            'limit' => 'nullable|integer|min:1',
            'joins' => 'nullable|array',
            'joins.*.table' => 'required|string|in:' . $tableNames,
            'joins.*.first_column' => 'required|string',
            'joins.*.operator' => 'required|string|in:=,>,<,>=,<=',
            'joins.*.second_column' => 'required|string',
            'joins.*.type' => 'nullable|string|in:inner,left,right,cross',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $viewName = $request->input('view_name');
        $tableName = $request->input('table_name');
        $columns = $request->input('columns', ['*']);
        $whereConditions = $request->input('where_conditions', []);
        $orderByColumn = $request->input('order_by_column');
        $orderByDirection = $request->input('order_by_direction', 'asc');
        $limit = $request->input('limit');
        $joins = $request->input('joins', []);

        // Build the SELECT query string
        $selectQuery = DB::table($tableName)
            ->select($columns);

        // Handle joins
        foreach ($joins as $join) {
            $joinType = $join['type'] ?? 'inner';
            $operator = $join['operator'] ?? '=';
            $selectQuery->join($join['table'], $join['first_column'], $operator, $join['second_column'], $joinType);
        }
        // Handle where conditions
        foreach ($whereConditions as $condition) {
            $selectQuery->where(
                $condition['column'],
                $condition['operator'],
                $condition['value']
            );
        }

        // Handle order by
        if ($orderByColumn) {
            $selectQuery->orderBy($orderByColumn, $orderByDirection);
        }

        // Handle limit
        if ($limit) {
            $selectQuery->limit($limit);
        }
        // Get the SQL query string
        $sql = $selectQuery->toSql();

        dd($sql);

        // Create the view
        DB::statement("CREATE VIEW {$viewName} AS {$sql}");

        return redirect()->route('admin.lists.index')->with('success', 'List view created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $viewName
     * @return \Illuminate\View\View
     */
    public function show(string $viewName): View
    {
        // Check if the view exists
        $viewExists = DB::select("SHOW TABLES LIKE '{$viewName}'");
        if (!$viewExists) {
            abort(404, 'View not found.');
        }

        // Fetch data from the view.
        $results = DB::table($viewName)->get();

        return view('lists.show', compact('results', 'viewName'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //This method is not needed for the ListModel
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //This method is not needed for the ListModel
        abort(404);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $viewName
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $viewName): RedirectResponse
    {
        // Check if the view exists before attempting to drop it.
        $viewExists = DB::select("SHOW TABLES LIKE '{$viewName}'");
        if (!$viewExists) {
            return redirect()->route('lists.index')->with('error', 'View does not exist.'); //Or throw an exception
        }
        DB::statement("DROP VIEW IF EXISTS {$viewName}");

        return redirect()->route('lists.index')->with('success', 'List view deleted successfully!');
    }


    /**
     * Fetches the columns for a given table.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTableColumns(Request $request)
    {
        // Validate the request to ensure the table_name is provided.
        $request->validate([
            'table_name' => 'required|string',
        ]);

        $tableName = $request->input('table_name');

        try {
            // Use the database connection to get the column names.
            // This method uses the database schema to get the column information.
            $columns = DB::connection()->getSchemaBuilder()->getColumnListing($tableName);

            if (empty($columns)) {
                // return response()->json(['error' => 'Table not found or has no columns.'], 404);
                return Inertia::json(['error' => 'Table not found or has no columns.']); // Add this line
            }

            // Return the column names as a JSON response.
            // return Inertia::json(['availableColumns' => $columns]); // Add this line

            return response()->json(['availableColumns' => $columns]);
        } catch (\Exception $e) {
            // Handle any errors that occur during the process.
            // Log the error message for debugging.
            \Log::error('Error fetching columns for table ' . $tableName . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch columns: ' . $e->getMessage()], 500);
        }
    }
}
