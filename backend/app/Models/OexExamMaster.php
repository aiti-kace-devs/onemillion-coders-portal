<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OexExamMaster extends Model
{
    use CrudTrait;
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('exam')
            ->setDescriptionForEvent(fn(string $event) => "Exam {$event}");
    }

    protected $table = "oex_exam_masters";

    protected $primaryKey = "id";

    protected $fillable = ['title', 'category', 'passmark', 'exam_date', 'status', 'exam_duration', 'number_of_questions'];
    protected $casts = [
        'status' => 'boolean',
        'exam_date' => 'datetime',
        'passmark' => 'integer',
        'exam_duration' => 'integer',
        'number_of_questions' => 'integer',


    ];

    public function categoryRelation()
    {
        return $this->belongsTo(OexCategory::class, 'category', 'id');
    }

    public function questions()
    {
        return $this->hasMany(OexQuestionMaster::class, 'exam_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('questions_count', function ($query) {
            $query->withCount('questions');
        });
    }
}
