<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
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
            $districtId = $request->input('value') ?? $request->input('keys', []);

            if (!is_array($districtId)) {
                $districtId = [$districtId];
            }

            if (count($districtId) === 1) {
                $district = District::find($districtId[0]);
                if ($district) {
                    return response()->json([[
                        'id' => $district->id,
                        'title' => $district->title,
                    ]]);
                }
            }

            return response()->json([]);
        }

        if (!$branchId) {
            return response()->json([]);
        }

        $term = $request->input('term', $request->input('q', ''));

        $districts = District::query()
            ->where('branch_id', $branchId)
            ->when($term, fn ($query) => $query->where('title', 'like', "%{$term}%"))
            ->orderBy('title')
            ->get()
            ->map(fn ($district) => [
                'id' => $district->id,
                'title' => $district->title,
            ]);

        return response()->json($districts);
    }
}
