<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OexQuestionMaster extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'oex_question_masters';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];

    protected $fillable = ['exam_set_id', 'questions', 'ans', 'options', 'status', 'exam_id'];
    protected $casts = [
        'status' => 'boolean',
        'options' => 'array',
    ];

    public function programmes()
    {
        return $this->belongsToMany(Programme::class, 'oex_question_master_programme');
    }

    public function exam()
    {
        return $this->belongsTo(OexExamMaster::class, 'exam_id', 'id');
    }


    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = json_encode($value);
    }

    public function getOptionsAttribute($value)
    {
        return json_decode($value, true);
    }

}
