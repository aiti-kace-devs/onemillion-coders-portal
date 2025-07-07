<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'title',
        'starts_at',
        'ends_at',
        'status'
    ];
}
