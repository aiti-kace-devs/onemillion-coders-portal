<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'admission_batches';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = ['title', 'description', 'start_date', 'end_date', 'total_admitted_students', 'year', 'total_completed_students', 'completed', 'status'];
    // protected $hidden = [];

    protected $casts = [
        'status' => 'boolean',
        'completed' => 'boolean',
    ];
    // public function course()
    // {
    //     return $this->belongsTo(Course::class, 'course_id', 'id');
    // }


    protected static function booted()
    {
        static::creating(function ($batch) {
            $batch->year = now()->year;
        });

    }


    public function assignedCourseBatches()
    {
        return $this->belongsToMany(Course::class, 'course_batches', 'batch_id', 'course_id');
    }


    public function batches()
    {
        return $this->assignedCourseBatches();
    }

    public function admittedStudents()
    {
        return $this->hasManyThrough(
            UserAdmission::class,
            Course::class,
            'batch_id', // Course foreign key
            'course_id', // UserAdmission foreign key
            'id', // Batch PK
            'id' // Course PK
        )->whereNotNull('confirmed');
    }



    public function calculateAdmittedStudents(): int
    {
        if ($this->relationLoaded('admittedStudents')) {
            return $this->admittedStudents->count();
        }
        
        return $this->admittedStudents()->count();
    }



}
