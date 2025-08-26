<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\User;

trait GetsFilteredQuery
{
    protected function getFilteredQuery(Request $request): Builder
    {
        $customView = $request->input('custom_view');

        if ($customView === 'setupStudentsWithExamResultsView' || $customView === 'students-with-exam-results') {
            return User::whereHas('examResults');
        }

        // This will trigger applyQueryClauses() which runs the filters.
        // We don't use the result, we just want the side-effect of the query being built.
        $this->crud->count();

        // The query builder is now filtered.
        return $this->crud->query;
    }
}
