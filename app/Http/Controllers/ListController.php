<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Query\Builder; // Import the Query Builder
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

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

        // For PostgreSQL alternative:
        // $views = DB::select("SELECT viewname as Name FROM pg_views WHERE schemaname NOT IN ('pg_catalog', 'information_schema')");

        // Format the data properly
        $formattedViews = array_map(function ($view) {
            return [
                'Name' => $view->{'Tables_in_' . env('DB_DATABASE')} ?? ($view->Name ?? $view->viewname),
            ];
        }, $views);

        return Inertia::render('List/Index', [
            'views' => $formattedViews,
        ]);
    }

    public function fetch()
    {
        $tables = $this->getTablesAndViews()['views'];
        $tables = collect($tables)->map(function ($t) {
            return [
                'name' => $t,
                'count' => DB::table($t)->count()
            ];
        });


        return DataTables::of($tables)
            ->addIndexColumn()
            // ->editColumn('duration', function ($row) {
            //     return $row->course_time;
            // })
            ->addColumn('action', function ($row) {
                $linkClass = 'inline-flex items-center w-full px-4 py-2 text-sm text-gray-700 disabled:cursor-not-allowed disabled:opacity-25 hover:text-gray-50 hover:bg-gray-100';

                $action =
                    '<div class="relative inline-block text-left">
                        <div class="flex justify-end">
                          <button type="button" class="dropdown-toggle py-2 rounded-md">
                          <span class="material-symbols-outlined dropdown-span" dropdown-log="' . $row['name'] . '">
                            more_vert
                          </span>
                          </button>
                        </div>

                        <div id="dropdown-menu-' . $row['name'] . '" class="hidden dropdown-menu fixed right-0 z-50 mt-2 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                            <button type="button" data-id="' . $row['name'] . '" class="sms ' . $linkClass . '">
                                Send SMS
                            </button>
                            <button type="button" data-id="' . $row['name'] . '" class="email ' . $linkClass . '">
                                Send Email
                            </button>
                            <button type="button" data-id="' . $row['name'] . '" class="view ' . $linkClass . '">
                                View List
                            </button>
                        <button type="button" data-id="' . $row['name'] . '" class="delete ' . $linkClass . '">
                             Delete
                        </button>
                        </div>
                      </div>
                      ';

                return $action;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $tables = $this->getTablesAndViews();
        return Inertia::render('List/Create', compact('tables'));
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(Request $request)
    {
        $tableNames = implode(',', $this->getTablesAndViews()['all']);

        $validator = Validator::make($request->all(), [
            'view_name' => 'nullable|required_if:is_test,false|string',
            'table_name' => 'required|string|in:' . $tableNames,
            'columns' => 'nullable|array',
            'columns.*.name' => 'required|string', // Changed to handle column objects
            'columns.*.alias' => 'nullable|string', // New field for aliases
            'where_conditions' => 'nullable|array',
            'where_conditions.*.column' => 'required_unless:where_conditions.*.operator,RAW|nullable|string|max:255',
            'where_conditions.*.operator' => 'required|string|in:=,!=,<,>,<=,>=,LIKE,NOT LIKE,IN,NOT IN,IS NULL,IS NOT NULL,BETWEEN,RAW',
            'where_conditions.*.value' => 'nullable|string|max:255',
            'order_by_column' => 'nullable|string',
            'order_by_direction' => 'nullable|string|in:asc,desc',
            'limit' => 'nullable|integer|min:1',
            'joins' => 'nullable|array',
            'joins.*.table' => 'required|string|in:' . $tableNames,
            'joins.*.first_column' => 'required|string',
            'joins.*.operator' => 'required|string|in:=,>,<,>=,<=',
            'joins.*.second_column' => 'required|string',
            'joins.*.type' => 'nullable|string|in:inner,left,right,cross',
            'is_test' => 'sometimes|boolean',
        ], [
            'where_conditions.*.column.required_unless' => 'If the condition operator is not RAW, then column is required'
        ], [
            'where_conditions.*.column' => 'where condition column',
            'where_conditions.*.operator' => 'where condition operator',
            'where_conditions.*.value' => 'where condition value'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $viewName = $request->input('view_name');
        $tableName = $request->input('table_name');
        $columns = count($request->input('columns')) > 0 ? $request->input('columns') :  [['name' => '*']];
        $whereConditions = $request->input('where_conditions', []);
        $orderByColumn = $request->input('order_by_column');
        $orderByDirection = $request->input('order_by_direction', 'asc');
        $limit = $request->input('limit');
        $joins = $request->input('joins', []);

        // Build the SELECT query string with column aliases
        $selectColumns = array_map(function ($column) {
            return isset($column['alias']) && $column['alias'] !== '' ? "{$column['name']} as {$column['alias']}" : $column['name'];
        }, $columns);

        $selectQuery = DB::table($tableName)->selectRaw(implode(', ', $selectColumns));

        // Handle joins
        foreach ($joins as $join) {
            $joinType = $join['type'] ?? 'inner';
            $operator = $join['operator'] ?? '=';
            $selectQuery->join($join['table'], $join['first_column'], $operator, $join['second_column'], $joinType);
        }

        // Handle where conditions
        $this->applyWhereConditions($selectQuery, $whereConditions);

        // Handle order by
        if ($orderByColumn) {
            $selectQuery->orderBy($orderByColumn, $orderByDirection);
        }

        // Handle limit
        if ($limit) {
            $selectQuery->limit($limit);
        }

        // dd($this->validateSelectOnly($selectQuery->toSql()));

        if (!$this->validateSelectOnly($selectQuery->toSql())) {
            return redirect()->back()->withErrors([
                'view_name' => 'Only SELECT queries are allowed.',
                'table_name' => 'Only SELECT queries are allowed.',
            ])->withInput();
        }

        if ($request->is_test) {
            // For test queries, just return metadata
            $start = microtime(true);
            try {
                //code...
                $count = $selectQuery->count();
            } catch (\Illuminate\Database\QueryException $th) {
                //throw $th;
                $message = $th->getMessage() ?? $th->sql;

                return redirect()->back()->withErrors([
                    'view_name' => "Error running query: ' . $message",
                    'table_name' => "Error running query: ' . $message",
                ]);
            }
            $time = round((microtime(true) - $start) * 1000, 2);

            $sql = Str::replaceArray('?', $selectQuery->getBindings(), Str::replace('?', "'?'", $selectQuery->toSql()));

            return Inertia::render('List/Create', [
                'tables' => $this->getTablesAndViews(),
                'testResult' => [
                    'count' => $count,
                    'time' => $time,
                    'sql' => $this->formatSql($sql),
                    'bindings' => $selectQuery->getBindings(),
                ]
            ]);
        }

        // dump($selectQuery->toSql());
        // Create the view
        try {
            $name = Str::lower(Str::snake($viewName));
            DB::statement("CREATE OR REPLACE VIEW {$name} AS {$selectQuery->toSql()}");
            return redirect()->route('admin.lists.index')->with('success', 'List view created successfully!');
            //code...
        } catch (\Illuminate\Database\QueryException $th) {
            $message = $th->getMessage() ?? "";

            return redirect()->back()->withErrors([
                'view_name' => "Error running query: $message",
                'table_name' => "Error running query: $message",
            ]);
        }
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

    public function viewData(Request $request)
    {
        $request->validate([
            'list' => 'required|string', // Adjust table name
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $listId = $request->input('list');
        $perPage = $request->input('per_page', 15); // Default to 15 items per page
        $currentPage = $request->input('page', 1);

        // Example query - adjust based on your needs
        $paginatedData = DB::table($listId)->paginate($perPage, ['*'], 'page', $currentPage);


        // Get dynamic columns if needed (adjust based on your implementation)
        $columns = $this->getColumnsByName($listId);

        return response()->json([
            'data' => $paginatedData->items(),
            'columns' => $columns,
            'current_page' => $paginatedData->currentPage(),
            'last_page' => $paginatedData->lastPage(),
            'total' => $paginatedData->total(),
        ]);
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
    public function destroy(string $viewName)
    {
        try {
            DB::statement("DROP VIEW IF EXISTS `{$viewName}`");
            // return redirect()->route('admin.lists.index')->with('success', 'View deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting view: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete view: ');
        }
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
            $columns = $this->getColumnsByName($tableName);

            return response()->json(['availableColumns' => $columns]);
        } catch (\Exception $e) {
            // Handle any errors that occur during the process.
            // Log the error message for debugging.
            \Log::error('Error fetching columns for table ' . $tableName . ': ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch columns: ' . $e->getMessage()], 500);
        }
    }

    private function getColumnsByName(string $tableName)
    {
        $columns = DB::connection()->getSchemaBuilder()->getColumns($tableName);

        if (empty($columns)) {
            // return response()->json(['error' => 'Table not found or has no columns.'], 404);
            return Inertia::json(['error' => 'Table not found or has no columns.']); // Add this line
        }

        // Return the column names as a JSON response.
        // return Inertia::json(['availableColumns' => $columns]); // Add this line
        $columns = collect($columns)->map(function ($col) use ($tableName) {
            return ['name' => $col['name'], 'type' => $this->convertDatabaseTypes($col['type_name'])];
        })->all();

        return $columns;
    }

    protected function applyWhereConditions($query, array $whereConditions)
    {
        foreach ($whereConditions as $condition) {
            if (empty($condition['column'])) {
                continue; // Skip invalid conditions
            }

            $column = $this->sanitizeColumnName($condition['column']);
            $operator = strtoupper($condition['operator'] ?? '=');
            $value = $condition['value'] ?? null;

            if ($condition['operator'] === 'RAW') {
                // if (!auth()->user()->can('use-raw-sql')) {
                //     throw new \Exception('Raw SQL not allowed');
                // }

                // Basic check for dangerous keywords
                $disallowed = ['delete', 'update', 'insert', 'drop', 'truncate', ';'];
                foreach ($disallowed as $keyword) {
                    if (stripos($condition['value'], $keyword) !== false) {
                        throw new \Exception('Potentially dangerous raw SQL detected');
                    }
                }
            }


            switch ($operator) {
                case 'IN':
                case 'NOT IN':
                    $values = is_array($value) ? $value : array_map('trim', explode(',', $value));
                    if ($operator === 'IN') {
                        $query->whereIn($column, $values);
                    } else {
                        $query->whereNotIn($column, $values);
                    }
                    break;

                case 'IS NULL':
                case 'IS NOT NULL':
                    if ($operator === 'IS NULL') {
                        $query->whereNull($column);
                    } else {
                        $query->whereNotNull($column);
                    }
                    break;

                case 'BETWEEN':
                    $values = is_array($value) ? $value : array_map('trim', explode(',', $value, 2));
                    if (count($values) === 2) {
                        $query->whereBetween($column, $values);
                    }
                    break;

                case 'LIKE':
                case 'NOT LIKE':
                    $query->where($column, $operator, '%' . $value . '%');
                    break;

                case 'RAW':
                    $query->whereRaw($value);
                    break;

                default:
                    // Standard operators: =, !=, <, >, <=, >=
                    $query->where($column, $operator, $value);
                    break;
            }
        }
    }

    protected function sanitizeColumnName(string $column): string
    {
        // Implement your column name sanitization logic here
        return preg_replace('/[^a-zA-Z0-9_\.]/', '', $column);
    }

    private function getTablesAndViews()
    {
        $dbtables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        $dbviews = collect(DB::connection()->getDoctrineSchemaManager()->listViews())->keys()->toArray();
        $tables = array_merge($dbtables, $dbviews);
        return ["tables" => $dbtables, "views" => $dbviews, "all" => $tables];
    }

    protected function formatSql($sql)
    {
        $keywords = [
            'select',
            'from',
            'where',
            'join',
            'inner',
            'left',
            'right',
            'outer',
            'on',
            'group by',
            'having',
            'order by',
            'limit'
        ];

        foreach ($keywords as $keyword) {
            $sql = preg_replace("/\b$keyword\b/i", "\n" . strtoupper($keyword), $sql);
        }

        return trim($sql);
    }

    /**
     * Validate that the SQL query is a SELECT statement
     *
     * @param string $sql
     * @return bool
     * @throws \Exception If query is not a SELECT
     */
    private function validateSelectOnly(string $sql): bool
    {
        $isSelectOnly = true;
        // Remove comments to prevent bypass attempts
        $sanitized = preg_replace('/\/\*.*?\*\/|--.*?$/s', '', $sql);

        // Trim whitespace and semicolons
        $sanitized = trim($sanitized, " \t\n\r\0\x0B;");

        // Check if query starts with SELECT (case insensitive)
        if (!preg_match('/^SELECT\s+/i', $sanitized)) {
            return  false;
        }

        // Additional checks for forbidden clauses
        $forbiddenPatterns = [
            '/\bINSERT\b/i',
            '/\bUPDATE\b/i',
            '/\bDELETE\b/i',
            '/\bTRUNCATE\b/i',
            '/\bDROP\b/i',
            '/\bCREATE\b/i',
            '/\bALTER\b/i',
            '/\bEXEC(UTE)?\b/i',
            '/\bSHUTDOWN\b/i',
            '/\bGRANT\b/i',
            '/\bREVOKE\b/i',
            '/;\s*$/'
        ];

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $sanitized)) {
                $isSelectOnly =  false;
                break;
            }
        }

        return $isSelectOnly;
    }

    private function convertDatabaseTypes($dbType)
    {
        switch ($dbType) {
            case 'int':
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'bigint':
                return 'integer';
            case 'float':
            case 'double':
            case 'decimal':
                return 'float';
            case 'char':
            case 'varchar':
            case 'text':
            case 'longtext':
            case 'mediumtext':
            case 'tinytext':
            case 'enum':
                return 'string';
            case 'date':
            case 'datetime':
            case 'timestamp':
                return 'datetime';
        }
    }
}
