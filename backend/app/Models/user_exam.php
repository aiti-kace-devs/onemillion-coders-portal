<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user_exam extends Model
{
    use CrudTrait;

    use HasFactory;

    protected $table = 'user_exams';
    protected $primaryKey = 'id';

    protected $fillable = ['user_id', 'exam_id', 'std_status', 'exam_joined', 'started', 'submitted'];

    protected $casts = [
        'std_status' => 'boolean',
        'exam_joined' => 'boolean',
        'started' => 'datetime',
        'submitted' => 'datetime',
    ];

    public function result()
    {
        return $this->hasOne(Oex_result::class, 'user_id', 'user_id');
    }
}
