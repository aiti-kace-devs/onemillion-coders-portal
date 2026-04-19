<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\District;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\MasterSession;
use Illuminate\Http\Request;

class CampaignTargetingController extends Controller
{
    /**
     * Get districts filtered by branch IDs
     */
    public function getDistrictsByBranches(Request $request)
    {
        $branchIds = $request->input('branch_ids', []);
        
        if (empty($branchIds)) {
            return response()->json([]);
        }

        $districts = District::whereIn('branch_id', $branchIds)
            ->with('branch')
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'branch_id' => $d->branch_id,
            ]);

        return response()->json($districts);
    }

    /**
     * Get centres filtered by district IDs
     */
    public function getCentresByDistricts(Request $request)
    {
        $districtIds = $request->input('district_ids', []);
        
        if (empty($districtIds)) {
            return response()->json([]);
        }

        $centres = Centre::whereHas('districts', function ($q) use ($districtIds) {
            $q->whereIn('district_id', $districtIds);
        })
        ->get()
        ->map(fn($c) => [
            'id' => $c->id,
            'title' => $c->title,
        ]);

        return response()->json($centres);
    }

    /**
     * Get courses filtered by centre IDs
     */
    public function getCoursesByCentres(Request $request)
    {
        $centreIds = $request->input('centre_ids', []);
        
        if (empty($centreIds)) {
            return response()->json([]);
        }

        $courses = Course::whereIn('centre_id', $centreIds)
            ->with('centre', 'programme')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->course_name ?? 'Unnamed Course',
                'centre_id' => $c->centre_id,
            ]);

        return response()->json($courses);
    }

    /**
     * Get sessions filtered by course IDs
     * Returns both Master Sessions and Course Sessions
     */
    public function getSessionsByCourses(Request $request)
    {
        $courseIds = $request->input('course_ids', []);
        
        if (empty($courseIds)) {
            return response()->json([]);
        }

        $courseSessions = CourseSession::whereIn('course_id', $courseIds)
            ->with('course')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'title' => $s->name ?? 'Session',
                'type' => 'course_session',
                'course_id' => $s->course_id,
            ]);

        // Also include associated master sessions
        $masterSessions = MasterSession::whereHas('centreSessions', function ($q) use ($courseIds) {
            $q->whereIn('course_id', $courseIds);
        })
        ->get()
        ->map(fn($s) => [
            'id' => $s->id,
            'title' => $s->master_name ?? 'Master Session',
            'type' => 'master_session',
        ]);

        return response()->json([...$courseSessions, ...$masterSessions]);
    }

    /**
     * Get all branches
     */
    public function getBranches()
    {
        $branches = Branch::all()
            ->map(fn($b) => [
                'id' => $b->id,
                'title' => $b->title,
            ]);

        return response()->json($branches);
    }
}
