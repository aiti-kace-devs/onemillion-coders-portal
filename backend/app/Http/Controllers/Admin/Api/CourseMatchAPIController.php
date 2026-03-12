<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Centre;
use App\Models\CourseMatch;
use App\Models\CourseCategory;
use App\Models\Branch;
use App\Models\UserAdmission;
use App\Models\Programme;
use App\Models\Course;
use App\Models\Batch;
use App\Models\CourseBatch;
use App\Models\User;
use App\Models\CourseMatchOption;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseMatchAPIController extends Controller
{



    public function index(Request $request)
    {
        $courseMatch = CourseMatch::with(['courseMatchOptions' => function ($query) {
            $query->where('status', 1);
        }])
            ->where('status', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $courseMatch
        ]);
    }





    public function fullRecommendation(Request $request)
    {
        $data = $request->validate([
            'option_ids' => 'required|array',
            'option_ids.*' => 'integer|exists:course_match_options,id',
        ]);

        $optionIds = $data['option_ids'];
        $totalOptions = count($optionIds);

        // Get all Programmes with their tags (CourseMatchOptions)
        $programmes = Programme::with('tags')
            ->where('status', 1)
            ->get();

        // Score each programme by how many of the selected options it has
        $scored = $programmes->map(function ($programme) use ($optionIds, $totalOptions) {
            $programmeOptionIds = $programme->tags->pluck('id')->toArray();
            $matches = count(array_intersect($optionIds, $programmeOptionIds));
            $percentage = $totalOptions > 0 ? round(($matches / $totalOptions) * 100) : 0;
            $programme->match_percentage = $percentage;
            $programme->match_count = $matches;
            return $programme;
        });

        // Get top 5 matches with at least 1 match, sorted by match count and percentage
        $top = $scored->filter(fn($p) => $p->match_count > 0)
                      ->sortByDesc('match_percentage')
                      ->take(5)
                      ->values();

        // Return all Programme fields + match_percentage
        $result = $top->map(function ($programme) {
            $arr = $programme->toArray();
            $arr['match_percentage'] = $programme->match_percentage;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'ttile' => 'Your Course Matches',
            'description' => 'Based on your preferences, here are recommended courses that align best with your goals',
            'matches' => $result,
        ]);
    }




        public function recommendCourses(Request $request)
        {
            $data = $request->validate([
                'option_ids' => 'required|array',
                'option_ids.*' => 'integer|exists:course_match_options,id',
                'userId' => 'required|exists:users,userId',
                'centre_id' => 'required|integer|exists:centres,id',
            ]);

            $user = User::where('userId', $data['userId'])->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            $optionIds = array_values($data['option_ids']);
            $centreId = (int) $data['centre_id'];

            $studentLevel = strtolower(trim((string) $user?->student_level));
            // Log::info('Student level: ' . $studentLevel);

            $centreCourses = $this->getCentreCourses($centreId);
            $programmeIds = $centreCourses->pluck('programme_id')->unique()->values()->all();

            [$preferredDelivery, $deliveryOptionIds] = $this->detectPreferredDelivery($optionIds);
            $scoringOptionIds = array_values(array_diff($optionIds, $deliveryOptionIds));

            $programmes = $this->getProgrammesForLevel($programmeIds, $studentLevel, $preferredDelivery);
            $scored = $this->scoreProgrammes($programmes, $scoringOptionIds);

            $hasScoringOptions = count($scoringOptionIds) > 0;
            if (!$hasScoringOptions) {
                $scored = $scored->map(function ($programme) {
                    $programme->setAttribute('match_percentage', 100);
                    $programme->setAttribute('match_count', 0);
                    return $programme;
                });
            }

            // Filter and sort top 5 matches
            $top = $hasScoringOptions
                ? $scored->filter(fn ($p) => $p->match_count > 0)
                    ->sortByDesc('match_percentage')
                    ->take(5)
                    ->values()
                : $scored->sortBy('title')->take(5)->values();

            $combined = $top;
            $limit = 5;
            if ($top->count() < $limit) {
                $topProgrammeIds = $top->pluck('id')->all();

                $extras = $scored
                    ->reject(fn ($programme) => in_array($programme->id, $topProgrammeIds, true))
                    ->sortByDesc('match_percentage')
                    ->take($limit - $top->count())
                    ->values();

                $combined = $top->concat($extras)->values();
            }

            // Format response with ranking number and the selected centre
            $result = $combined->map(function ($programme, $index) use ($centreCourses, $centreId) {
                $programmeCourses = $centreCourses->where('programme_id', $programme->id);

                return [
                    'rank' => '#' . ($index + 1),
                    'id' => $programme->id,
                    'title' => $programme->title,
                    'sub_title' => $programme->sub_title,
                    'duration' => $programme->duration,
                    'level' => $programme->level,
                    'image' => $programme->image,
                    'job_responsible' => $programme->job_responsible,
                    'prerequisites' => $programme->prerequisites,
                    'mode_of_delivery' => $programme->mode_of_delivery,
                    'provider' => $programme->provider,
                    'match_percentage' => $programme->match_percentage . '% Match',
                    'course_id' => $programmeCourses->first()?->id,
                    'centre_id' => $centreId,
                ];
            });

            $this->storeRecommendations($combined, $centreCourses, $centreId, $optionIds, $studentLevel, $data['userId']);

            return response()->json([
                'success' => true,
                'title' => 'Your Course Matches',
                'description' => 'Based on your preferences, here are recommended courses that align best with your goals',
                'matches' => $result,
            ]);
        }

        /**
         * @return \Illuminate\Support\Collection<int, \App\Models\Course>
         */
        protected function getCentreCourses(int $centreId)
        {
            $today = Carbon::today()->toDateString();

            return Course::join('admission_batches', 'courses.batch_id', '=', 'admission_batches.id')
                ->where('courses.centre_id', $centreId)
                ->whereNotNull('courses.programme_id')
                ->where('courses.status', 1)
                ->where('admission_batches.start_date', '<=', $today)
                ->where('admission_batches.end_date', '>=', $today)
                ->where('admission_batches.completed', false)
                ->where('admission_batches.status', true)
                ->get(['courses.id', 'courses.programme_id', 'courses.centre_id']);
        }

        /**
         * @param  array<int>  $programmeIds
         * @return \Illuminate\Support\Collection<int, \App\Models\Programme>
         */
        protected function getProgrammesForLevel(array $programmeIds, string $studentLevel, ?string $preferredDelivery = null)
        {
            $query = Programme::select('id', 'title', 'sub_title', 'duration', 'level', 'job_responsible', 'image', 'prerequisites', 'mode_of_delivery', 'provider')
                ->with('tags')
                ->whereIn('id', $programmeIds)
                ->where('status', true);

            if ($studentLevel !== '') {
                $query->whereRaw('LOWER(level) = ?', [$studentLevel]);
            }

            if ($preferredDelivery) {
                $query->whereRaw('LOWER(mode_of_delivery) = ?', [strtolower($preferredDelivery)]);
            }

            return $query->get();
        }

        /**
         * @param  array<int>  $optionIds
         */
        /**
         * @param  array<int>  $optionIds
         * @return array{0: string|null, 1: array<int>}
         */
        protected function detectPreferredDelivery(array $optionIds): array
        {
            $options = CourseMatchOption::whereIn('id', $optionIds)
                ->get(['id', 'answer', 'value']);

            $detected = [
                'In Person' => [],
                'Online' => [],
            ];

            foreach ($options as $option) {
                $raw = $option->value ?: $option->answer;
                $normalized = $this->normalizeOptionValue($raw);

                if ($normalized === 'in person') {
                    $detected['In Person'][] = $option->id;
                    continue;
                }

                if (in_array($normalized, ['online', 'online for all'], true)) {
                    $detected['Online'][] = $option->id;
                }
            }

            $hasInPerson = count($detected['In Person']) > 0;
            $hasOnline = count($detected['Online']) > 0;

            if ($hasInPerson && !$hasOnline) {
                return ['In Person', array_values(array_unique($detected['In Person']))];
            }

            if ($hasOnline && !$hasInPerson) {
                return ['Online', array_values(array_unique($detected['Online']))];
            }

            return [null, []];
        }

        protected function normalizeOptionValue(?string $value): string
        {
            $value = strtolower(trim((string) $value));
            $value = preg_replace('/[^a-z]+/', ' ', $value);
            $value = preg_replace('/\s+/', ' ', $value);

            return trim($value);
        }

        /**
         * @param  array<int>  $programmeIds
         * @return \Illuminate\Support\Collection<int, \App\Models\Programme>
         */
        protected function getOnlineProgrammes(array $programmeIds, string $studentLevel)
        {
            $query = Programme::select('id', 'title', 'sub_title', 'duration', 'level', 'job_responsible', 'image', 'prerequisites', 'mode_of_delivery', 'provider')
                ->with('tags')
                ->whereIn('id', $programmeIds)
                ->where('status', true)
                ->whereRaw('LOWER(mode_of_delivery) = ?', ['online']);

            if ($studentLevel !== '') {
                $query->whereRaw('LOWER(level) = ?', [$studentLevel]);
            }

            return $query->get();
        }

        /**
         * @param  \Illuminate\Support\Collection<int, \App\Models\Programme>  $programmes
         * @param  array<int>  $optionIds
         * @return \Illuminate\Support\Collection<int, \App\Models\Programme>
         */
        protected function scoreProgrammes($programmes, array $optionIds)
        {
            $totalOptions = count($optionIds);

            return $programmes->map(function ($programme) use ($optionIds, $totalOptions) {
                $programmeOptionIds = $programme->tags->pluck('id')->toArray();
                $matches = count(array_intersect($optionIds, $programmeOptionIds));
                $percentage = $totalOptions > 0 ? round(($matches / $totalOptions) * 100) : 0;

                $programme->setAttribute('match_percentage', $percentage);
                $programme->setAttribute('match_count', $matches);
                return $programme;
            });
        }

        /**
         * @param  \Illuminate\Support\Collection<int, \App\Models\Programme>  $combined
         * @param  \Illuminate\Support\Collection<int, \App\Models\Course>  $centreCourses
         * @param  array<int>  $optionIds
         */
        protected function storeRecommendations($combined, $centreCourses, int $centreId, array $optionIds, string $studentLevel, string $userId): void
        {
            if ($combined->isEmpty()) {
                return;
            }

            $now = now();
            $recommendationRows = $combined->map(function ($programme, $index) use ($centreCourses, $centreId, $optionIds, $studentLevel, $now, $userId) {
                $programmeCourses = $centreCourses->where('programme_id', $programme->id);

                return [
                    'user_id' => $userId,
                    'programme_id' => $programme->id,
                    'course_id' => $programmeCourses->first()?->id,
                    'centre_id' => $centreId,
                    'rank' => $index + 1,
                    'match_percentage' => $programme->match_percentage,
                    'match_count' => $programme->match_count,
                    'student_level' => $studentLevel !== '' ? $studentLevel : null,
                    'mode_of_delivery' => $programme->mode_of_delivery,
                    'provider' => $programme->provider,
                    'option_ids' => json_encode($optionIds),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            DB::transaction(function () use ($userId, $centreId, $recommendationRows) {
                DB::table('user_course_recommendations')
                    ->where('user_id', $userId)
                    ->where('centre_id', $centreId)
                    ->delete();

                DB::table('user_course_recommendations')->insert($recommendationRows);
            });
        }
        



    



    public function recommend(Request $request)
    {
        $data = $request->validate([
            'option_ids' => 'required|array',
            'option_ids.*' => 'integer|exists:course_match_options,id',
        ]);
        
        $optionIds = $data['option_ids'];
        $totalOptions = count($optionIds);
        
        // Get Programmes with ONLY needed columns + tags relationship
        $programmes = Programme::select('id', 'title', 'sub_title', 'duration', 'level', 'job_responsible', 'image', 'prerequisites', 'mode_of_delivery', 'provider')
            ->with('tags')
            ->where('status', true)
            ->get();
        
        // Score each programme by matching option IDs
        $scored = $programmes->map(function ($programme) use ($optionIds, $totalOptions) {
            $programmeOptionIds = $programme->tags->pluck('id')->toArray();
            $matches = count(array_intersect($optionIds, $programmeOptionIds));
            $percentage = $totalOptions > 0 ? round(($matches / $totalOptions) * 100) : 0;
        
            $programme->match_percentage = $percentage;
            $programme->match_count = $matches;
            return $programme;
        });
        
        // Filter and sort top 5 matches
        $top = $scored->filter(fn($p) => $p->match_count > 0)
                      ->sortByDesc('match_percentage')
                      ->take(5)
                      ->values();
        
        // Format response with ranking number
        $result = $top->map(function ($programme, $index) {
            return [
                'rank' => '#' . ($index + 1),
                'id' => $programme->id,
                'title' => $programme->title,
                'sub_title' => $programme->sub_title,
                'duration' => $programme->duration,
                'level' => $programme->level,
                'image' => $programme->image,
                'job_responsible' => $programme->job_responsible,
                'image' => $programme->image,
                'prerequisites' => $programme->prerequisites,
                'mode_of_delivery' => $programme->mode_of_delivery,
                'provider' => $programme->provider,
                'match_percentage' => $programme->match_percentage . '% Match',
            ];
        });
        

        return response()->json([
            'success' => true,
            'title' => 'Your Course Matches',
            'description' => 'Based on your preferences, here recommended courses that align best with your goals',
            'matches' => $result,
        ]);
    }
    




    public function allProgrammesWithCourseMatch()
    {
        $programmes = Programme::with(['tags.courseMatch' => function ($query) {
            $query->where('status', 1);
        }])
            ->where('status', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $programmes
        ]);
    }

}
