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
use Illuminate\Support\Str;

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
                'branch_id' => 'required|integer|exists:branches,id',
                'debug' => 'sometimes|boolean',
            ]);

            $user = User::where('userId', $data['userId'])->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            $optionIds = array_values($data['option_ids']);
            $branchId = (int) $data['branch_id'];
            $includeDebug = filter_var($request->input('debug', false), FILTER_VALIDATE_BOOLEAN);

            $studentLevel = strtolower(trim((string) $user?->student_level));
            // Log::info('Student level: ' . $studentLevel);

            $branchCourses = $this->getBranchCourses($branchId);
            $programmeIds = $branchCourses->pluck('programme_id')->unique()->values()->all();

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

            $centresByProgramme = $this->buildCentresByProgramme($branchCourses);

            $result = $top->map(function ($programme, $index) use ($branchCourses, $centresByProgramme, $includeDebug) {
                $programmeCourses = $branchCourses->where('programme_id', $programme->id);

                $payload = [
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
                    'centres' => $centresByProgramme->get($programme->id, collect())->values()
                ];

                if ($includeDebug) {
                    $payload['match_breakdown'] = $programme->match_breakdown ?? [];
                }

                return $payload;
            });

            $this->storeRecommendations($top, $branchCourses, $optionIds, $studentLevel, $data['userId']);

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
        protected function getBranchCourses(int $branchId)
        {
            $today = Carbon::today()->toDateString();

            return Course::join('centres', 'courses.centre_id', '=', 'centres.id')
                ->join('admission_batches', 'courses.batch_id', '=', 'admission_batches.id')
                ->where('centres.branch_id', $branchId)
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
                    } elseif (!empty(array_intersect($groupOptionIds, $deliveryOptionIds))) {
                        $groupType = 'delivery';
                    } elseif (!empty(array_intersect($groupOptionIds, $categoryOptionIds)) || $this->isCategoryType($type)) {
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
                            if ($preferredDelivery !== null
                                && strtolower((string) $programme->mode_of_delivery) === strtolower($preferredDelivery)) {
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
                        if (!$centre) {
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
            $recommendationRows = $combined->map(function ($programme, $index) use ($centreCourses, $optionIds, $studentLevel, $now, $userId) {
                $programmeCourses = $centreCourses->where('programme_id', $programme->id);

                return [
                    'user_id' => $userId,
                    'course_id' => $programmeCourses->first()?->id,
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

            DB::transaction(function () use ($userId, $courseIds, $hasNullCourse, $recommendationRows) {
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
