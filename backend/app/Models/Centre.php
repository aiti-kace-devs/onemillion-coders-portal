<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Centre extends Model
{
    use CrudTrait;
    use HasFactory;
    use \StatamicRadPack\Runway\Traits\HasRunwayResource;


    protected $fillable = [
        'title',
        'branch_id',
        'status'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function programme()
    {
        return $this->belongsToMany(Programme::class, 'courses');
    }
}
