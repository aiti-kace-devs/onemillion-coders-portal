<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Tag extends Model
{
    use CrudTrait;

    protected $fillable = ['name'];

    public function courses()
    {
        return $this->morphedByMany(Course::class, 'taggable');
    }

    public function questions()
    {
        return $this->morphedByMany(OexQuestionMaster::class, 'taggable');
    }
}
