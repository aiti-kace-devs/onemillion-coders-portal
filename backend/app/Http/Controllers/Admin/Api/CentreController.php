<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Centre;
use Illuminate\Http\Request;

class CentreController extends Controller
{



   public function filterByBranch(Request $request)
{
    // Attempt to get the branch_id from multiple places (standard, form input, or array input)
    $branchId = $request->input('branch_id') 
        ?? $request->input('form.branch_id')
        ?? optional(collect($request->input('form'))
            ->firstWhere('name', 'branch_id'))['value']
        ?? null;

    // Handle preloading the selected centre if branch_id is not available
    if (!$branchId && $request->has('q') === false) {
        // Support both 'value' and 'keys[]' formats
        $centreId = $request->input('value') ?? $request->input('keys', []);
        
        if (!is_array($centreId)) {
            $centreId = [$centreId];
        }

        if (count($centreId) === 1) {
            $centre = Centre::find($centreId[0]);
            if ($centre) {
                return response()->json([[
                    'id' => $centre->id,
                    'title' => $centre->title
                ]]);
            }
        }

        return response()->json([]);
    }

    if (!$branchId) {
        return response()->json([]);
    }

    $term = $request->input('term', '');

    $centres = Centre::where('branch_id', $branchId)
        ->when($term, fn($q) => $q->where('title', 'like', "%{$term}%"))
        ->get()
        ->map(fn($centre) => [
            'id' => $centre->id,
            'title' => $centre->title
        ]);

    return response()->json($centres);
}



//    public function filterByBranch(Request $request)
// {
//     $branchId = $request->input('branch_id') 
//               ?? $request->input('form.branch_id')
//               ?? optional(collect($request->input('form'))
//                   ->firstWhere('name', 'branch_id'))['value']
//               ?? null;

//     if (!$branchId) {
//         return [];
//     }

//     $term = $request->input('term', '');

//     return Centre::where('branch_id', $branchId)
//         ->when($term, fn($q) => $q->where('title', 'like', "%{$term}%"))
//         ->get()
//         ->map(fn($centre) => [
//             'id' => $centre->id,
//             'title' => $centre->title
//         ]);
// }




}
