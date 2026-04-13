<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevokedAdmission extends Model
{
    use HasFactory;

    protected $table = 'revoked_admissions';

    protected $fillable = [
        'user_id',
        'course_id',
        'batch_id',
        'programme_batch_id',
        'session',
        'location',
        'originally_confirmed_at',
        'revoked_at',
    ];

    protected $casts = [
        'originally_confirmed_at' => 'datetime',
        'revoked_at'              => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'userId');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
