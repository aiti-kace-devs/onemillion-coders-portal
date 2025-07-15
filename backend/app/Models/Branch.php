<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'title',
        'status'
    ];


    protected $casts = [
        'status' => 'boolean',
    ];
    public function centre(){
        return $this->hasMany(Centre::class);
    }
}
