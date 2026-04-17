<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Centre;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseMatch;
use App\Models\CourseMatchOption;
use App\Models\CourseSession;
use App\Models\Programme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CourseMatchAPIController extends Controller
{
    public function index(Request $request)
    {
        $query = CourseMatch::with([
            'courseMatchOptions' => function ($query) {
                $query->where('status', 1);
            },
        ])
            ->where('status', 1);

        if ($request->has('type')) {
            $type = trim((string) $request->query('type'));
            if ($type !== '') {
                $query->where('type', $type);
            }
        }

        $courseMatch = $query->get();

        return response()->json([
            'success' => true,
            'data' => $courseMatch,
        ]);
    }

    public function checkUserRecommendedCourses(Request $request, string $userId)
    {
        $user = User::where('userId', $userId)->first();
        $registeredCourseId = $user ? (int) $user->registered_course : null;

        $recommendations = DB::table('user_course_recommendations')
            ->where('user_id', $userId)
            ->orderBy('rank')
            ->get();

        if ($recommendations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No recommended courses found.',
            ]);
        }

        // Exclude user's registered course from recommendations
        if ($registeredCourseId) {
            $recommendations = $recommendations->filter(fn ($rec) => (int) $rec->course_id !== $registeredCourseId)->values();
        }

        if ($recommendations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No recommended courses found.',
            ]);
        }

        $courseIds = $recommendations
            ->pluck('course_id')
            ->filter()
            ->unique()
            ->values();

        $courses = Course::with('programme')
            ->whereIn('id', $courseIds)
            ->get()
            ->keyBy('id');

        $matches = $recommendations
            ->map(function ($recommendation, $index) use ($request, $courses) {
                $courseId = $recommendation->course_id;
                $course = $courseId ? $courses->get($courseId) : null;
                $rankValue = $recommendation->rank ?? ($index + 1);

                return $this->buildStoredRecommendationPayload(
                    $request,
                    $course,
                    $rankValue,
                    $recommendation->match_percentage,
                    $recommendation->centre_id ?? $course?->centre_id
                );
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'title' => 'These are Your Recommended Courses',
            'description' => 'Based on your preferences, here are the recommended courses that best align with your goals',
            'matches' => $matches,
        ]);
    }

    public function siblingCourses(Request $request)
    {
        $data = $request->validate([
            'userId' => 'required|exists:users,userId',
            'course_id' => 'nullable|integer|exists:courses,id',
        ]);

        $userId = $data['userId'];
        $user = User::with('course')->where('userId', $userId)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ]);
        }

        $filter = $request->query('filter');
        $sort = $request->query('sort');
        $order = strtolower((string) $request->query('order', 'asc'));
        $limit = $request->query('limit');

        if (is_string($sort) && str_starts_with($sort, '-')) {
            $sort = ltrim($sort, '-');
            $order = 'desc';
        }

        $limit = is_numeric($limit) ? (int) $limit : null;
        if ($limit !== null && $limit <= 0) {
            $limit = null;
        }

        $currentCourseId = $user->registered_course ? (int) $user->registered_course : null;
        $currentCourse = $user->course;

        if ($currentCourseId && ! $currentCourse) {
            Log::warning('siblingCourses: registered course could not be loaded.', [
                'userId' => $userId,
                'registered_course' => $currentCourseId,
            ]);
        }

        $selectedCourseId = isset($data['course_id']) ? (int) $data['course_id'] : $currentCourseId;
        $selectedCourse = null;

        if ($selectedCourseId !== null) {
            $selectedCourse = $currentCourseId === $selectedCourseId
                ? $currentCourse
                : Course::find($selectedCourseId);
        }

        if (! $selectedCourse) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found.',
            ]);
        }

        $selectedCourseId = (int) $selectedCourse->id;
        $selectedCentreId = $selectedCourse->centre_id ? (int) $selectedCourse->centre_id : null;
        $excludedCourseIds = collect([$currentCourseId, $selectedCourseId])
            ->filter()
            ->map(fn ($courseId) => (int) $courseId)
            ->unique()
            ->values()
            ->all();

        $recommendations = DB::table('user_course_recommendations')
            ->where('user_id', $userId)
            ->orderBy('rank')
            ->get();

        $recommendedCourseIds = $recommendations
            ->pluck('course_id')
            ->filter()
            ->map(fn ($courseId) => (int) $courseId)
            ->reject(fn ($courseId) => in_array($courseId, $excludedCourseIds, true))
            ->unique()
            ->values();

        $recommendedCourses = Course::with('programme')
            ->whereIn('id', $recommendedCourseIds)
            ->get()
            ->keyBy('id');

        $matches = $recommendations
            ->map(function ($recommendation, $index) use ($request, $recommendedCourses, $excludedCourseIds) {
                $courseId = $recommendation->course_id ? (int) $recommendation->course_id : null;

                if (! $courseId || in_array($courseId, $excludedCourseIds, true)) {
                    return null;
                }

                $rankValue = $recommendation->rank ?? ($index + 1);

                return $this->buildStoredRecommendationPayload(
                    $request,
                    $recommendedCourses->get($courseId),
                    $rankValue,
                    $recommendation->match_percentage,
                    $recommendation->centre_id
                );
            })
            ->filter()
            ->values();

        // Apply filter to matches
        if (is_string($filter) && $filter !== '') {
            $filterLower = strtolower($filter);
            $matches = $matches->filter(function ($match) use ($filterLower) {
                return $this->courseMatchesFilter($match, $filterLower);
            })->values();
        }

        // Apply sort to matches
        if (is_string($sort) && $sort !== '') {
            $matches = $this->sortCourses($matches, $sort, $order);
        }

        // Apply limit to matches
        if ($limit !== null) {
            $matches = $matches->take($limit)->values();
        }

        $existingCourseIds = $matches
            ->pluck('course_id')
            ->filter()
            ->map(fn ($courseId) => (int) $courseId)
            ->unique()
            ->values()
            ->all();

        $availableCourseIds = collect();

        if ($selectedCentreId !== null) {
            $availableCourseIds = $this->getCentreCourses($selectedCentreId)
                ->pluck('id')
                ->map(fn ($courseId) => (int) $courseId)
                ->reject(fn ($courseId) => in_array($courseId, $excludedCourseIds, true) || in_array($courseId, $existingCourseIds, true))
                ->unique()
                ->values();
        }

        $availableCourses = collect();

        if ($availableCourseIds->isNotEmpty()) {
            $availableCourses = Course::with('programme')
                ->whereIn('id', $availableCourseIds)
                ->get()
                ->sortBy(fn ($course) => strtolower(trim((string) $course->programme?->title)))
                ->values();
        }

        $nextRank = $this->nextRecommendationRank($matches);

        $availableMatches = $availableCourses
            ->map(function ($course) use ($request, &$nextRank) {
                $payload = $this->buildStoredRecommendationPayload(
                    $request,
                    $course,
                    $nextRank,
                    null,
                    $course->centre_id
                );

                if (! $payload) {
                    return null;
                }

                $slotLeft = $payload['slot_left'];
                if (! is_int($slotLeft) || $slotLeft < 1) {
                    return null;
                }

                $nextRank++;

                return $payload;
            })
            ->filter()
            ->values();

        // Apply filter to available courses
        if (is_string($filter) && $filter !== '') {
            $filterLower = strtolower($filter);
            $availableMatches = $availableMatches->filter(function ($match) use ($filterLower) {
                return $this->courseMatchesFilter($match, $filterLower);
            })->values();
        }

        // Apply sort to available courses
        if (is_string($sort) && $sort !== '') {
            $availableMatches = $this->sortCourses($availableMatches, $sort, $order);
        }

        // Apply limit to available courses
        if ($limit !== null) {
            $availableMatches = $availableMatches->take($limit)->values();
        }

        $availableCoursesList = $availableMatches
            ->map(function ($match) {
                return [
                    'id' => $match['id'],
                    'title' => $match['title'],
                    'sub_title' => $match['sub_title'],
                    'duration' => $match['duration'],
                    'level' => $match['level'],
                    'image' => $match['image'],
                    'job_responsible' => $match['job_responsible'],
                    'prerequisites' => $match['prerequisites'],
                    'mode_of_delivery' => $match['mode_of_delivery'],
                    'provider' => $match['provider'],
                    'course_id' => $match['course_id'],
                    'slot_left' => $match['slot_left'],
                    'centre_id' => $match['centre_id'] ?? null,
                ];
            })
            ->values();

        if ($matches->isEmpty() && $availableCoursesList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No recommended or available courses found.',
            ]);
        }

        return response()->json([
            'success' => true,
            'title' => 'These are Your Recommended Courses',
            'description' => 'Based on your preferences, here are the recommended courses that best align with your goals',
            'matches' => $matches,
            'available_courses' => $availableCoursesList,
        ]);
    }

    public function courseSlotLeft(Request $request, ?int $courseId = null)
    {
        $data = validator(
            ['course_id' => $courseId ?? $request->query('course_id')],
            ['course_id' => 'required|integer|exists:courses,id']
        )->validate();

        $courseId = (int) $data['course_id'];
        $course = Course::findOrFail($courseId);

        $courseSessions = CourseSession::query()
            ->courseType()
            ->where('course_id', $courseId)
            ->orderBy('id')
            ->get();

        $session = $courseSessions->first();
        $slotsLeft = null;

        if ($session) {
            $courseSessionIds = $courseSessions
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $courseSessionConfirmed = DB::table('user_admission')
                ->whereIn('session', $courseSessionIds)
                ->whereNotNull('confirmed')
                ->selectRaw('session, COUNT(*) as count')
                ->groupBy('session')
                ->pluck('count', 'session');

            $confirmedCount = (int) ($courseSessionConfirmed[$session->id] ?? 0);
            $limit = (int) ($session->limit ?? 0);
            $slotsLeft = $limit > 0 ? max(0, $limit - $confirmedCount) : null;
        }

        return response()->json([
            'success' => true,
            'course_id' => $course->id,
            'slot_left' => $slotsLeft,
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
        $top = $scored->filter(fn ($p) => $p->match_count > 0)
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
            'title' => 'These are Your Recommended Courses',
            'description' => 'Based on your preferences, here are the recommended courses that align best with your goals',
            'matches' => $result,
        ]);
    }

    public function recommendCourses(Request $request)
    {
        $data = $request->validate([
            'option_ids' => 'required|array',
            'option_ids.*' => 'integer|exists:course_match_options,id',
            'userId' => 'nullable|exists:users,userId',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'centre_id' => 'nullable|integer|exists:centres,id',
            'debug' => 'sometimes|boolean',
        ]);

        $userId = $data['userId'] ?? null;
        $branchId = $data['branch_id'] ?? null;
        $centreId = $data['centre_id'] ?? null;

        $user = null;
        if ($userId) {
            $user = User::where('userId', $userId)->first();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        $optionIds = array_values($data['option_ids']);
        $includeDebug = filter_var($request->input('debug', false), FILTER_VALIDATE_BOOLEAN);

        $studentLevel = $user ? strtolower(trim((string) $user->student_level)) : '';
        // Log::info('Student level: ' . $studentLevel);

        $centreCourses = $this->getCentreCourses($centreId !== null ? (int) $centreId : null);
        $programmeIds = $centreCourses->pluck('programme_id')->unique()->values()->all();

        [$preferredDelivery, $deliveryOptionIds] = $this->detectPreferredDelivery($optionIds);
        [, $categoryOptionIds] = $this->detectCategorySelections($optionIds);

        $programmes = $this->getProgrammesForLevel($programmeIds, $studentLevel, $preferredDelivery);

        $matchGroups = $this->buildMatchGroups($optionIds, $deliveryOptionIds, $categoryOptionIds);

        $scored = $this->scoreProgrammesByTags(
            $programmes,
            $optionIds,
            $matchGroups,
            $includeDebug
        );

        $top = $scored
            ->filter(fn ($programme) => $programme->match_count > 0)
            ->sort(function ($a, $b) {
                $percentageCompare = $b->match_percentage <=> $a->match_percentage;
                if ($percentageCompare !== 0) {
                    return $percentageCompare;
                }

                return strcasecmp((string) $a->title, (string) $b->title);
            })
            ->take(4)
            ->values();

        // Exclude user's registered course programme from recommendations
        if ($user && $user->registered_course) {
            $registeredCourse = $user->course;
            $registeredProgrammeId = $registeredCourse?->programme_id;

            if ($registeredProgrammeId) {
                $top = $top->filter(fn ($programme) => (int) $programme->id !== (int) $registeredProgrammeId)
                    ->values();
            }
        }

        $centresByProgramme = $this->buildCentresByProgramme($centreCourses);

        $result = $top->map(function ($programme, $index) use ($request, $centreCourses, $includeDebug) {
            $programmeCourses = $centreCourses->where('programme_id', $programme->id);
            $courseId = $programmeCourses->first()?->id;
            $slotLeftResponse = $courseId ? $this->courseSlotLeft($request, (int) $courseId) : null;
            $slotLeft = $slotLeftResponse ? $slotLeftResponse->getData(true)['slot_left'] : null;

            $payload = [
                'rank' => '#'.($index + 1),
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
                'match_percentage' => $programme->match_percentage.'% Match',
                'course_id' => $courseId,
                'slot_left' => $slotLeft,
                // 'centres' => $centresByProgramme->get($programme->id, collect())->values()
            ];

            if ($includeDebug) {
                $payload['match_breakdown'] = $programme->match_breakdown ?? [];
            }

            return $payload;
        });

        if ($userId) {
            $this->storeRecommendations($top, $centreCourses, $optionIds, $studentLevel, $userId);
        }

        return response()->json([
            'success' => true,
            'title' => 'These are Your Recommended Courses',
            'description' => 'Based on your preferences, here are the recommended courses that align best with your goals',
            'matches' => $result,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Course>
     */
    protected function getBranchCourses(?int $branchId = null)
    {
        $today = Carbon::today()->toDateString();

        $query = Course::join('centres', 'courses.centre_id', '=', 'centres.id')
            ->join('admission_batches', 'courses.batch_id', '=', 'admission_batches.id')
            ->whereNotNull('courses.programme_id')
            ->where('courses.status', 1)
            ->where('admission_batches.start_date', '<=', $today)
            ->where('admission_batches.end_date', '>=', $today)
            ->where('admission_batches.completed', false)
            ->where('admission_batches.status', true);

        if ($branchId !== null) {
            $query->where('centres.branch_id', $branchId);
        }

        return $query->get(['courses.id', 'courses.programme_id', 'courses.centre_id']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Course>
     */
    protected function getCentreCourses(?int $centreId = null)
    {
        $today = Carbon::today()->toDateString();

        $query = Course::join('centres', 'courses.centre_id', '=', 'centres.id')
            ->join('admission_batches', 'courses.batch_id', '=', 'admission_batches.id')
            ->whereNotNull('courses.programme_id')
            ->where('courses.status', 1)
            ->where('admission_batches.start_date', '<=', $today)
            ->where('admission_batches.end_date', '>=', $today)
            ->where('admission_batches.completed', false)
            ->where('admission_batches.status', true);

        if ($centreId !== null) {
            $query->where('courses.centre_id', $centreId);
        }

        return $query->get(['courses.id', 'courses.programme_id', 'courses.centre_id']);
    }

    /**
     * @param  array<int>  $programmeIds
     * @return \Illuminate\Support\Collection<int, \App\Models\Programme>
     */
    protected function getProgrammesForLevel(array $programmeIds, string $studentLevel, ?string $preferredDelivery = null)
    {
        $query = Programme::select('id', 'title', 'sub_title', 'duration', 'level', 'job_responsible', 'image', 'prerequisites', 'mode_of_delivery', 'provider', 'course_category_id')
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

        if ($hasInPerson && ! $hasOnline) {
            return ['In Person', array_values(array_unique($detected['In Person']))];
        }

        if ($hasOnline && ! $hasInPerson) {
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
     * @param  array<int>  $optionIds
     * @return array{0: array<int>, 1: array<int>, 2: array<int,int>}
     */
    protected function detectCategorySelections(array $optionIds): array
    {
        $options = CourseMatchOption::with(['courseMatch:id,type'])
            ->whereIn('id', $optionIds)
            ->get(['id', 'answer', 'value', 'course_match_id']);

        $categories = CourseCategory::query()
            ->select('id', 'title')
            ->get();

        $categoriesById = $categories->keyBy('id');
        $categoriesByTitle = $categories->mapWithKeys(function ($category) {
            $normalized = $this->normalizeOptionValue($category->title);

            return $normalized !== '' ? [$normalized => $category->id] : [];
        })->all();
        $categoriesBySlug = $categories->mapWithKeys(function ($category) {
            $slug = Str::slug($category->title);

            return $slug !== '' ? [$slug => $category->id] : [];
        })->all();

        $categoryIds = [];
        $categoryOptionIds = [];
        $categoryOptionMap = [];

        foreach ($options as $option) {
            $raw = $option->value ?: $option->answer;
            $raw = trim((string) $raw);
            $normalized = $this->normalizeOptionValue($raw);
            $type = $this->normalizeOptionValue($option->courseMatch?->type ?? '');

            $isCategoryType = $this->isCategoryType($type);
            $matchedCategoryId = null;

            if ($raw !== '' && ctype_digit($raw)) {
                $candidateId = (int) $raw;
                if ($categoriesById->has($candidateId)) {
                    $matchedCategoryId = $candidateId;
                }
            } elseif ($normalized !== '' && isset($categoriesByTitle[$normalized])) {
                $matchedCategoryId = $categoriesByTitle[$normalized];
            } else {
                $slug = Str::slug($raw);
                if ($slug !== '' && isset($categoriesBySlug[$slug])) {
                    $matchedCategoryId = $categoriesBySlug[$slug];
                }
            }

            if ($matchedCategoryId !== null || $isCategoryType) {
                $categoryOptionIds[] = $option->id;
                if ($matchedCategoryId !== null) {
                    $categoryIds[] = $matchedCategoryId;
                    $categoryOptionMap[$option->id] = $matchedCategoryId;
                }
            }
        }

        return [
            array_values(array_unique($categoryIds)),
            array_values(array_unique($categoryOptionIds)),
            $categoryOptionMap,
        ];
    }

    protected function isCategoryType(string $type): bool
    {
        if ($type === '') {
            return false;
        }

        return str_contains($type, 'category')
            || str_contains($type, 'area')
            || str_contains($type, 'interest');
    }

    /**
     * @param  array<int>  $optionIds
     * @param  array<int>  $deliveryOptionIds
     * @param  array<int>  $categoryOptionIds
     * @return array<int, array<string, mixed>>
     */
    protected function buildMatchGroups(array $optionIds, array $deliveryOptionIds, array $categoryOptionIds): array
    {
        $options = CourseMatchOption::with(['courseMatch:id,type,reference_source,question'])
            ->whereIn('id', $optionIds)
            ->get(['id', 'answer', 'value', 'course_match_id']);

        return $options
            ->groupBy('course_match_id')
            ->map(function ($groupOptions) use ($deliveryOptionIds, $categoryOptionIds) {
                $courseMatch = $groupOptions->first()->courseMatch;
                $referenceSource = $this->normalizeOptionValue($courseMatch?->reference_source ?? '');
                $type = $this->normalizeOptionValue($courseMatch?->type ?? '');
                $groupOptionIds = $groupOptions->pluck('id')->all();
                $groupOptionValues = $groupOptions
                    ->map(fn ($option) => trim((string) ($option->value ?: $option->answer)))
                    ->filter(fn ($value) => $value !== '')
                    ->values()
                    ->all();

                $groupType = 'tag';

                if ($this->isBranchReference($referenceSource)) {
                    $groupType = 'branch';
                } elseif ($this->isCategoryReference($referenceSource)) {
                    $groupType = 'category';
                } elseif ($this->isDeliveryReference($referenceSource)) {
                    $groupType = 'delivery';
                } elseif (! empty(array_intersect($groupOptionIds, $deliveryOptionIds))) {
                    $groupType = 'delivery';
                } elseif (! empty(array_intersect($groupOptionIds, $categoryOptionIds)) || $this->isCategoryType($type)) {
                    $groupType = 'category';
                }

                return [
                    'course_match_id' => $courseMatch?->id,
                    'question' => $courseMatch?->question,
                    'reference_source' => $courseMatch?->reference_source,
                    'type' => $groupType,
                    'option_ids' => $groupOptionIds,
                    'option_values' => $groupOptionValues,
                ];
            })
            ->values()
            ->all();
    }

    protected function isBranchReference(string $referenceSource): bool
    {
        if ($referenceSource === '') {
            return false;
        }

        return str_contains($referenceSource, 'branch')
            || str_contains($referenceSource, 'region');
    }

    protected function isCategoryReference(string $referenceSource): bool
    {
        if ($referenceSource === '') {
            return false;
        }

        return str_contains($referenceSource, 'category')
            || str_contains($referenceSource, 'course_categories')
            || str_contains($referenceSource, 'course category');
    }

    protected function isDeliveryReference(string $referenceSource): bool
    {
        if ($referenceSource === '') {
            return false;
        }

        return str_contains($referenceSource, 'mode')
            || str_contains($referenceSource, 'delivery');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Programme>  $programmes
     * @param  array<int>  $categoryIds
     * @param  array<int>  $categoryOptionIds
     * @return \Illuminate\Support\Collection<int, \App\Models\Programme>
     */
    protected function filterProgrammesByCategory($programmes, array $categoryOptionIds)
    {
        if (empty($categoryOptionIds)) {
            return $programmes;
        }

        return $programmes->filter(function ($programme) use ($categoryOptionIds) {
            $programmeOptionIds = $programme->tags->pluck('id')->toArray();

            return count(array_intersect($categoryOptionIds, $programmeOptionIds)) > 0;
        })->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Programme>  $programmes
     * @param  array<int, array<string, mixed>>  $groups
     * @return \Illuminate\Support\Collection<int, \App\Models\Programme>
     */
    protected function scoreProgrammesByGroups($programmes, array $groups, ?string $preferredDelivery, array $categoryOptionIds, int $branchId, ?string $branchTitle, bool $includeBreakdown = false, bool $hasCategoryGroup = false)
    {
        $totalGroups = count($groups);

        return $programmes->map(function ($programme) use ($groups, $preferredDelivery, $categoryOptionIds, $branchId, $branchTitle, $totalGroups, $includeBreakdown, $hasCategoryGroup) {
            $programmeOptionIds = $programme->tags->pluck('id')->toArray();

            $matches = 0;
            $breakdown = [];
            $categoryMatched = null;
            foreach ($groups as $group) {
                $matched = false;
                switch ($group['type']) {
                    case 'delivery':
                        if (
                            $preferredDelivery !== null
                            && strtolower((string) $programme->mode_of_delivery) === strtolower($preferredDelivery)
                        ) {
                            $matches++;
                            $matched = true;
                        }
                        break;
                    case 'category':
                        $matched = $this->programmeMatchesCategory(
                            $programme,
                            $categoryOptionIds,
                            $programmeOptionIds
                        );
                        $categoryMatched = $categoryMatched === null ? $matched : ($categoryMatched || $matched);
                        if ($matched) {
                            $matches++;
                        }
                        break;
                    case 'branch':
                        if ($this->branchSelectionMatches($branchId, $branchTitle, $group['option_values'] ?? [])) {
                            $matches++;
                            $matched = true;
                        }
                        break;
                    default:
                        if (count(array_intersect($group['option_ids'] ?? [], $programmeOptionIds)) > 0) {
                            $matches++;
                            $matched = true;
                        }
                        break;
                }

                if ($includeBreakdown) {
                    $breakdown[] = [
                        'course_match_id' => $group['course_match_id'] ?? null,
                        'question' => $group['question'] ?? null,
                        'reference_source' => $group['reference_source'] ?? null,
                        'type' => $group['type'] ?? null,
                        'option_ids' => $group['option_ids'] ?? [],
                        'option_values' => $group['option_values'] ?? [],
                        'matched' => $matched,
                    ];
                }
            }

            $percentage = $totalGroups > 0 ? round(($matches / $totalGroups) * 100) : 100;

            $programme->setAttribute('match_percentage', $percentage);
            $programme->setAttribute('match_count', $matches);
            if ($hasCategoryGroup) {
                $programme->setAttribute('category_matched', (bool) $categoryMatched);
            }
            if ($includeBreakdown) {
                $programme->setAttribute('match_breakdown', $breakdown);
            }

            return $programme;
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Programme>  $programmes
     * @param  array<int>  $optionIds
     * @param  array<int, array<string, mixed>>  $groups
     * @return \Illuminate\Support\Collection<int, \App\Models\Programme>
     */
    protected function scoreProgrammesByTags($programmes, array $optionIds, array $groups, bool $includeBreakdown = false)
    {
        $totalOptions = count($optionIds);

        return $programmes->map(function ($programme) use ($optionIds, $groups, $includeBreakdown, $totalOptions) {
            $programmeOptionIds = $programme->tags->pluck('id')->toArray();
            $matchedOptionIds = array_values(array_intersect($optionIds, $programmeOptionIds));
            $matches = count($matchedOptionIds);
            $percentage = $totalOptions > 0 ? round(($matches / $totalOptions) * 100) : 100;

            $programme->setAttribute('match_percentage', $percentage);
            $programme->setAttribute('match_count', $matches);

            if ($includeBreakdown) {
                $breakdown = [];

                foreach ($groups as $group) {
                    $groupOptionIds = $group['option_ids'] ?? [];
                    $groupMatchedOptionIds = array_values(array_intersect($groupOptionIds, $programmeOptionIds));

                    $breakdown[] = [
                        'course_match_id' => $group['course_match_id'] ?? null,
                        'question' => $group['question'] ?? null,
                        'reference_source' => $group['reference_source'] ?? null,
                        'type' => $group['type'] ?? null,
                        'option_ids' => $groupOptionIds,
                        'option_values' => $group['option_values'] ?? [],
                        'matched' => count($groupMatchedOptionIds) > 0,
                        'matched_option_ids' => $groupMatchedOptionIds,
                    ];
                }

                $programme->setAttribute('match_breakdown', $breakdown);
            }

            return $programme;
        });
    }

    /**
     * @param  \App\Models\Programme  $programme
     * @param  array<int>  $categoryIds
     * @param  array<int>  $categoryOptionIds
     * @param  array<int,int>  $categoryOptionMap
     * @param  array<int>  $programmeOptionIds
     */
    protected function programmeMatchesCategory($programme, array $categoryOptionIds, array $programmeOptionIds): bool
    {
        if (empty($categoryOptionIds)) {
            return false;
        }

        return count(array_intersect($categoryOptionIds, $programmeOptionIds)) > 0;
    }

    /**
     * @param  array<int, string>  $values
     */
    protected function branchSelectionMatches(int $branchId, ?string $branchTitle, array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        $normalizedTitle = $branchTitle ? $this->normalizeOptionValue($branchTitle) : '';
        $slugTitle = $branchTitle ? Str::slug($branchTitle) : '';

        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if (ctype_digit($value) && (int) $value === $branchId) {
                return true;
            }

            $normalizedValue = $this->normalizeOptionValue($value);
            if ($normalizedValue !== '' && $normalizedValue === $normalizedTitle) {
                return true;
            }

            $slugValue = Str::slug($value);
            if ($slugValue !== '' && $slugValue === $slugTitle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Course>  $branchCourses
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    protected function buildCentresByProgramme($branchCourses)
    {
        $centreIds = $branchCourses->pluck('centre_id')->unique()->values()->all();

        if (empty($centreIds)) {
            return collect();
        }

        $centresById = Centre::with(['constituency:id,title', 'districts:id,title'])
            ->whereIn('id', $centreIds)
            ->get()
            ->keyBy('id');

        return $branchCourses
            ->groupBy('programme_id')
            ->map(function ($courses) use ($centresById) {
                $courseCentreIds = $courses->pluck('centre_id')->unique()->values();

                return $courseCentreIds->map(function ($centreId) use ($centresById) {
                    $centre = $centresById->get($centreId);
                    if (! $centre) {
                        return null;
                    }

                    $district = $centre->districts->first();

                    return [
                        'id' => $centre->id,
                        'title' => $centre->title,
                        'gps_address' => $centre->gps_address,
                        'is_pwd_friendly' => $centre->is_pwd_friendly,
                        'wheelchair_accessible' => $centre->wheelchair_accessible,
                        'has_access_ramp' => $centre->has_access_ramp,
                        'has_accessible_toilet' => $centre->has_accessible_toilet,
                        'has_elevator' => $centre->has_elevator,
                        'constituency' => $centre->constituency ? ['title' => $centre->constituency->title] : null,
                        'district' => $district ? ['title' => $district->title] : null,
                    ];
                })->filter()->values();
            });
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
    protected function storeRecommendations($combined, $centreCourses, array $optionIds, string $studentLevel, string $userId): void
    {
        if ($combined->isEmpty()) {
            return;
        }

        $now = now();
        $recommendationRows = $combined->map(function ($programme, $index) use ($centreCourses, $optionIds, $now, $userId) {
            $programmeCourses = $centreCourses->where('programme_id', $programme->id);
            $primaryCourse = $programmeCourses->first();

            return [
                'user_id' => $userId,
                'course_id' => $primaryCourse?->id,
                'centre_id' => $primaryCourse?->centre_id,
                'rank' => $index + 1,
                'match_percentage' => $programme->match_percentage,
                'option_ids' => json_encode($optionIds),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        $courseIds = collect($recommendationRows)
            ->pluck('course_id')
            ->filter()
            ->unique()
            ->values();

        $hasNullCourse = collect($recommendationRows)->pluck('course_id')->contains(null);

        DB::transaction(function () use ($userId, $recommendationRows) {
            DB::table('user_course_recommendations')
                ->where('user_id', $userId)
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
        $top = $scored->filter(fn ($p) => $p->match_count > 0)
            ->sortByDesc('match_percentage')
            ->take(5)
            ->values();

        // Format response with ranking number
        $result = $top->map(function ($programme, $index) {
            return [
                'rank' => '#'.($index + 1),
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
                'match_percentage' => $programme->match_percentage.'% Match',
            ];
        });

        return response()->json([
            'success' => true,
            'title' => 'These are Your Recommended Courses',
            'description' => 'Based on your preferences, here are the recommended courses that align best with your goals',
            'matches' => $result,
        ]);
    }

    public function allProgrammesWithCourseMatch()
    {
        $programmes = Programme::with([
            'tags.courseMatch' => function ($query) {
                $query->where('status', 1);
            },
        ])
            ->where('status', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $programmes,
        ]);
    }

    protected function buildStoredRecommendationPayload(
        Request $request,
        ?Course $course,
        int $rankValue,
        $matchPercentage = null,
        ?int $centreId = null
    ): ?array {
        $programme = $course?->programme;

        if (! $programme) {
            return null;
        }

        $courseId = $course->id ? (int) $course->id : null;
        $slotLeftResponse = $courseId ? $this->courseSlotLeft($request, $courseId) : null;
        $slotLeft = $slotLeftResponse ? $slotLeftResponse->getData(true)['slot_left'] : null;

        return [
            'rank' => '#'.$rankValue,
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
            'match_percentage' => $matchPercentage !== null ? ((int) $matchPercentage).'% Match' : null,
            'course_id' => $courseId,
            'slot_left' => $slotLeft,
            'centre_id' => $centreId ?? $course?->centre_id,
        ];
    }

    protected function nextRecommendationRank($matches): int
    {
        $maxRank = collect($matches)
            ->map(function ($match) {
                $rank = ltrim((string) ($match['rank'] ?? ''), '#');

                return ctype_digit($rank) ? (int) $rank : null;
            })
            ->filter(fn ($rank) => $rank !== null)
            ->max();

        return $maxRank ? ($maxRank + 1) : 1;
    }

    /**
     * Check if a course match matches the given filter string.
     * Searches in title, sub_title, level, mode_of_delivery, and provider.
     */
    protected function courseMatchesFilter(array $match, string $filterLower): bool
    {
        $searchableFields = [
            'title',
            'sub_title',
            'level',
            'mode_of_delivery',
            'provider',
            'duration',
            'job_responsible',
            'prerequisites',
        ];

        foreach ($searchableFields as $field) {
            $value = $match[$field] ?? null;
            if ($value !== null && str_contains(strtolower((string) $value), $filterLower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sort a collection of course matches by the specified field and order.
     */
    protected function sortCourses($courses, string $sortField, string $order): \Illuminate\Support\Collection
    {
        $sortableFields = [
            'title',
            'level',
            'duration',
            'mode_of_delivery',
            'provider',
            'slot_left',
            'rank',
        ];

        if (! in_array($sortField, $sortableFields, true)) {
            return $courses;
        }

        return $courses->sortBy(function ($course) use ($sortField) {
            $value = $course[$sortField] ?? null;
            if ($sortField === 'slot_left') {
                return is_int($value) ? $value : PHP_INT_MAX;
            }
            if ($sortField === 'rank') {
                $rank = ltrim((string) $value, '#');

                return ctype_digit($rank) ? (int) $rank : PHP_INT_MAX;
            }

            return strtolower((string) $value);
        }, SORT_NATURAL)->when($order === 'desc', fn ($collection) => $collection->reverse())->values();
    }
}
