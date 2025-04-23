<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'centre_id',
        'programme_id',
        'course_name',
        'location',
        'duration',
        'start_date',
        'end_date',
        'status',
    ];

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function assignedAdmins()
{
    return $this->belongsToMany(Admin::class, 'admin_course', 'course_id', 'admin_id');
}
}
