<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAssessment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'answered_question_ids' => 'array',
        'completed' => 'boolean',
        'level_started_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
