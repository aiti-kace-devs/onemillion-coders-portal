<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAdmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdmissionsDashboardService
{
    protected int $cacheTtl = 3600; // 1 hour

    /**
     * Get summary statistics for the dashboard
     */
    public function getSummaryStats(): array
    {
        return Cache::flexible('admissions_dashboard_summary', [now()->addMinutes(30), now()->addHour()], function () {
            return [
                'total_registered' => User::count(),
                'total_admitted' => UserAdmission::whereNotNull('confirmed')->count(),
                'total_shortlisted' => User::where('shortlist', 1)->count(),
                'waiting_list' => User::where('shortlist', 1)
                    ->whereDoesntHave('admission')
                    ->count(),
            ];
        });
    }

    /**
     * Get admissions breakdown by Branch with Gender distribution
     */
    public function getAdmissionsByBranch(): array
    {
        return Cache::flexible('admissions_by_branch_v3', [now()->addMinutes(30), now()->addHour()], function () {
            return DB::table('branches as b')
                ->leftJoin('centres as c', 'b.id', '=', 'c.branch_id')
                ->leftJoin('courses as co', 'c.id', '=', 'co.centre_id')
                ->leftJoin('user_admission as ua', 'co.id', '=', 'ua.course_id')
                ->leftJoin('users as u', 'ua.user_id', '=', 'u.userId')
                ->select(
                    'b.id',
                    'b.title as label',
                    DB::raw('COUNT(ua.id) as count'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' THEN 1 ELSE 0 END) as male"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' THEN 1 ELSE 0 END) as female")
                )
                ->whereNotNull('ua.confirmed')
                ->groupBy('b.id', 'b.title')
                ->orderByDesc('count')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get admissions breakdown by Programme with Gender distribution
     */
    public function getAdmissionsByProgramme(): array
    {
        return Cache::flexible('admissions_by_programme_v3', [now()->addMinutes(30), now()->addHour()], function () {
            return DB::table('programmes as p')
                ->leftJoin('courses as c', 'p.id', '=', 'c.programme_id')
                ->leftJoin('user_admission as ua', 'c.id', '=', 'ua.course_id')
                ->leftJoin('users as u', 'ua.user_id', '=', 'u.userId')
                ->select(
                    'p.id',
                    'p.title as label',
                    DB::raw('COUNT(ua.id) as count'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' THEN 1 ELSE 0 END) as male"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' THEN 1 ELSE 0 END) as female")
                )
                ->whereNotNull('ua.confirmed')
                ->groupBy('p.id', 'p.title')
                ->orderByDesc('count')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get admissions breakdown by Centre with Top Programme and Gender
     */
    public function getAdmissionsByCentre(): array
    {
        return Cache::flexible('admissions_by_centre_v2', [now()->addMinutes(30), now()->addHour()], function () {
            $centres = DB::table('centres as c')
                ->leftJoin('courses as co', 'c.id', '=', 'co.centre_id')
                ->leftJoin('user_admission as ua', 'co.id', '=', 'ua.course_id')
                ->leftJoin('users as u', 'ua.user_id', '=', 'u.userId')
                ->select(
                    'c.id',
                    'c.title as label',
                    DB::raw('COUNT(ua.id) as count'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' THEN 1 ELSE 0 END) as male"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' THEN 1 ELSE 0 END) as female")
                )
                ->whereNotNull('ua.confirmed')
                ->groupBy('c.id', 'c.title')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            return $centres->map(function ($centre) {
                // Find top programme for this centre
                $topProgramme = DB::table('programmes as p')
                    ->join('courses as co', 'p.id', '=', 'co.programme_id')
                    ->join('user_admission as ua', 'co.id', '=', 'ua.course_id')
                    ->select('p.title', DB::raw('COUNT(ua.id) as admissions'))
                    ->where('co.centre_id', $centre->id)
                    ->whereNotNull('ua.confirmed')
                    ->groupBy('p.id', 'p.title')
                    ->orderByDesc('admissions')
                    ->first();

                $centre->top_programme = $topProgramme ? $topProgramme->title : 'N/A';
                return (array) $centre;
            })->toArray();
        });
    }

    /**
     * Get detailed programme breakdown for a specific branch (for View All)
     */
    public function getBranchProgrammeDetails(int $branchId): array
    {
        return Cache::flexible("branch_details_{$branchId}_v3", [now()->addMinutes(30), now()->addHour()], function () use ($branchId) {
            $levels = [User::beginner, User::intermediate, User::advanced];

            $stats = DB::table('programmes as p')
                ->join('courses as c', 'p.id', '=', 'c.programme_id')
                ->join('centres as ctr', 'c.centre_id', '=', 'ctr.id')
                ->leftJoin('users as u', 'c.id', '=', 'u.registered_course')
                ->select(
                    'p.id',
                    'p.title',
                    'p.level',
                    DB::raw('COUNT(u.id) as registered'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as male_admitted"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as female_admitted")
                )
                ->where('ctr.branch_id', $branchId)
                ->groupBy('p.id', 'p.title', 'p.level')
                ->orderByDesc('admitted')
                ->get();

            // Calculate mismatch counts for all programmes in this branch in one query
            $mismatches = DB::table('users as u')
                ->join('courses as c', 'u.registered_course', '=', 'c.id')
                ->join('programmes as p', 'c.programme_id', '=', 'p.id')
                ->join('centres as ctr', 'c.centre_id', '=', 'ctr.id')
                ->select(
                    'p.id',
                    DB::raw('COUNT(u.id) as registered_mismatch'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted_mismatch')
                )
                ->where('ctr.branch_id', $branchId)
                ->whereNotNull('u.student_level')
                ->whereNotNull('p.level')
                ->whereRaw("
                    (LOWER(u.student_level) = 'beginner' AND LOWER(p.level) IN ('intermediate', 'advanced')) OR
                    (LOWER(u.student_level) = 'intermediate' AND LOWER(p.level) = 'advanced')
                ")
                ->groupBy('p.id')
                ->get()
                ->keyBy('id')
                ->toArray();

            return $stats->map(function ($item) use ($mismatches) {
                $m = $mismatches[$item->id] ?? null;
                $item->registered_mismatch = $m ? $m->registered_mismatch : 0;
                $item->admitted_mismatch = $m ? $m->admitted_mismatch : 0;
                return (array) $item;
            })->toArray();
        });
    }

    /**
     * Get detailed programme breakdown for a specific district (for View All)
     */
    public function getDistrictProgrammeDetails(int $districtId): array
    {
        return Cache::flexible("district_details_{$districtId}_v3", [now()->addMinutes(30), now()->addHour()], function () use ($districtId) {
            $stats = DB::table('programmes as p')
                ->join('courses as c', 'p.id', '=', 'c.programme_id')
                ->join('district_centre as dc', 'c.centre_id', '=', 'dc.centre_id')
                ->leftJoin('users as u', 'c.id', '=', 'u.registered_course')
                ->select(
                    'p.id',
                    'p.title',
                    'p.level',
                    DB::raw('COUNT(u.id) as registered'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as male_admitted"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as female_admitted")
                )
                ->where('dc.district_id', $districtId)
                ->groupBy('p.id', 'p.title', 'p.level')
                ->orderByDesc('admitted')
                ->get();

            $mismatches = DB::table('users as u')
                ->join('courses as c', 'u.registered_course', '=', 'c.id')
                ->join('programmes as p', 'c.programme_id', '=', 'p.id')
                ->join('district_centre as dc', 'c.centre_id', '=', 'dc.centre_id')
                ->select(
                    'p.id',
                    DB::raw('COUNT(u.id) as registered_mismatch'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted_mismatch')
                )
                ->where('dc.district_id', $districtId)
                ->whereNotNull('u.student_level')
                ->whereNotNull('p.level')
                ->whereRaw("
                    (LOWER(u.student_level) = 'beginner' AND LOWER(p.level) IN ('intermediate', 'advanced')) OR
                    (LOWER(u.student_level) = 'intermediate' AND LOWER(p.level) = 'advanced')
                ")
                ->groupBy('p.id')
                ->get()
                ->keyBy('id')
                ->toArray();

            return $stats->map(function ($item) use ($mismatches) {
                $m = $mismatches[$item->id] ?? null;
                $item->registered_mismatch = $m ? $m->registered_mismatch : 0;
                $item->admitted_mismatch = $m ? $m->admitted_mismatch : 0;
                return (array) $item;
            })->toArray();
        });
    }

    /**
     * Get detailed programme breakdown for a specific constituency (for View All)
     */
    public function getConstituencyProgrammeDetails(int $constituencyId): array
    {
        return Cache::flexible("constituency_details_{$constituencyId}_v3", [now()->addMinutes(30), now()->addHour()], function () use ($constituencyId) {
            $stats = DB::table('programmes as p')
                ->join('courses as c', 'p.id', '=', 'c.programme_id')
                ->join('centres as ctr', 'c.centre_id', '=', 'ctr.id')
                ->leftJoin('users as u', 'c.id', '=', 'u.registered_course')
                ->select(
                    'p.id',
                    'p.title',
                    'p.level',
                    DB::raw('COUNT(u.id) as registered'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as male_admitted"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as female_admitted")
                )
                ->where('ctr.constituency_id', $constituencyId)
                ->groupBy('p.id', 'p.title', 'p.level')
                ->orderByDesc('admitted')
                ->get();

            $mismatches = DB::table('users as u')
                ->join('courses as c', 'u.registered_course', '=', 'c.id')
                ->join('programmes as p', 'c.programme_id', '=', 'p.id')
                ->join('centres as ctr', 'c.centre_id', '=', 'ctr.id')
                ->select(
                    'p.id',
                    DB::raw('COUNT(u.id) as registered_mismatch'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted_mismatch')
                )
                ->where('ctr.constituency_id', $constituencyId)
                ->whereNotNull('u.student_level')
                ->whereNotNull('p.level')
                ->whereRaw("
                    (LOWER(u.student_level) = 'beginner' AND LOWER(p.level) IN ('intermediate', 'advanced')) OR
                    (LOWER(u.student_level) = 'intermediate' AND LOWER(p.level) = 'advanced')
                ")
                ->groupBy('p.id')
                ->get()
                ->keyBy('id')
                ->toArray();

            return $stats->map(function ($item) use ($mismatches) {
                $m = $mismatches[$item->id] ?? null;
                $item->registered_mismatch = $m ? $m->registered_mismatch : 0;
                $item->admitted_mismatch = $m ? $m->admitted_mismatch : 0;
                return (array) $item;
            })->toArray();
        });
    }

    /**
     * Get detailed breakdown for a specific programme overall (for View All)
     */
    public function getProgrammeDetailedStats(string $programmeTitle): array
    {
        return Cache::flexible("programme_full_details_v2_" . md5($programmeTitle), [now()->addMinutes(30), now()->addHour()], function () use ($programmeTitle) {
            $stats = DB::table('programmes as p')
                ->join('courses as c', 'p.id', '=', 'c.programme_id')
                ->leftJoin('users as u', 'c.id', '=', 'u.registered_course')
                ->select(
                    'p.id',
                    'p.title',
                    'p.level',
                    DB::raw('COUNT(u.id) as registered'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as male_admitted"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as female_admitted")
                )
                ->where('p.title', $programmeTitle)
                ->groupBy('p.id', 'p.title', 'p.level')
                ->first();

            if (!$stats) return [];

            $mismatch = DB::table('users as u')
                ->join('courses as c', 'u.registered_course', '=', 'c.id')
                ->join('programmes as p', 'c.programme_id', '=', 'p.id')
                ->select(
                    DB::raw('COUNT(u.id) as registered_mismatch'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted_mismatch')
                )
                ->where('p.title', $programmeTitle)
                ->whereNotNull('u.student_level')
                ->whereNotNull('p.level')
                ->whereRaw("
                    (LOWER(u.student_level) = 'beginner' AND LOWER(p.level) IN ('intermediate', 'advanced')) OR
                    (LOWER(u.student_level) = 'intermediate' AND LOWER(p.level) = 'advanced')
                ")
                ->first();

            $stats->registered_mismatch = $mismatch ? $mismatch->registered_mismatch : 0;
            $stats->admitted_mismatch = $mismatch ? $mismatch->admitted_mismatch : 0;
            return [(array) $stats];
        });
    }

    /**
     * Get detailed programme breakdown for a specific centre (for AJAX modal)
     */
    public function getCentreProgrammeDetails(int $centreId): array
    {
        return Cache::flexible("centre_details_{$centreId}_v5", [now()->addMinutes(30), now()->addHour()], function () use ($centreId) {
            $stats = DB::table('programmes as p')
                ->join('courses as c', 'p.id', '=', 'c.programme_id')
                ->leftJoin('users as u', 'c.id', '=', 'u.registered_course')
                ->select(
                    'p.id',
                    'p.title',
                    'p.level',
                    DB::raw('COUNT(u.id) as registered'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as male_admitted"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as female_admitted")
                )
                ->where('c.centre_id', $centreId)
                ->groupBy('p.id', 'p.title', 'p.level')
                ->orderByDesc('admitted')
                ->get();

            $mismatches = DB::table('users as u')
                ->join('courses as c', 'u.registered_course', '=', 'c.id')
                ->join('programmes as p', 'c.programme_id', '=', 'p.id')
                ->select(
                    'p.id',
                    DB::raw('COUNT(u.id) as registered_mismatch'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = u.userId AND ua.confirmed IS NOT NULL AND ua.course_id = c.id) THEN 1 ELSE 0 END) as admitted_mismatch')
                )
                ->where('c.centre_id', $centreId)
                ->whereNotNull('u.student_level')
                ->whereNotNull('p.level')
                ->whereRaw("
                    (LOWER(u.student_level) = 'beginner' AND LOWER(p.level) IN ('intermediate', 'advanced')) OR
                    (LOWER(u.student_level) = 'intermediate' AND LOWER(p.level) = 'advanced')
                ")
                ->groupBy('p.id')
                ->get()
                ->keyBy('id')
                ->toArray();

            return $stats->map(function ($item) use ($mismatches) {
                $m = $mismatches[$item->id] ?? null;
                $item->registered_mismatch = $m ? $m->registered_mismatch : 0;
                $item->admitted_mismatch = $m ? $m->admitted_mismatch : 0;
                return (array) $item;
            })->toArray();
        });
    }

    /**
     * Get level mismatch statistics with Gender
     */
    public function getLevelMismatchStats(): array
    {
        return Cache::flexible('admissions_level_mismatch_v2', [now()->addMinutes(30), now()->addHour()], function () {
            $levels = [User::beginner, User::intermediate, User::advanced];

            $query = User::query()
                ->join('courses as c', 'users.registered_course', '=', 'c.id')
                ->join('programmes as p', 'c.programme_id', '=', 'p.id')
                ->select(
                    'users.student_level as user_level',
                    'p.level as programme_level',
                    DB::raw('COUNT(users.id) as registered_count'),
                    DB::raw('SUM(CASE WHEN users.shortlist = 1 THEN 1 ELSE 0 END) as shortlisted_count'),
                    DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = users.userId AND ua.confirmed IS NOT NULL) THEN 1 ELSE 0 END) as admitted_count'),
                    DB::raw("SUM(CASE WHEN users.gender = 'male' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = users.userId AND ua.confirmed IS NOT NULL) THEN 1 ELSE 0 END) as male_admitted"),
                    DB::raw("SUM(CASE WHEN users.gender = 'female' AND EXISTS (SELECT 1 FROM user_admission ua WHERE ua.user_id = users.userId AND ua.confirmed IS NOT NULL) THEN 1 ELSE 0 END) as female_admitted")
                )
                ->whereNotNull('users.student_level')
                ->whereNotNull('p.level')
                ->groupBy('users.student_level', 'p.level')
                ->get();

            $mismatched = $query->filter(function ($item) use ($levels) {
                $uIdx = array_search(strtolower($item['user_level']), $levels);
                $pIdx = array_search(strtolower($item['programme_level']), $levels);

                return $uIdx !== false && $pIdx !== false && $pIdx > $uIdx;
            });

            return [
                'registered' => $mismatched->sum('registered_count'),
                'shortlisted' => $mismatched->sum('shortlisted_count'),
                'admitted' => $mismatched->sum('admitted_count'),
                'male_admitted' => $mismatched->sum('male_admitted'),
                'female_admitted' => $mismatched->sum('female_admitted'),
                'details' => $mismatched->values()->toArray()
            ];
        });
    }

    /**
     * Get admissions breakdown by District with Gender
     */
    public function getAdmissionsByDistrict(): array
    {
        return Cache::flexible('admissions_by_district_v3', [now()->addMinutes(30), now()->addHour()], function () {
            return DB::table('districts as d')
                ->join('district_centre as dc', 'd.id', '=', 'dc.district_id')
                ->join('centres as c', 'dc.centre_id', '=', 'c.id')
                ->join('courses as co', 'c.id', '=', 'co.centre_id')
                ->join('user_admission as ua', 'co.id', '=', 'ua.course_id')
                ->leftJoin('users as u', 'ua.user_id', '=', 'u.userId')
                ->select(
                    'd.id',
                    'd.title as label',
                    DB::raw('COUNT(ua.id) as count'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' THEN 1 ELSE 0 END) as male"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' THEN 1 ELSE 0 END) as female")
                )
                ->whereNotNull('ua.confirmed')
                ->groupBy('d.id', 'd.title')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray();
        });
    }

    /**
     * Get admissions breakdown by Constituency with Gender
     */
    public function getAdmissionsByConstituency(): array
    {
        return Cache::flexible('admissions_by_constituency_v3', [now()->addMinutes(30), now()->addHour()], function () {
            return DB::table('constituencies as con')
                ->join('centres as c', 'con.id', '=', 'c.constituency_id')
                ->join('courses as co', 'c.id', '=', 'co.centre_id')
                ->join('user_admission as ua', 'co.id', '=', 'ua.course_id')
                ->leftJoin('users as u', 'ua.user_id', '=', 'u.userId')
                ->select(
                    'con.id',
                    'con.title as label',
                    DB::raw('COUNT(ua.id) as count'),
                    DB::raw("SUM(CASE WHEN u.gender = 'male' THEN 1 ELSE 0 END) as male"),
                    DB::raw("SUM(CASE WHEN u.gender = 'female' THEN 1 ELSE 0 END) as female")
                )
                ->whereNotNull('ua.confirmed')
                ->groupBy('con.id', 'con.title')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray();
        });
    }
}
