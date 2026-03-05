<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Backpack\CRUD\app\Models\Traits\CrudTrait;

class TagType extends Model
{
    use CrudTrait;

    public const AVAILABLE_TARGET_MODELS = [
        'App\Models\Course' => 'Course',
        'App\Models\OexQuestionMaster' => 'Question Master',
        'App\Models\User' => 'User',
        'App\Models\Programme' => 'Programme',
        'App\Models\Batch' => 'Batch',
        'App\Models\Branch' => 'Branch',
        'App\Models\Centre' => 'Centre',
    ];

    protected $fillable = ['name', 'target_models'];

    protected $casts = [
        'target_models' => 'array',
    ];

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }
}
