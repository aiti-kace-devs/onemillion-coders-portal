<?php

namespace App\Services\Scheduling;

use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use Illuminate\Support\Collection;

class SessionAlternativesService
{
    private const MAX_ALTERNATIVES = 20;

    public function __construct(
        private ProgrammeQuotaService $quotaService,
    ) {}

    /**
     * @return array{same_centre_other_courses: array, same_course_other_centres: array}
     */
    public function build(User $user, Course $course, ?int $preferredCentreId): array
    {
        if (! StudentSessionFlow::requiresCentreSupportFlow($user, $course)) {
            return [
                'same_centre_other_courses' => [],
                'same_course_other_centres' => [],
            ];
        }

        $preferredCentreId = $preferredCentreId ?? $course->centre_id;
        if (! $preferredCentreId) {
            return [
                'same_centre_other_courses' => [],
                'same_course_other_centres' => [],
            ];
        }

        $preferredCentre = Centre::query()
            ->with(['branch', 'constituency', 'districts'])
            ->find($preferredCentreId);

        if (! $preferredCentre) {
            return [
                'same_centre_other_courses' => [],
                'same_course_other_centres' => [],
            ];
        }

        return [
            'same_centre_other_courses' => $this->sameCentreOtherCourses($course, $preferredCentreId),
            'same_course_other_centres' => $this->sameCourseOtherCentres($course, $preferredCentre, $preferredCentreId),
        ];
    }

    private function sameCentreOtherCourses(Course $course, int $preferredCentreId): array
    {
        $courses = Course::query()
            ->with([
                'programme',
                'sessions' => fn ($q) => $q->courseType()->where('status', true)->orderBy('session'),
            ])
            ->where('centre_id', $preferredCentreId)
            ->where('batch_id', $course->batch_id)
            ->where('id', '!=', $course->id)
            ->limit(self::MAX_ALTERNATIVES)
            ->get();

        $out = [];
        foreach ($courses as $c) {
            $sessions = $this->mapSessionsWithSlots($c, $user);
            if ($sessions === []) {
                continue;
            }
            $out[] = [
                'course_id' => $c->id,
                'course_name' => $c->course_name,
                'programme_id' => $c->programme_id,
                'sessions' => $sessions,
            ];
        }

        return $out;
    }

    private function sameCourseOtherCentres(Course $course, Centre $preferredCentre, int $preferredCentreId): array
    {
        $courses = Course::query()
            ->with([
                'centre.branch',
                'centre.constituency',
                'centre.districts',
                'sessions' => fn ($q) => $q->courseType()->where('status', true)->orderBy('session'),
            ])
            ->where('programme_id', $course->programme_id)
            ->where('batch_id', $course->batch_id)
            ->where('centre_id', '!=', $preferredCentreId)
            ->get();

        $byCentre = $courses->groupBy('centre_id');
        $rows = [];

        foreach ($byCentre as $centreId => $group) {
            /** @var Course $first */
            $first = $group->first();
            $centre = $first->centre;
            if (! $centre) {
                continue;
            }

            $sessionsOut = [];
            foreach ($group as $c) {
                foreach ($this->mapSessionsWithSlots($c, $user) as $s) {
                    $sessionsOut[] = $s;
                }
            }

            if ($sessionsOut === []) {
                continue;
            }

            $geo = $this->geoMeta($preferredCentre, $centre);

            $rows[] = [
                'centre_id' => $centre->id,
                'centre_title' => $centre->title,
                'branch_id' => $centre->branch_id,
                'branch_title' => $centre->branch?->title,
                'geo_tier' => $geo['geo_tier'],
                'distance_km' => $geo['distance_km'],
                'course_id' => $first->id,
                'sessions' => $sessionsOut,
                '_rank' => $geo['rank'],
                '_distance' => $geo['distance_km'] ?? PHP_FLOAT_MAX,
            ];
        }

        return collect($rows)
            ->sort(function (array $a, array $b) {
                if ($a['_rank'] !== $b['_rank']) {
                    return $a['_rank'] <=> $b['_rank'];
                }
                $da = $a['_distance'] ?? PHP_FLOAT_MAX;
                $db = $b['_distance'] ?? PHP_FLOAT_MAX;
                if ($da !== $db) {
                    return $da <=> $db;
                }

                return strcmp((string) $a['centre_title'], (string) $b['centre_title']);
            })
            ->take(self::MAX_ALTERNATIVES)
            ->map(fn (array $r) => array_diff_key($r, ['_rank' => 1, '_distance' => 1]))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapSessionsWithSlots(Course $course, User $user): array
    {
        $programme = $course->programme;
        if (! $programme) {
            return [];
        }

        $out = [];
        foreach ($course->sessions as $session) {
            if (! $session instanceof CourseSession) {
                continue;
            }
            $slotsLeft = $session->slotLeft();
            if ($slotsLeft < 1) {
                continue;
            }
            $quotaOk = $this->quotaService->hasCapacityForNewConfirmation($programme, $course, $user);
            if (! $quotaOk) {
                continue;
            }
            $out[] = [
                'course_session_id' => $session->id,
                'session_label' => $session->session,
                'slots_left' => $slotsLeft,
                'course_time' => $session->course_time,
                'status' => (bool) $session->status,
            ];
        }

        return $out;
    }

    /**
     * @return array{geo_tier: string, distance_km: float|null, rank: int}
     */
    private function geoMeta(Centre $preferred, Centre $other): array
    {
        $distance = $this->distanceKm($preferred, $other);

        if ($preferred->id === $other->id) {
            return ['geo_tier' => 'same_centre', 'distance_km' => $distance ?? 0.0, 'rank' => 0];
        }

        if ($preferred->constituency_id
            && $other->constituency_id
            && (int) $preferred->constituency_id === (int) $other->constituency_id) {
            return ['geo_tier' => 'same_constituency', 'distance_km' => $distance, 'rank' => 1];
        }

        $prefDistricts = $preferred->relationLoaded('districts')
            ? $preferred->districts->pluck('id')
            : $preferred->districts()->pluck('id');
        $otherDistricts = $other->relationLoaded('districts')
            ? $other->districts->pluck('id')
            : $other->districts()->pluck('id');

        if ($prefDistricts instanceof Collection
            && $otherDistricts instanceof Collection
            && $prefDistricts->intersect($otherDistricts)->isNotEmpty()) {
            return ['geo_tier' => 'shared_district', 'distance_km' => $distance, 'rank' => 2];
        }

        if ($preferred->branch_id
            && $other->branch_id
            && (int) $preferred->branch_id === (int) $other->branch_id) {
            return ['geo_tier' => 'same_branch', 'distance_km' => $distance, 'rank' => 3];
        }

        return ['geo_tier' => 'other', 'distance_km' => $distance, 'rank' => 4];
    }

    private function distanceKm(Centre $a, Centre $b): ?float
    {
        $ca = $this->coordinates($a);
        $cb = $this->coordinates($b);
        if (! $ca || ! $cb) {
            return null;
        }

        $earth = 6371.0;
        [$lat1, $lon1] = $ca;
        [$lat2, $lon2] = $cb;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $x = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return round(2 * $earth * asin(min(1, sqrt($x))), 2);
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function coordinates(Centre $centre): ?array
    {
        $loc = $centre->gps_location;
        if (! is_array($loc)) {
            return null;
        }
        $lat = $loc['lat'] ?? $loc['latitude'] ?? null;
        $lng = $loc['lng'] ?? $loc['longitude'] ?? null;
        if ($lat === null || $lng === null) {
            return null;
        }

        return [(float) $lat, (float) $lng];
    }
}
