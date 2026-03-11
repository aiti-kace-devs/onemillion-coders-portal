<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Models\CourseCategory;
use App\Models\Branch;
use App\Models\Form;
use App\Models\Course;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RegistrationFormAPIController extends Controller
{




    public function index(Request $request)
    {
        $groupConfig = [
            'Personal Information' => [
                'first_name',
                'last_name',
                'middle_name',
                'age',
                'gender',
                'email',
                'phone',
            ],
            'Educational Information' => [
                'highest_level_of_education',
                'certificate',
            ],
        ];

        $form = Form::all()->map(function (Form $form) use ($groupConfig) {
            $schema = collect($form->schema ?? [])->map(function ($field) {
                if (empty($field['field_name']) && ! empty($field['title'])) {
                    $field['field_name'] = Str::slug(strtolower($field['title']), '_');
                }

                return $field;
            });

            $schemaByName = $schema->keyBy('field_name');
            $assigned = [];

            $grouped = collect($groupConfig)
                ->map(function (array $fieldNames, string $groupTitle) use ($schemaByName, &$assigned) {
                    $fields = collect($fieldNames)
                        ->map(function (string $fieldName) use ($schemaByName, &$assigned) {
                            if (! $schemaByName->has($fieldName)) {
                                return null;
                            }

                            $assigned[] = $fieldName;
                            return $schemaByName->get($fieldName);
                        })
                        ->filter()
                        ->values();

                    return [
                        'title' => $groupTitle,
                        'fields' => $fields,
                    ];
                })
                ->filter(fn (array $group) => $group['fields']->isNotEmpty())
                ->values();

            $ungrouped = $schema
                ->filter(function (array $field) use ($assigned) {
                    $name = $field['field_name'] ?? null;
                    return $name && ! in_array($name, $assigned, true);
                })
                ->values();

            if ($ungrouped->isNotEmpty()) {
                $grouped->push([
                    'title' => 'Other',
                    'fields' => $ungrouped,
                ]);
            }

            $form->setAttribute('grouped_schema', $grouped);

            return $form;
        });

        return response()->json([
            'success' => true,
            'data' => $form
        ]);

    }





}
