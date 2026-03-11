<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Form extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'schema',
        'code',
        'message_after_registration',
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
                $originalSchema = $model->getOriginal('schema');
                if (is_string($originalSchema)) {
                    $originalSchema = json_decode($originalSchema, true) ?: [];
                }
                if (!is_array($originalSchema)) {
                    $originalSchema = [];
                }

                $originalValidatorsByField = [];
                foreach ($originalSchema as $originalField) {
                    $originalFieldName = $originalField['field_name']
                        ?? (isset($originalField['title']) ? Str::slug(strtolower($originalField['title']), '_') : null);
                    if ($originalFieldName && isset($originalField['validators']) && is_array($originalField['validators'])) {
                        $originalValidatorsByField[$originalFieldName] = $originalField['validators'];
                    }
                }

                $model->schema = array_map(function ($schema) use ($originalValidatorsByField) {
                    $fieldName = $schema['field_name'] ?? Str::slug(strtolower($schema['title']), '_');
                    $schema['field_name'] = $fieldName;

                    if (!isset($schema['validators']) || !is_array($schema['validators'])) {
                        $inlineRequired = $schema['validators.required'] ?? null;
                        $inlineUnique = $schema['validators.unique'] ?? null;

                        if ($inlineRequired !== null || $inlineUnique !== null) {
                            $schema['validators'] = [
                                'required' => filter_var($inlineRequired, FILTER_VALIDATE_BOOLEAN),
                                'unique' => filter_var($inlineUnique, FILTER_VALIDATE_BOOLEAN),
                            ];
                        } elseif (isset($originalValidatorsByField[$fieldName])) {
                            $schema['validators'] = $originalValidatorsByField[$fieldName];
                        }

                        unset($schema['validators.required'], $schema['validators.unique']);
                    }

                    if (isset($schema['validators']) && is_array($schema['validators'])) {
                        $schema['validators'] = array_map(
                            fn($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                            $schema['validators']
                        );
                        $schema['validators'] += [
                            'required' => false,
                            'unique' => false,
                        ];
                    }

                    return $schema;
                }, $model->schema);
            }
        });
    }

    public function responses()
    {
        return $this->hasMany(FormResponse::class);
    }


    public function getPreviewButton()
    {
        return '<a href="'.route('forms.preview', $this->id).'" target="_blank" class="btn btn-sm btn-primary"><i class="la la-eye"></i>Custom Preview</a>';
    }

}
