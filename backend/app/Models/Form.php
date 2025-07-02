<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Form extends Model
{
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
                $model->schema = array_map(function ($schema) {
                    $schema['field_name'] = Str::slug(strtolower($schema['title']));
                    
                    if (isset($schema['validators']) && is_array($schema['validators'])) {
                        $schema['validators'] = array_map(
                            fn($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                            $schema['validators']
                        );
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
}
