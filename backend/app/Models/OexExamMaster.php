<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OexExamMaster extends Model
{
    use CrudTrait;
    use HasFactory;
    protected $table="oex_exam_masters";

    protected $primaryKey="id";

    protected $fillable=['title','category','passmark', 'exam_date','status','exam_duration'];
    protected $casts = [
        'status' => 'boolean',
    ];

    public function categoryRelation()
    {
        return $this->belongsTo(OexCategory::class, 'category', 'id');
    }
}
