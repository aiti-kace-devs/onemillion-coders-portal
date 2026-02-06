<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oex_exam_master extends Model
{
    use HasFactory;

    protected $table = "oex_exam_masters";

    protected $primaryKey = "id";

    protected $fillable = ['title', 'category', 'passmark', 'exam_date', 'status', 'exam_duration'];

    protected $casts = [
        'status' => 'boolean',
        'exam_date' => 'datetime',
        'passmark' => 'integer',
        'exam_duration' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Oex_category::class, 'category', 'id');
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
