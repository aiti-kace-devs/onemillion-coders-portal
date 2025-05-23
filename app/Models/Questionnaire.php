<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Questionnaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'schema',
        'code',
        'message_after_submission',
        'message_when_inactive',
        'active',
        'status'
    ];

    protected $casts = [
        'schema' => 'array',
        'active' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if ($model->isDirty('schema')) {
                $schema = is_array($model->schema) ? $model->schema : [];

                $processedSchema = array_map(function ($section) {
                    $section['title'] = ($section['type'] !== 'others') ? $section['type'] : ($section['title'] ?? '');

                    if (($section['type'] ?? null) === 'instructors') {
                        // Check if an instructor question already exists
                        $hasInstructorQuestion = collect($section['questions'] ?? [])
                            ->contains(fn($q) => ($q['type'] ?? null) === 'instructor_feedback');

                        // Only add default instructor question if none exists
                        // if (!$hasInstructorQuestion) {
                        //     $section['questions'][] = [
                        //         'title' => $section['type'],
                        //         'field_name' => Str::slug(strtolower($section['type'])),
                        //         'validators' => [
                        //             'required' => true,
                        //             'unique' => false
                        //         ],
                        //         'type' => 'instructor_feedback' // Added explicit type
                        //     ];
                        // }
                    }

                    // Ensure section has questions array
                    $section['questions'] = $section['questions'] ?? [];

                    // Process each question in the section
                    $section['questions'] = array_map(function ($question) {
                        // Ensure question is an array
                        if (!is_array($question)) return $question;

                        // Generate field_name from title if it doesn't exist
                        if (!empty($question['title']) && empty($question['field_name'])) {
                            $question['field_name'] = Str::slug(strtolower($question['title']));
                        }

                        // Process validators
                        if (isset($question['validators']) && is_array($question['validators'])) {
                            $question['validators'] = array_map(
                                function ($value) {
                                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                                },
                                $question['validators']
                            );
                        }

                        return $question;
                    }, $section['questions']);

                    return $section;
                }, $schema);

                $model->schema = $processedSchema;
            }
        });
    }

    public function responses()
    {
        return $this->hasMany(QuestionnaireResponse::class);
    }
}
