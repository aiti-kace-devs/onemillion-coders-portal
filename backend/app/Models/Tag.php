<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Tag extends Model
{
    use CrudTrait;

    protected $fillable = ['name', 'tag_type_id'];

    public function tagType()
    {
        return $this->belongsTo(TagType::class);
    }

    public function courses()
    {
        return $this->morphedByMany(Course::class, 'taggable');
    }

    public function questions()
    {
        return $this->morphedByMany(OexQuestionMaster::class, 'taggable');
    }

    public function programmes()
    {
        return $this->morphedByMany(Programme::class, 'taggable');
    }
}
