<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CourseCategory;
use App\Models\CourseMatch;
use Illuminate\Support\Facades\DB;

class CourseMatchReferenceService
{
    /**
     * @return array<string, array<string, mixed>>|null
     */
    public function getReferenceOptions(?string $referenceSource): ?array
    {
        if (!$referenceSource) {
            return null;
        }

        if ($referenceSource === 'course_categories') {
            return CourseCategory::query()
                ->select('id', 'title', 'description', 'status')
                ->where('status', true)
                ->orderBy('title')
                ->get()
                ->mapWithKeys(function ($category) {
                    $value = (string) $category->id;
                    return [$value => [
                        'answer' => $category->title,
                        'value' => $value,
                        'description' => $category->description,
                        'status' => $category->status ? 1 : 0,
                    ]];
                })
                ->all();
        }

        if ($referenceSource === 'branches') {
            return Branch::query()
                ->select('id', 'title', 'status')
                ->where('status', true)
                ->orderBy('title')
                ->get()
                ->mapWithKeys(function ($branch) {
                    $value = (string) $branch->id;
                    return [$value => [
                        'answer' => $branch->title,
                        'value' => $value,
                        'description' => null,
                        'status' => $branch->status ? 1 : 0,
                    ]];
                })
                ->all();
        }

        if ($referenceSource === 'mode_of_delivery') {
            return [
                'online' => [
                    'answer' => 'Online',
                    'value' => 'online',
                    'description' => null,
                    'status' => 1,
                ],
                'in_person' => [
                    'answer' => 'In Person',
                    'value' => 'in_person',
                    'description' => null,
                    'status' => 1,
                ],
            ];
        }

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $referenceOptions
     */
    public function syncCourseMatchOptions(CourseMatch $courseMatch, array $referenceOptions): void
    {
        $existingOptions = $courseMatch->courseMatchOptions()->get(['id', 'value']);
        $existingByValue = $existingOptions->keyBy('value');

        $incomingValues = array_keys($referenceOptions);
        $existingValues = $existingByValue->keys()->all();

        $valuesToDelete = array_diff($existingValues, $incomingValues);
        if (!empty($valuesToDelete)) {
            $deleteIds = $existingByValue->only($valuesToDelete)->pluck('id')->filter()->all();
            if (!empty($deleteIds)) {
                DB::table('programme_course_match_options')
                    ->whereIn('course_match_option_id', $deleteIds)
                    ->delete();

                $courseMatch->courseMatchOptions()->whereIn('id', $deleteIds)->delete();
            }
        }

        foreach ($referenceOptions as $value => $payload) {
            $data = [
                'answer' => $payload['answer'] ?? $value,
                'value' => $payload['value'] ?? $value,
                'icon' => $payload['icon'] ?? null,
                'description' => $payload['description'] ?? null,
                'status' => $payload['status'] ?? 1,
            ];

            if ($existingByValue->has($value)) {
                $courseMatch->courseMatchOptions()->where('id', $existingByValue[$value]->id)->update($data);
                continue;
            }

            $courseMatch->courseMatchOptions()->create($data);
        }
    }

    public function syncReferenceSource(string $referenceSource): void
    {
        $referenceOptions = $this->getReferenceOptions($referenceSource);
        if ($referenceOptions === null) {
            return;
        }

        CourseMatch::query()
            ->where('reference_source', $referenceSource)
            ->get()
            ->each(function (CourseMatch $courseMatch) use ($referenceOptions) {
                $this->syncCourseMatchOptions($courseMatch, $referenceOptions);
            });
    }
}
