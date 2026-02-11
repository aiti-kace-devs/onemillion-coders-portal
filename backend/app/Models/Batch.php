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
    protected $fillable = ['title', 'description', 'start_date', 'end_date', 'total_admitted_students', 'year', 'total_completed_students', 'completed', 'status', 'branch_id', 'centre_ids', 'programme_ids'];
    // protected $hidden = [];

    protected $casts = [
        'status' => 'boolean',
        'completed' => 'boolean',
    ];
    public function centres()
    {
        return $this->belongsToMany(Centre::class, 'courses', 'batch_id', 'centre_id')
            ->distinct();
    }

    public function programmes()
    {
        return $this->belongsToMany(Programme::class, 'courses', 'batch_id', 'programme_id')
            ->distinct();
    }


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

    /**
     * Get the course batches for this admission batch
     */
    public function courseBatches()
    {
        return $this->hasMany(CourseBatch::class, 'batch_id');
    }

    /**
     * Get all courses for this batch (direct relationship via batch_id)
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'batch_id');
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
