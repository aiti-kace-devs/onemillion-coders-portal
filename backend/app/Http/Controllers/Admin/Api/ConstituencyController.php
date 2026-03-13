<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use Illuminate\Http\Request;

class ConstituencyController extends Controller
{
    public function filterByBranch(Request $request)
    {
        $branchId = $request->input('branch_id')
            ?? $request->input('form.branch_id')
            ?? optional(collect($request->input('form'))
                ->firstWhere('name', 'branch_id'))['value']
            ?? null;

        // Support preloading selected value when editing.
        if (!$branchId && $request->has('q') === false) {
            $constituencyId = $request->input('value') ?? $request->input('keys', []);

            if (!is_array($constituencyId)) {
                $constituencyId = [$constituencyId];
            }

            if (count($constituencyId) === 1) {
                $constituency = Constituency::find($constituencyId[0]);
                if ($constituency) {
                    return response()->json([[
                        'id' => $constituency->id,
                        'title' => $constituency->title,
                    ]]);
                }
            }

            return response()->json([]);
        }

        if (!$branchId) {
            return response()->json([]);
        }

        $term = $request->input('term', $request->input('q', ''));

        $constituencies = Constituency::query()
            ->where('branch_id', $branchId)
            ->when($term, fn ($query) => $query->where('title', 'like', "%{$term}%"))
            ->orderBy('title')
            ->get()
            ->map(fn ($constituency) => [
                'id' => $constituency->id,
                'title' => $constituency->title,
            ]);

        return response()->json($constituencies);
    }
}
