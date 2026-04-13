<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgrammeBatch extends Model
{
    use CrudTrait;
    use HasFactory, SoftDeletes;

    protected $table = 'programme_batches';

    protected $fillable = [
        'admission_batch_id',
        'programme_id',
        'centre_id',
        'start_date',
        'end_date',
        'max_enrolments',
        'available_slots',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'boolean',
    ];

    public function admissionBatch()
    {
        return $this->belongsTo(Batch::class, 'admission_batch_id');
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }

    public function userAdmissions()
    {
        return $this->hasMany(UserAdmission::class, 'programme_batch_id');
    }
}
